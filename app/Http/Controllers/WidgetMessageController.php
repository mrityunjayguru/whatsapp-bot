<?php

namespace App\Http\Controllers;

use App\Events\NewMessage;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Employee;
use App\Models\Message;
use App\Services\WidgetApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Public, unauthenticated endpoint the embedded widget script calls for
 * every visitor message - NOT the Python API directly anymore. This is
 * what gives widget conversations the same persistence + real-time
 * delivery + human-handoff surface your WhatsApp conversations already
 * have, by reusing the exact same Conversation/Message/NewMessage
 * pipeline MetaWebhookController uses.
 *
 * CORS for this is scoped in config/cors.php to just api/widget/* -
 * intentionally open, since this must be callable from any third-party
 * site that embeds the widget script.
 */
class WidgetMessageController extends Controller
{
    /**
     * Find (never create) this visitor's most recent conversation for a
     * widget, or null if they've never messaged before. Shared by
     * send(), history(), and close() so all three agree on exactly which
     * conversation a (token, session_id) pair refers to.
     */
        private function checkCompany(string $token)
    {
        // Compare as plain dates, not against a full timestamp: expiry_date
        // is a DATE column, so MySQL reads it as midnight on that day. With
        // "now()" (a full timestamp) on the other side, a widget set to
        // expire on the 25th would actually go dark at 12:00 AM on the
        // 25th instead of staying valid through that whole day - and the
        // same off-by-a-few-hours risk applies to valid_from too. This
        // mirrors how the Python side compares its own dates (see
        // WidgetConfig.is_currently_active) so both enforcement points
        // agree on the same day boundary.
        $today = now()->toDateString();
        return \App\Models\Company::where('widget_token', $token)
            ->where('is_active', true)
            ->where(function ($q) use ($today) {
                $q->whereNull('valid_from')->orWhereDate('valid_from', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('expiry_date')->orWhereDate('expiry_date', '>=', $today);
            })
            ->first();
    }

    private function findConversation(string $token, string $sessionId): ?Conversation
    {
        $syntheticId = 'web:' . $token . ':' . $sessionId;
        $contact = Contact::where('phone_number', $syntheticId)->first();
        if (!$contact) {
            return null;
        }
        return Conversation::where('contact_id', $contact->id)
            ->where('channel', 'web_widget')
            ->where('widget_token', $token)
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * Called once when the widget opens, BEFORE showing the welcome
     * message - restores prior messages for this visitor's session so
     * reopening the widget (or a page reload) doesn't look like the
     * conversation vanished, even though it was always there server-side.
     */
    public function history(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'session_id' => 'required|string',
        ]);

        if (!$this->checkCompany($validated['token'])) {
            return response()->json(['error' => 'Unknown or inactive widget.'], 404);
        }

        $conversation = $this->findConversation($validated['token'], $validated['session_id']);

        if (!$conversation || $conversation->status === 'CLOSED') {
            // Nothing to restore, or the last conversation was formally
            // closed - the widget should start fresh (welcome message),
            // same as a first-ever visit.
            return response()->json(['conversation_id' => null, 'status' => null, 'messages' => []]);
        }

        $assignedEmployee = $conversation->assigned_tenant_user_id
            ? Employee::find($conversation->assigned_tenant_user_id)
            : null;

        $messages = Message::where('conversation_id', $conversation->id)
            ->orderBy('id', 'asc')
            ->get(['direction', 'sender_type', 'message_text', 'sent_at', 'media_url', 'file_name'])
            ->map(fn ($m) => [
                'from' => $m->direction === 'INBOUND' ? 'user' : 'bot',
                'text' => $m->message_text,
                'sent_at' => $m->sent_at,
                'media_url' => $m->media_url,
                'file_name' => $m->file_name,
                // Only meaningful for past EMPLOYEE messages - lets the
                // widget label "Sapna Das" on old messages the same way
                // it does for ones arriving live, when history is
                // restored on reopen.
                'sender_name' => $m->sender_type === 'EMPLOYEE' ? $assignedEmployee?->display_name : null,
            ]);

        return response()->json([
            'conversation_id' => $conversation->id,
            'status' => $conversation->status,
            'human_assigned' => (bool) $conversation->assigned_tenant_user_id,
            'assigned_employee_name' => $assignedEmployee?->display_name,
            'messages' => $messages,
        ]);
    }

