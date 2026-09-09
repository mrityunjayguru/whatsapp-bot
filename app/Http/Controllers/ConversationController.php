<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function index()
    {
        $conversations = \App\Models\Conversation::with(['contact', 'assignedUser'])->latest('last_message_at')->paginate(10);
        return view('conversations.index', compact('conversations'));
    }

    public function show(\App\Models\Conversation $conversation)
    {
        $allTags = \App\Models\Tag::all();
        $allContacts = \App\Models\Contact::all();
        return view('conversations.show', compact('conversation', 'allTags', 'allContacts'));
    }

    public function sendMessage(Request $request, \App\Models\Conversation $conversation)
    {
        $request->validate([
            'message_text' => 'nullable|string',
            'files' => 'nullable|array',
            'files.*' => 'file|max:10240',
        ]);

        $contact = $conversation->contact;
        $text = $request->input('message_text');
        $files = $request->file('files') ?? [];

        if (empty($text) && empty($files)) {
            return response()->json(['error' => 'Message text or files required.'], 400);
        }

        if (!$contact || !$contact->phone_number) {
            return response()->json(['error' => 'Contact does not have a phone number.'], 400);
        }

        try {
            $sentMessageIds = app(\App\Services\WhatsAppService::class)->sendMultipartMessage($contact->phone_number, $text, $files);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to send message: ' . $e->getMessage()], 500);
        }

        $savedMessages = [];

        foreach ($sentMessageIds as $sentMsg) {
            $type = $sentMsg['type']; // 'TEXT', 'IMAGE', 'DOCUMENT', etc.
            $outboundMsgId = $sentMsg['id'];

            // Save Message to DB
            $message = \App\Models\Message::create([
                'tenant_id' => $conversation->tenant_id,
                'conversation_id' => $conversation->id,
                'contact_id' => $contact->id,
                'whatsapp_phone_number_id' => $conversation->whatsapp_phone_number_id,
                'meta_message_id' => $outboundMsgId,
                'message_type' => $type,
                'direction' => 'OUTBOUND',
                'sender_type' => 'EMPLOYEE',
                'message_text' => $type === 'TEXT' ? $text : "[$type Attachment sent]",
                'status' => 'SENT',
                'sent_at' => now(),
            ]);

            $savedMessages[] = $message;

            // Dispatch WebSocket Event for each
            event(new \App\Events\NewMessage($message));
        }

        if (!empty($savedMessages)) {
            $lastMsg = end($savedMessages);
            // Update Conversation
            $conversation->update([
                'last_message_at' => now(),
                'last_message_id' => $lastMsg->id,
                'last_message_preview' => \Illuminate\Support\Str::limit($lastMsg->message_text, 50),
            ]);
        }

        return response()->json([
            'success' => true,
            'messages' => $savedMessages
        ]);
    }
}
