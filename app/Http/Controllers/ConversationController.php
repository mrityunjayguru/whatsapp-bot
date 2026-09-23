<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $query = \App\Models\Conversation::where('tenant_id', $companyId)->with(['contact', 'assignedUser']);

        if (auth()->id() !== 1) {
            $employee = \App\Models\Employee::where('email', auth()->user()->email)->first();
            // If they are an employee but NOT an admin, restrict their view
            if ($employee && $employee->role !== 'ADMIN') {
                $query->where('assigned_tenant_user_id', $employee->id);
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
        $companyId = auth()->user()->company_id;
        $conversation = \App\Models\Conversation::where('tenant_id', $companyId)->findOrFail($id);

        // Reset unread count when opening the conversation
        if ($conversation->unread_count > 0) {
            $conversation->update(['unread_count' => 0]);
        }

        $allTags = \App\Models\Tag::where('tenant_id', $companyId)->get();
        $allContacts = \App\Models\Contact::where('tenant_id', $companyId)->get();
        $allCountries = \App\Models\Country::orderBy('name')->get();
        $activeEmployees = \App\Models\Employee::where('tenant_id', $companyId)->where('status', 'ACTIVE')->orderBy('display_name')->get();
        return view('conversations.show', compact('conversation', 'allTags', 'allContacts', 'activeEmployees', 'allCountries'));
    }

    public function sendMessage(Request $request, $id)
    {
        $companyId = auth()->user()->company_id;
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
                    $mediaUrl = \Illuminate\Support\Facades\Storage::url($path);
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
                    'last_message_preview' => \Illuminate\Support\Str::limit($preview, 50),
                ]);
            }

            return response()->json(['success' => true, 'messages' => $savedMessages]);
        }

        if (!$contact || !$contact->phone_number) {
            return response()->json(['error' => 'Contact does not have a phone number.'], 400);
        }

        try {
            $sentMessageIds = app(\App\Services\WhatsAppService::class)->sendMultipartMessage($contact->phone_number, $text, $files);
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
                $mediaUrl = \Illuminate\Support\Facades\Storage::url($path);
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
                'last_message_preview' => \Illuminate\Support\Str::limit($lastMsg->message_text, 50),
            ]);
        }

        return response()->json([
            'success' => true,
            'messages' => $savedMessages
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $companyId = auth()->user()->company_id;
        $conversation = \App\Models\Conversation::where('tenant_id', $companyId)->findOrFail($id);

        $request->validate([
            'status' => 'required|in:OPEN,PENDING,RESOLVED,CLOSED'
        ]);

        $conversation->update([
            'status' => $request->status,
            'resolved_at' => in_array($request->status, ['RESOLVED', 'CLOSED']) ? now() : null,
        ]);

        return back()->with('success', 'Conversation status updated to ' . $request->status);
    }

    public function assign(Request $request, $id)
    {
        $companyId = auth()->user()->company_id;
        $conversation = \App\Models\Conversation::where('tenant_id', $companyId)->findOrFail($id);

        $request->validate([
            'employee_id' => 'nullable|exists:employees,id',
        ]);

        $previousEmployeeId = $conversation->assigned_tenant_user_id;
        $newEmployeeId = $request->employee_id ?: null;

        $conversation->update(['assigned_tenant_user_id' => $newEmployeeId]);

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

        return back()->with('success', $newEmployeeId
            ? 'Conversation assigned - the bot will no longer auto-reply here.'
            : 'Conversation unassigned - handed back to the bot.');
    }
}