    /**
     * Visitor-initiated "end chat" - the other half of "closed from both
     * ends": an agent can already resolve a conversation from the CRM
     * (ConversationController::updateStatus); this lets the visitor do
     * the same from their side. The NEXT message from this same
     * session_id will start a brand new conversation (see send()'s
     * CLOSED check), same as if the agent had resolved it.
     */
    public function close(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'session_id' => 'required|string',
        ]);

        if (!$this->checkCompany($validated['token'])) {
            return response()->json(['error' => 'Unknown or inactive widget.'], 404);
        }

        $conversation = $this->findConversation($validated['token'], $validated['session_id']);

        if ($conversation) {
            $conversation->update(['status' => 'CLOSED', 'resolved_at' => now()]);
        }

        return response()->json(['success' => true]);
    }

    public function send(Request $request, WidgetApiService $api)
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'session_id' => 'required|string',
            'message' => 'nullable|string',
            'attachment' => 'nullable|file|max:10240',
        ]);

        if (empty($validated['message']) && !$request->hasFile('attachment')) {
            return response()->json(['error' => 'Message or attachment is required.'], 400);
        }

        $company = $this->checkCompany($validated['token']);

        if (!$company) {
            return response()->json(['error' => 'Unknown or inactive widget.'], 404);
        }

        // One Contact per (widget, visitor) pair - phone_number has no
        // real meaning for a website visitor, so we synthesize a stable
        // id from the token + their client-generated session_id, the
        // same way MetaWebhookController looks contacts up by phone_number.
        $syntheticId = 'web:' . $validated['token'] . ':' . $validated['session_id'];
        $contact = Contact::firstOrCreate(
            ['phone_number' => $syntheticId],
            [
                'tenant_id' => $company->id,
                'custom_name' => 'Website visitor',
            ]
        );

        // One Conversation per (widget, visitor) - reopens if it was
        // resolved, exactly like MetaWebhookController's WhatsApp logic.
        if (!$this->checkCompany($validated['token'])) {
            return response()->json(['error' => 'Unknown or inactive widget.'], 404);
        }

        $conversation = $this->findConversation($validated['token'], $validated['session_id']);

        if (!$conversation || $conversation->status === 'CLOSED') {
            $conversation = Conversation::create([
                'contact_id' => $contact->id,
                'tenant_id' => $company->id,
                'channel' => 'web_widget',
                'widget_token' => $validated['token'],
                'whatsapp_phone_number_id' => null,
                'title' => $company->name . ' - Website Visitor',
                'status' => 'OPEN',
                'unread_count' => 0,
                'first_message_at' => now(),
            ]);
        } elseif ($conversation->status === 'RESOLVED') {
            $conversation->update(['status' => 'OPEN']);
        }

        $mediaUrl = null;
        $fileName = null;
        $messageType = 'TEXT';
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $fileName = $file->getClientOriginalName();
            $path = $file->store('widget_attachments', 'public');
            $mediaUrl = asset('storage/' . $path);
            $mime = $file->getMimeType();
            $messageType = str_starts_with($mime, 'image/') ? 'IMAGE' : 'DOCUMENT';
        }

        // 1. Save + broadcast the visitor's own message.
        $inbound = Message::create([
            'tenant_id' => $company->id,
            'channel' => 'web_widget',
            'conversation_id' => $conversation->id,
            'contact_id' => $contact->id,
            'whatsapp_phone_number_id' => null,
            'meta_message_id' => 'web_' . (string) Str::uuid(),
            'message_type' => $messageType,
            'direction' => 'INBOUND',
            'sender_type' => 'CUSTOMER',
            'message_text' => $validated['message'] ?? '',
            'media_url' => $mediaUrl,
            'file_name' => $fileName,
            'status' => 'RECEIVED',
            'sent_at' => now(),
        ]);

        $msgPreview = $fileName ? 'Attachment: ' . $fileName : Str::limit($validated['message'] ?? '', 50);

        $conversation->update([
            'unread_count' => $conversation->unread_count + 1,
            'last_message_at' => now(),
            'last_message_id' => $inbound->id,
            'last_message_preview' => $msgPreview,
        ]);

        event(new NewMessage($inbound));

        // 2. If a human agent has already taken this conversation over
        // (see ConversationController::assign), don't let the bot
        // auto-answer - just save the visitor's message and let the
        // agent reply manually from the CRM. This is what was missing
        // before: previously the bot kept answering every message even
        // after a human had started handling the conversation.
        if ($conversation->assigned_tenant_user_id) {
            return response()->json([
                'reply' => null,
                'escalated' => false,
                'options' => null,
                'conversation_id' => $conversation->id,
                'human_assigned' => true,
            ]);
        }

        // 3. Ask the bot for an answer.
        $messageForBot = $validated['message'] ?? '[Attachment]';
        $botResponse = $api->sendMessage($validated['token'], $validated['session_id'], $messageForBot);
        $replyText = $botResponse['reply'] ?? "Sorry, I'm having trouble right now - please try again in a moment.";
        $escalated = $botResponse['escalated'] ?? false;
        $options = $botResponse['options'] ?? null;

        // 4. Save + broadcast the bot's reply.
        $outbound = Message::create([
            'tenant_id' => $company->id,
            'channel' => 'web_widget',
            'conversation_id' => $conversation->id,
            'contact_id' => $contact->id,
            'whatsapp_phone_number_id' => null,
            'meta_message_id' => 'web_' . (string) Str::uuid(),
            'message_type' => 'TEXT',
            'direction' => 'OUTBOUND',
            'sender_type' => $escalated ? 'SYSTEM' : 'BOT',
            'message_text' => $replyText,
            'status' => 'SENT',
            'sent_at' => now(),
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'last_message_id' => $outbound->id,
            'last_message_preview' => Str::limit($replyText, 50),
        ]);

        // Escalated (bot couldn't answer) - flag it for a human. A later
        // stage adds proper company-scoped agent assignment; for now
        // this just makes it findable/filterable in the existing Inbox.
        if ($escalated) {
            $conversation->update(['status' => 'PENDING']);
        }

        event(new NewMessage($outbound));

        return response()->json([
            'reply' => $replyText,
            'escalated' => $escalated,
            'options' => $options,
            'conversation_id' => $conversation->id,
        ]);
    }

    /**
     * Called when a visitor taps one of the option buttons shown after
     * an ambiguous match (see send() above) - saves both the tapped
     * question and the bot's exact answer as real Messages, the same as
     * any other exchange. Previously the widget called Python's
     * /api/web/select directly for this, bypassing Laravel entirely -
     * meaning a clicked option's question and answer were never saved
     * at all, invisible in the CRM's conversation history even though
     * everything else in the conversation showed up correctly.
     */
    public function select(Request $request, WidgetApiService $api)
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'session_id' => 'required|string',
            'source_id' => 'required|string',
            'title' => 'required|string',
        ]);

        if (!$this->checkCompany($validated['token'])) {
            return response()->json(['error' => 'Unknown or inactive widget.'], 404);
        }

        $conversation = $this->findConversation($validated['token'], $validated['session_id']);

        if (!$conversation) {
            return response()->json(['error' => 'No conversation found for this session.'], 404);
        }

        // 1. Save + broadcast the tapped option as the visitor's message
        // - the option's title IS what the visitor is effectively
        // "saying" by tapping it, same as if they'd typed the question.
        $inbound = Message::create([
            'tenant_id' => $conversation->tenant_id,
            'channel' => 'web_widget',
            'conversation_id' => $conversation->id,
            'contact_id' => $conversation->contact_id,
            'whatsapp_phone_number_id' => null,
            'meta_message_id' => 'web_' . (string) Str::uuid(),
            'message_type' => 'TEXT',
            'direction' => 'INBOUND',
            'sender_type' => 'CUSTOMER',
            'message_text' => $validated['title'],
            'status' => 'RECEIVED',
            'sent_at' => now(),
        ]);
        event(new NewMessage($inbound));

        // 2. Same bot-pause rule as send() - a human already handling
        // this conversation shouldn't have the bot jump in just because
        // an old option list is still on screen and got tapped.
        if ($conversation->assigned_tenant_user_id) {
            $conversation->update([
                'unread_count' => $conversation->unread_count + 1,
                'last_message_at' => now(),
                'last_message_id' => $inbound->id,
                'last_message_preview' => Str::limit($validated['title'], 50),
            ]);
            return response()->json(['reply' => null, 'human_assigned' => true, 'conversation_id' => $conversation->id]);
        }

        // 3. Get that exact FAQ's answer and save + broadcast it too.
        $botResponse = $api->selectOption($validated['token'], $validated['source_id']);
        $replyText = $botResponse['reply'] ?? "Sorry, I'm having trouble right now - please try again in a moment.";

        $outbound = Message::create([
            'tenant_id' => $conversation->tenant_id,
            'channel' => 'web_widget',
            'conversation_id' => $conversation->id,
            'contact_id' => $conversation->contact_id,
            'whatsapp_phone_number_id' => null,
            'meta_message_id' => 'web_' . (string) Str::uuid(),
            'message_type' => 'TEXT',
            'direction' => 'OUTBOUND',
            'sender_type' => 'BOT',
            'message_text' => $replyText,
            'status' => 'SENT',
            'sent_at' => now(),
        ]);

        $conversation->update([
            'unread_count' => $conversation->unread_count + 1,
            'last_message_at' => now(),
            'last_message_id' => $outbound->id,
            'last_message_preview' => Str::limit($replyText, 50),
        ]);

        event(new NewMessage($outbound));

        return response()->json([
            'reply' => $replyText,
            'conversation_id' => $conversation->id,
        ]);
    }
}
