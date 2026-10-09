<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ConversationController extends Controller
{
    /**
     * Resolve the WhatsAppService that can actually send as THIS
     * conversation's own registered number, instead of always falling
     * back to Track Route Pro's single default number from .env. Every
     * outbound send below (plain replies, attachments, status/assign/
     * handoff notices) previously called `app(WhatsAppService::class)`
     * directly, which only ever uses that one default number's
     * credentials - fine for TRP's own number, but wrong (and silently
     * so, since Meta still returns a response - typically a permission
     * error, "Recipient phone number not in allowed list" in sandbox
     * mode, or the message is simply sent from the wrong business
     * identity) for every OTHER company's registered WhatsApp number.
     * Mirrors MetaWebhookController::handle()'s own
     * WhatsAppService::forNumber() lookup for inbound messages, so both
     * directions agree on which number a conversation belongs to.
     */
    private function whatsAppServiceFor(\App\Models\Conversation $conversation): \App\Services\WhatsAppService
    {
        if ($conversation->whatsapp_phone_number_id) {
            $number = \App\Models\WhatsappNumber::where('phone_number_id', $conversation->whatsapp_phone_number_id)->first();
            if ($number) {
                return \App\Services\WhatsAppService::forNumber($number);
            }
        }
        // No registered number on this conversation (e.g. it predates
        // multi-tenancy) - fall back to the single default number,
        // exactly as every call site did before this fix.
        return app(\App\Services\WhatsAppService::class);
    }

    public function index(Request $request)
    {
        $companyId = (auth()->user()->company_id ?? auth()->user()->tenant_id);
        $query = \App\Models\Conversation::where('tenant_id', $companyId)->with(['contact', 'assignedUser']);

        if (auth()->id() !== 1) {
            $employee = \App\Models\Employee::where('email', auth()->user()->email)->first();
            if ($employee && $employee->role !== 'ADMIN') {
                $query->where(function($q) use ($employee) {
                    $q->where('assigned_tenant_user_id', $employee->id)
                      ->orWhereJsonContains('assignment_history', ['employee_id' => $employee->id])
                      ->orWhereJsonContains('assignment_history', ['employee_id' => (string)$employee->id]);
                });
            }
            // If they don't have an employee record, they are the primary company owner (Admin), so they see all.
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_tenant_user_id', $request->assigned_to);
        }

        if ($request->filled('unread')) {
            $query->where('unread_count', '>', 0);
        }

        if ($request->filled('date')) {
            $query->whereDate('last_message_at', $request->date);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('contact', function($q) use ($search) {
                $q->where('phone_number', 'like', "%{$search}%")
                  ->orWhere('custom_name', 'like', "%{$search}%")
                  ->orWhere('whatsapp_profile_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('tags')) {
            $tags = (array) $request->tags;
            $query->whereHas('contact.tags', function($q) use ($tags) {
                $q->whereIn('tags.id', $tags);
            });
        }

        $conversations = $query->latest('last_message_at')->paginate(10)->withQueryString();
        
        $allUsers = \App\Models\User::where('company_id', $companyId)->get();
        $allEmployees = \App\Models\Employee::where('tenant_id', $companyId)->where('status', 'ACTIVE')->orderBy('display_name')->get();
        $allTags = \App\Models\Tag::where('tenant_id', $companyId)->get();

        return view('conversations.index', compact('conversations', 'allUsers', 'allEmployees', 'allTags'));
    }

    public function show($id)
    {
        $companyId = (auth()->user()->company_id ?? auth()->user()->tenant_id);
        $conversation = \App\Models\Conversation::where('tenant_id', $companyId)->findOrFail($id);

        $isEmployeeNotAssigned = false;
        if (auth()->id() !== 1) {
            $employee = \App\Models\Employee::where('email', auth()->user()->email)->first();
            if ($employee && $employee->role !== 'ADMIN') {
                $isCurrentlyAssigned = ($conversation->assigned_tenant_user_id === $employee->id);
                $isEmployeeNotAssigned = !$isCurrentlyAssigned;
                $hasHistory = false;
                $history = is_array($conversation->assignment_history) ? $conversation->assignment_history : json_decode($conversation->assignment_history, true) ?? [];
                foreach ($history as $record) {
                    if (isset($record['employee_id']) && $record['employee_id'] == $employee->id) {
                        $hasHistory = true;
                        break;
                    }
                }
                
                if (!$isCurrentlyAssigned && !$hasHistory) {
                    abort(403, 'Unauthorized access to this conversation.');
                }
            }
        }

        // Reset unread count when opening the conversation
        if ($conversation->unread_count > 0) {
            $conversation->update(['unread_count' => 0]);
        }

        $allTags = \App\Models\Tag::where('tenant_id', $companyId)->get();
        $allContacts = \App\Models\Contact::where('tenant_id', $companyId)->get();
        $allCountries = \App\Models\Country::orderBy('name')->get();
        
        $activeEmployeesQuery = \App\Models\Employee::where('tenant_id', $companyId)->where('status', 'ACTIVE');
        if (isset($employee) && $employee && $employee->role !== 'ADMIN') {
            $activeEmployeesQuery->where('id', $employee->id);
        }
        $activeEmployees = $activeEmployeesQuery->orderBy('display_name')->get();

        return view('conversations.show', compact('conversation', 'allTags', 'allContacts', 'activeEmployees', 'allCountries', 'isEmployeeNotAssigned'));
    }

    public function sendMessage(Request $request, $id)
    {
        $companyId = (auth()->user()->company_id ?? auth()->user()->tenant_id);
        $conversation = \App\Models\Conversation::where('tenant_id', $companyId)->findOrFail($id);

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

        // A website widget visitor has no WhatsApp phone number - the
        // conversation itself is the delivery channel (save + broadcast
        // the Message, the visitor's own browser is listening on this
        // conversation's Reverb channel and picks it up live, same
        // mechanism the CRM's own Inbox already uses). WhatsApp
        // conversations are unchanged below.
        if ($conversation->channel === 'web_widget') {
            $savedMessages = [];

            if (!empty($text)) {
                $message = \App\Models\Message::create([
                    'tenant_id' => $conversation->tenant_id,
                    'channel' => 'web_widget',
                    'conversation_id' => $conversation->id,
                    'contact_id' => $contact->id,
                    'whatsapp_phone_number_id' => null,
                    'meta_message_id' => 'web_' . (string) \Illuminate\Support\Str::uuid(),
                    'message_type' => 'TEXT',
                    'direction' => 'OUTBOUND',
                    'sender_type' => 'EMPLOYEE',
                    'message_text' => $text,
                    'status' => 'SENT',
                    'sent_at' => now(),
                ]);
                $savedMessages[] = $message;
                event(new \App\Events\NewMessage($message));
            }

            if (!empty($files)) {
                foreach ($files as $file) {
                    $path = $file->store('attachments', 'public');
                    $mediaUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($path);
                    $mimeType = $file->getClientMimeType();
                    $fileName = $file->getClientOriginalName();
                    
                    $messageType = 'DOCUMENT';
                    if (str_starts_with($mimeType, 'image/')) {
                        $messageType = 'IMAGE';
                    } elseif (str_starts_with($mimeType, 'video/')) {
                        $messageType = 'VIDEO';
                    } elseif (str_starts_with($mimeType, 'audio/')) {
                        $messageType = 'AUDIO';
                    }

                    $message = \App\Models\Message::create([
                        'tenant_id' => $conversation->tenant_id,
                        'channel' => 'web_widget',
                        'conversation_id' => $conversation->id,
                        'contact_id' => $contact->id,
                        'whatsapp_phone_number_id' => null,
                        'meta_message_id' => 'web_' . (string) \Illuminate\Support\Str::uuid(),
                        'message_type' => $messageType,
                        'direction' => 'OUTBOUND',
                        'sender_type' => 'EMPLOYEE',
                        'message_text' => '',
                        'media_url' => $mediaUrl,
                        'mime_type' => $mimeType,
                        'file_name' => $fileName,
                        'status' => 'SENT',
                        'sent_at' => now(),
                    ]);
                    $savedMessages[] = $message;
                    event(new \App\Events\NewMessage($message));
                }
            }

            if (!empty($savedMessages)) {
                $lastMsg = end($savedMessages);
                $preview = $lastMsg->message_text ?: ($lastMsg->file_name ?: 'Attachment');
                $conversation->update([
                    'last_message_at' => now(),
                    'last_message_id' => $lastMsg->id,
                    'last_message_preview' => \Illuminate\Support\Str::limit(strip_tags(html_entity_decode($preview)), 50),
                ]);
            }

            return response()->json(['success' => true, 'messages' => $savedMessages]);
        }

        if (!$contact || !$contact->phone_number) {
            return response()->json(['error' => 'Contact does not have a phone number.'], 400);
        }

        // WhatsApp has no separate UI to show who's replying (unlike the
        // widget, which labels the sender above the bubble) - the only way
        // the customer can tell a human answered, and which one, is if the
        // name is actually part of the message text itself.
        if (!empty($text) && $conversation->assigned_tenant_user_id) {
            $employee = \App\Models\Employee::find($conversation->assigned_tenant_user_id);
            if ($employee) {
                $text = "*{$employee->display_name}*: {$text}";
            }
        }

        try {
            $sentMessageIds = $this->whatsAppServiceFor($conversation)->sendMultipartMessage($contact->phone_number, $text, $files);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to send message: ' . $e->getMessage()], 500);
        }

        if (empty($sentMessageIds)) {
            return response()->json(['error' => 'Failed to send message to Meta API. Token may be expired or invalid.'], 500);
        }

        $savedMessages = [];

        foreach ($sentMessageIds as $sentMsg) {
            $type = $sentMsg['type']; // 'TEXT', 'IMAGE', 'DOCUMENT', etc.
            $outboundMsgId = $sentMsg['id'];
            
            $mediaUrl = null;
            $mimeType = null;
            $fileName = null;

            if (isset($sentMsg['file'])) {
                $file = $sentMsg['file'];
                $path = $file->store('attachments', 'public');
                $mediaUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($path);
                $mimeType = $file->getClientMimeType();
                $fileName = $file->getClientOriginalName();
            }

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
                'message_text' => $type === 'TEXT' ? $text : '',
                'media_url' => $mediaUrl,
                'mime_type' => $mimeType,
                'file_name' => $fileName,
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
                'last_message_preview' => \Illuminate\Support\Str::limit(strip_tags(html_entity_decode($lastMsg->message_text)), 50),
            ]);
        }

        return response()->json([
            'success' => true,
            'messages' => $savedMessages
        ]);
    }

    public function updateDetails(Request $request, $id)
    {
        $companyId = (auth()->user()->company_id ?? auth()->user()->tenant_id);
        $conversation = \App\Models\Conversation::where('tenant_id', $companyId)->findOrFail($id);

        $oldStatus = $conversation->status;
        $previousEmployeeId = $conversation->assigned_tenant_user_id;

        if ($request->has('status') && $oldStatus !== $request->status) {
            $this->updateStatus($request, $id);
        }

        if ($request->has('employee_id') && $previousEmployeeId != $request->employee_id) {
            $this->assign($request, $id);
        }

        return back()->with('success', 'Conversation details updated successfully.');
    }

    public function updateStatus(Request $request, $id)
    {
        $companyId = (auth()->user()->company_id ?? auth()->user()->tenant_id);
        $conversation = \App\Models\Conversation::where('tenant_id', $companyId)->findOrFail($id);

        $request->validate([
            'status' => 'required|in:OPEN,PENDING,RESOLVED,CLOSED'
        ]);

        $conversation->update([
            'status' => $request->status,
            'resolved_at' => in_array($request->status, ['RESOLVED', 'CLOSED']) ? now() : null,
        ]);

        if ($request->status === 'CLOSED') {
            $msgText = "Ended conversation";
            $contact = $conversation->contact;
            
            if ($conversation->channel === 'web_widget') {
                $message = \App\Models\Message::create([
                    'tenant_id' => $conversation->tenant_id,
                    'channel' => 'web_widget',
                    'conversation_id' => $conversation->id,
                    'contact_id' => $contact->id,
                    'whatsapp_phone_number_id' => null,
                    'meta_message_id' => 'web_' . (string) \Illuminate\Support\Str::uuid(),
                    'message_type' => 'TEXT',
                    'direction' => 'OUTBOUND',
                    'sender_type' => 'SYSTEM',
                    'message_text' => $msgText,
                    'status' => 'SENT',
                    'sent_at' => now(),
                ]);
                event(new \App\Events\NewMessage($message));
            } else {
                try {
                    $whatsappService = $this->whatsAppServiceFor($conversation);
                    $whatsappResponse = $whatsappService->sendMessage($contact->phone_number, $msgText);
                    if (!empty($whatsappResponse) && isset($whatsappResponse['messages'][0]['id'])) {
                        $message = \App\Models\Message::create([
                            'tenant_id' => $conversation->tenant_id,
                            'conversation_id' => $conversation->id,
                            'contact_id' => $contact->id,
                            'whatsapp_phone_number_id' => $conversation->whatsapp_phone_number_id,
                            'meta_message_id' => $whatsappResponse['messages'][0]['id'],
                            'message_type' => 'TEXT',
                            'direction' => 'OUTBOUND',
                            'sender_type' => 'SYSTEM',
                            'message_text' => $msgText,
                            'status' => 'SENT',
                            'sent_at' => now(),
                        ]);
                        event(new \App\Events\NewMessage($message));
                    }
                } catch (\Exception $e) {
                    \Log::error('Failed to send status update message: ' . $e->getMessage());
                }
            }
        }

        return back()->with('success', 'Conversation status updated to ' . $request->status);
    }

    public function assign(Request $request, $id)
    {
        $companyId = (auth()->user()->company_id ?? auth()->user()->tenant_id);
        $conversation = \App\Models\Conversation::where('tenant_id', $companyId)->findOrFail($id);

        $request->validate([
            'employee_id' => 'nullable|exists:employees,id',
        ]);

        $previousEmployeeId = $conversation->assigned_tenant_user_id;
        $newEmployeeId = $request->employee_id ?: null;

        $history = is_array($conversation->assignment_history) ? $conversation->assignment_history : json_decode($conversation->assignment_history, true) ?? [];
        
        if ($previousEmployeeId && $previousEmployeeId != $newEmployeeId) {
            foreach ($history as &$record) {
                if ($record['employee_id'] == $previousEmployeeId && empty($record['unassigned_at'])) {
                    $record['unassigned_at'] = now()->toIso8601String();
                }
            }
        }
        
        if ($newEmployeeId && $newEmployeeId != $previousEmployeeId) {
            $history[] = [
                'employee_id' => $newEmployeeId,
                'assigned_at' => now()->toIso8601String(),
                'unassigned_at' => null,
            ];
        }

        $updateData = [
            'assigned_tenant_user_id' => $newEmployeeId,
            'assignment_history' => $history,
        ];
        
        if ($newEmployeeId) {
            $updateData['bot_stopped'] = true;
        } else {
            $updateData['bot_stopped'] = false;
        }

        $conversation->update($updateData);

        // Keep each Employee's own counters roughly in sync - these
        // columns already exist on the employees table
        // (assigned_conversation_count / resolved_conversation_count)
        // but nothing was ever incrementing them.
        if ($previousEmployeeId && $previousEmployeeId != $newEmployeeId) {
            \App\Models\Employee::where('id', $previousEmployeeId)->decrement('assigned_conversation_count');
        }
        if ($newEmployeeId && $newEmployeeId != $previousEmployeeId) {
            \App\Models\Employee::where('id', $newEmployeeId)->increment('assigned_conversation_count');
        }

        // Send a message indicating assignment change
        // $handedBackToAI: true only for the unassign case - this is the
        // exact moment control genuinely returns to the bot (send()
        // above only auto-replies when assigned_tenant_user_id is
        // empty), so it's also the right moment to tell the visitor the
        // bot is answering again - see the second, follow-up message
        // sent below.
        $handedBackToAI = false;
        if ($newEmployeeId && $newEmployeeId != $previousEmployeeId) {
            $employee = \App\Models\Employee::find($newEmployeeId);
            $msgText = $employee->display_name . " joined conversation";
        } elseif (!$newEmployeeId && $previousEmployeeId) {
            $employee = \App\Models\Employee::find($previousEmployeeId);
            $msgText = "Ended conversation with " . $employee->display_name;
            $handedBackToAI = true;
        }

        if (isset($msgText)) {
            $contact = $conversation->contact;
            if ($conversation->channel === 'web_widget') {
                $message = \App\Models\Message::create([
                    'tenant_id' => $conversation->tenant_id,
                    'channel' => 'web_widget',
                    'conversation_id' => $conversation->id,
                    'contact_id' => $contact->id,
                    'whatsapp_phone_number_id' => null,
                    'meta_message_id' => 'web_' . (string) \Illuminate\Support\Str::uuid(),
                    'message_type' => 'TEXT',
                    'direction' => 'OUTBOUND',
                    'sender_type' => 'SYSTEM',
                    'message_text' => $msgText,
                    'status' => 'SENT',
                    'sent_at' => now(),
                ]);
                event(new \App\Events\NewMessage($message));

                // Widget-only, and only on hand-back (not on a human
                // joining) - lets the visitor know the bot, not a
                // person, is reading their next message. Deliberately
                // NOT sent on WhatsApp: every outbound WhatsApp message
                // is a billed conversation message, and this is purely
                // a widget UI cue, not something worth that cost there.
                if ($handedBackToAI) {
                    $resumedMessage = \App\Models\Message::create([
                        'tenant_id' => $conversation->tenant_id,
                        'channel' => 'web_widget',
                        'conversation_id' => $conversation->id,
                        'contact_id' => $contact->id,
                        'whatsapp_phone_number_id' => null,
                        'meta_message_id' => 'web_' . (string) \Illuminate\Support\Str::uuid(),
                        'message_type' => 'TEXT',
                        'direction' => 'OUTBOUND',
                        'sender_type' => 'SYSTEM',
                        'message_text' => 'AI Support is now assisting you',
                        'status' => 'SENT',
                        'sent_at' => now(),
                    ]);
                    event(new \App\Events\NewMessage($resumedMessage));
                }
            } else {
                try {
                    $whatsappService = $this->whatsAppServiceFor($conversation);
                    $whatsappResponse = $whatsappService->sendMessage($contact->phone_number, $msgText);
                    if (!empty($whatsappResponse) && isset($whatsappResponse['messages'][0]['id'])) {
                        $message = \App\Models\Message::create([
                            'tenant_id' => $conversation->tenant_id,
                            'conversation_id' => $conversation->id,
                            'contact_id' => $contact->id,
                            'whatsapp_phone_number_id' => $conversation->whatsapp_phone_number_id,
                            'meta_message_id' => $whatsappResponse['messages'][0]['id'],
                            'message_type' => 'TEXT',
                            'direction' => 'OUTBOUND',
                            'sender_type' => 'SYSTEM',
                            'message_text' => $msgText,
                            'status' => 'SENT',
                            'sent_at' => now(),
                        ]);
                        event(new \App\Events\NewMessage($message));
                    }
                } catch (\Exception $e) {
                    \Log::error('Failed to send assignment message: ' . $e->getMessage());
                }
            }
        }

        return back()->with('success', $newEmployeeId
            ? 'Conversation assigned - the bot will no longer auto-reply here.'
            : 'Conversation unassigned.');
    }

    public function toggleBot(Request $request, $id)
    {
        $companyId = (auth()->user()->company_id ?? auth()->user()->tenant_id);
        $conversation = \App\Models\Conversation::where('tenant_id', $companyId)->findOrFail($id);

        $botWasActive = !$conversation->bot_stopped;
        $willStopBot = !$conversation->bot_stopped; // True if it's currently active and will be stopped
        
        $updateData = ['bot_stopped' => $willStopBot];
        
        // If we are starting the bot manually, unassign the employee
        if (!$willStopBot && $conversation->assigned_tenant_user_id) {
            $updateData['assigned_tenant_user_id'] = null;
        }
        
        $conversation->update($updateData);

        if ($botWasActive && $conversation->bot_stopped) {
            $handoffMessage = "Admin joined conversation";
            $contact = $conversation->contact;

            if ($conversation->channel === 'web_widget') {
                $message = \App\Models\Message::create([
                    'tenant_id' => $conversation->tenant_id,
                    'channel' => 'web_widget',
                    'conversation_id' => $conversation->id,
                    'contact_id' => $contact->id,
                    'whatsapp_phone_number_id' => null,
                    'meta_message_id' => 'web_' . (string) \Illuminate\Support\Str::uuid(),
                    'message_type' => 'TEXT',
                    'direction' => 'OUTBOUND',
                    'sender_type' => 'SYSTEM',
                    'message_text' => $handoffMessage,
                    'status' => 'SENT',
                    'sent_at' => now(),
                ]);
                event(new \App\Events\NewMessage($message));
            } else {
                try {
                    $whatsappService = $this->whatsAppServiceFor($conversation);
                    $whatsappResponse = $whatsappService->sendMessage($contact->phone_number, $handoffMessage);
                    
                    if (!empty($whatsappResponse) && isset($whatsappResponse['messages'][0]['id'])) {
                        $message = \App\Models\Message::create([
                            'tenant_id' => $conversation->tenant_id,
                            'conversation_id' => $conversation->id,
                            'contact_id' => $contact->id,
                            'whatsapp_phone_number_id' => $conversation->whatsapp_phone_number_id,
                            'meta_message_id' => $whatsappResponse['messages'][0]['id'],
                            'message_type' => 'TEXT',
                            'direction' => 'OUTBOUND',
                            'sender_type' => 'SYSTEM',
                            'message_text' => $handoffMessage,
                            'status' => 'SENT',
                            'sent_at' => now(),
                        ]);
                        event(new \App\Events\NewMessage($message));
                    }
                } catch (\Exception $e) {
                    \Log::error('Failed to send handoff message: ' . $e->getMessage());
                }
            }
        }

        return back()->with('success', $conversation->bot_stopped ? 'Bot stopped for this conversation.' : 'Bot resumed for this conversation.');
    }
}
