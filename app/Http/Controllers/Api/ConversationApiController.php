<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ConversationApiController extends Controller
{
    /**
     * Get list of conversations with filter tabs, search, and unread counts.
     * Compatible with the mobile app / screen layout:
     * - Filters: status (all, active, new, closed, pending, resolved)
     * - Search: contact name, phone, or profile name
     * - Returns: conversations list + counts for tabs (all, active, new, closed)
     */
    #[
        OA\Get(
            path: "/conversations",
            summary: "Get Conversations List (Mobile Screen)",
            description: "Retrieve conversations with filter tabs (all, active, new, closed) and real-time counts.",
            security: [["bearerAuth" => []]],
            tags: ["Conversations"],
            parameters: [
                new OA\Parameter(name: "status", in: "query", required: false, description: "Filter by status: all, active, new, closed, open, pending, resolved", schema: new OA\Schema(type: "string")),
                new OA\Parameter(name: "search", in: "query", required: false, description: "Search by contact name, phone, or message", schema: new OA\Schema(type: "string")),
                new OA\Parameter(name: "unread_only", in: "query", required: false, description: "Filter only unread conversations", schema: new OA\Schema(type: "boolean")),
                new OA\Parameter(name: "page", in: "query", required: false, description: "Page number", schema: new OA\Schema(type: "integer")),
                new OA\Parameter(name: "per_page", in: "query", required: false, description: "Items per page", schema: new OA\Schema(type: "integer"))
            ]
        ),
        OA\Response(response: 200, description: "Conversations list with tabs count"),
        OA\Response(response: 401, description: "Unauthenticated")
    ]
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = $user->company_id;

        if (!$companyId) {
            return response()->json([
                'status'  => false,
                'message' => 'No company associated with this account.',
            ], 403);
        }

        // Base query scoped to the user's company (tenant)
        $baseQuery = Conversation::where('tenant_id', $companyId);

        // Check if the logged-in user is an Employee or Company Admin
        $employee = Employee::where('tenant_id', $companyId)
            ->where('email', $user->email)
            ->first();

        // 1. If Company logged in: see ALL conversations of that company.
        // 2. If Employee logged in: see only conversations currently ASSIGNED to this employee.
        if ($employee) {
            $baseQuery->where('assigned_tenant_user_id', $employee->id);
        }

        // Calculate count badges for tabs shown on screen:
        // "Active" = OPEN or PENDING
        // "New" = unread_count > 0 or unassigned & OPEN
        // "Closed" = CLOSED or RESOLVED
        $counts = [
            'all'    => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->whereIn('status', ['OPEN', 'PENDING'])->count(),
            'new'    => (clone $baseQuery)->where(function ($q) {
                $q->where('unread_count', '>', 0)
                  ->orWhere(function ($sub) {
                      $sub->whereNull('assigned_tenant_user_id')
                          ->where('status', 'OPEN');
                  });
            })->count(),
            'closed' => (clone $baseQuery)->whereIn('status', ['CLOSED', 'RESOLVED'])->count(),
            'total_unread' => (clone $baseQuery)->sum('unread_count'),
        ];

        // Apply filters to main query
        $query = (clone $baseQuery)->with(['contact', 'assignedUser']);

        // 1. Status Filter
        if ($request->filled('status')) {
            $statusFilter = strtolower(trim($request->status));
            switch ($statusFilter) {
                case 'active':
                    // Active means OPEN / ongoing conversations
                    $query->whereIn('status', ['OPEN', 'PENDING']);
                    break;
                case 'new':
                    // New/unassigned or unread conversations
                    $query->where(function ($q) {
                        $q->where('unread_count', '>', 0)
                          ->orWhere(function ($sub) {
                              $sub->whereNull('assigned_tenant_user_id')
                                  ->where('status', 'OPEN');
                          });
                    });
                    break;
                case 'closed':
                    $query->whereIn('status', ['CLOSED']);
                    break;
                case 'open':
                    $query->where('status', 'OPEN');
                    break;
                case 'pending':
                    $query->where('status', 'PENDING');
                    break;
                case 'resolved':
                    $query->where('status', 'RESOLVED');
                    break;
                case 'all':
                default:
                    // All conversations, no status restriction
                    break;
            }
        }

        // 2. Search Filter (matches contact name, phone, or profile name)
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereHas('contact', function ($contactQuery) use ($search) {
                    $contactQuery->where('phone_number', 'like', "%{$search}%")
                                 ->orWhere('custom_name', 'like', "%{$search}%")
                                 ->orWhere('whatsapp_profile_name', 'like', "%{$search}%");
                })->orWhere('last_message_preview', 'like', "%{$search}%");
            });
        }

        // 3. Unread only filter (optional query param: ?unread_only=1)
        if ($request->boolean('unread_only')) {
            $query->where('unread_count', '>', 0);
        }

        // Pagination (default 20)
        $perPage = (int) $request->input('per_page', 20);
        $conversations = $query->latest('last_message_at')->paginate($perPage);

        // Format data for mobile app / UI list
        $items = collect($conversations->items())->map(function ($conv) {
            $contact = $conv->contact;

            // Determine contact display name
            $name = $contact?->custom_name
                ?: $contact?->whatsapp_profile_name
                ?: $contact?->phone_number
                ?: 'Guest User';

            // Clean web-widget prefix if any
            if (str_starts_with($name, 'web:')) {
                $name = 'Web Visitor #' . substr($name, 4, 6);
            }

            // Human friendly formatted time (e.g. "12:42 PM", "Yesterday", "12 Oct")
            $timeFormatted = null;
            if ($conv->last_message_at) {
                $lastMsgDate = Carbon::parse($conv->last_message_at);
                if ($lastMsgDate->isToday()) {
                    $timeFormatted = $lastMsgDate->format('h:i A');
                } elseif ($lastMsgDate->isYesterday()) {
                    $timeFormatted = 'Yesterday';
                } else {
                    $timeFormatted = $lastMsgDate->format('d M');
                }
            }

            // Clean phone number: if it is empty or starts with web session token, return null
            $phoneNumber = $contact?->phone_number;
            if ($phoneNumber && str_starts_with($phoneNumber, 'web:')) {
                $phoneNumber = null;
            }

            return [
                'id'                   => $conv->id,
                'conversation_code'    => $conv->formatted_id,
                'channel'              => $conv->channel ?? 'whatsapp',
                'status'               => $conv->status,
                'is_active'            => in_array($conv->status, ['OPEN', 'PENDING']),
                'is_closed'            => in_array($conv->status, ['CLOSED', 'RESOLVED']),
                'unread_count'         => (int) ($conv->unread_count ?? 0),
                'last_message'         => $conv->last_message_preview,
                'last_message_at'      => $conv->last_message_at ? Carbon::parse($conv->last_message_at)->toIso8601String() : null,
                'time_formatted'       => $timeFormatted,
                'contact' => [
                    'id'           => $contact?->id,
                    'name'         => $name,
                    'phone_number' => $phoneNumber,
                    'email'        => $contact?->email,
                    'avatar_text'  => mb_strtoupper(mb_substr($name, 0, 1)),
                ],
                'assigned_to' => $conv->assignedUser ? [
                    'id'           => $conv->assignedUser->id,
                    'name'         => $conv->assignedUser->display_name ?? trim($conv->assignedUser->first_name . ' ' . $conv->assignedUser->last_name),
                    'role'         => $conv->assignedUser->role,
                ] : null,
            ];
        });

        return response()->json([
            'status'  => true,
            'message' => 'Conversations fetched successfully.',
            'tabs'    => $counts,
            'data'    => $items,
        ], 200);
    }

    /**
     * Get specific conversation details along with chat messages and options.
     * Endpoint: GET /api/conversations/{id}
     */
    #[
        OA\Get(
            path: "/conversations/{id}",
            summary: "Get Conversation Details & Messages",
            description: "Retrieve specific conversation details, contact info, assigned agent and list of messages.",
            security: [["bearerAuth" => []]],
            tags: ["Conversations"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, description: "Conversation ID", schema: new OA\Schema(type: "integer"))
            ]
        ),
        OA\Response(response: 200, description: "Conversation details and messages"),
        OA\Response(response: 403, description: "Unauthorized access"),
        OA\Response(response: 404, description: "Conversation not found")
    ]
    public function show(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $companyId = $user->company_id;

        $conversation = Conversation::where('tenant_id', $companyId)
            ->with(['contact.tags', 'assignedUser', 'assignedByUser'])
            ->find($id);

        if (!$conversation) {
            return response()->json([
                'status'  => false,
                'message' => 'Conversation not found.',
            ], 404);
        }

        // Check employee access restrictions
        $employee = Employee::where('tenant_id', $companyId)
            ->where('email', $user->email)
            ->first();

        if ($employee && $employee->role !== 'ADMIN') {
            $isAssigned = ($conversation->assigned_tenant_user_id === $employee->id);
            $history = is_array($conversation->assignment_history) ? $conversation->assignment_history : json_decode($conversation->assignment_history, true) ?? [];
            $hasHistory = false;
            foreach ($history as $h) {
                if (isset($h['employee_id']) && $h['employee_id'] == $employee->id) {
                    $hasHistory = true;
                    break;
                }
            }

            if (!$isAssigned && !$hasHistory) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Unauthorized access to this conversation.',
                ], 403);
            }
        }

        // Mark unread as 0 when conversation is opened
        if ($conversation->unread_count > 0) {
            $conversation->update(['unread_count' => 0]);
        }

        // Fetch messages
        $msgQuery = \App\Models\Message::where('conversation_id', $conversation->id)
            ->orderBy('sent_at', 'asc')
            ->orderBy('id', 'asc');

        // Scoping for employee view history
        if ($employee && $employee->role !== 'ADMIN' && $conversation->assigned_tenant_user_id !== $employee->id) {
            $history = is_array($conversation->assignment_history) ? $conversation->assignment_history : json_decode($conversation->assignment_history, true) ?? [];
            $maxUnassignedAt = null;
            foreach ($history as $record) {
                if (isset($record['employee_id']) && $record['employee_id'] == $employee->id && !empty($record['unassigned_at'])) {
                    if ($maxUnassignedAt === null || $record['unassigned_at'] > $maxUnassignedAt) {
                        $maxUnassignedAt = $record['unassigned_at'];
                    }
                }
            }
            if ($maxUnassignedAt) {
                $msgQuery->where('sent_at', '<=', Carbon::parse($maxUnassignedAt));
            }
        }

        $rawMessages = $msgQuery->get();

        $contact = $conversation->contact;
        $contactName = $contact?->custom_name
            ?: $contact?->whatsapp_profile_name
            ?: $contact?->phone_number
            ?: 'Website Visitor';

        if (str_starts_with($contactName, 'web:')) {
            $contactName = 'Website Visitor';
        }

        // Clean phone number (null if web widget)
        $phoneNumber = $contact?->phone_number;
        if ($phoneNumber && str_starts_with($phoneNumber, 'web:')) {
            $phoneNumber = null;
        }

        // Determine Assigned By Name
        $assignedByName = null;
        if ($conversation->assigned_by) {
            $assignedByUser = \App\Models\User::find($conversation->assigned_by);
            $assignedByName = $assignedByUser?->name;
        }

        // Fallback: Check assignment_history if assigned_by column was empty for older records
        if (!$assignedByName && !empty($conversation->assignment_history)) {
            $history = is_array($conversation->assignment_history) ? $conversation->assignment_history : json_decode($conversation->assignment_history, true) ?? [];
            $latest = end($history);
            if (isset($latest['assigned_by'])) {
                $u = \App\Models\User::find($latest['assigned_by']);
                $assignedByName = $u?->name;
            }
        }

        // If still null but has an assigned employee, fallback to Company Name or Admin
        if (!$assignedByName && $conversation->assigned_tenant_user_id) {
            $assignedByName = $conversation->company?->name ?? 'Admin';
        }

        // Format Messages
        $messages = $rawMessages->map(function ($msg) {
            $sentAt = $msg->sent_at ? Carbon::parse($msg->sent_at) : Carbon::parse($msg->created_at);
            $isEvent = \App\Models\Message::isConversationEvent($msg->sender_type, $msg->message_text);

            return [
                'id'           => $msg->id,
                'direction'    => $msg->direction, // INBOUND (received from customer) or OUTBOUND (sent by agent/system)
                'sender_type'  => $msg->sender_type, // CONTACT, EMPLOYEE, BOT, SYSTEM
                'message_type' => $msg->message_type, // TEXT, IMAGE, DOCUMENT, VIDEO, AUDIO
                'message_text' => $msg->message_text,
                'media_url'    => $msg->media_url,
                'file_name'    => $msg->file_name,
                'mime_type'    => $msg->mime_type,
                'status'       => $msg->status, // SENT, DELIVERED, READ, FAILED
                'is_event'     => $isEvent, // true for dividers like "AI Support is now assisting", "Admin joined conversation"
                'sent_at'      => $sentAt->toIso8601String(),
                'time'         => $sentAt->format('h:i A'),
                'date'         => $sentAt->format('Y-m-d'),
                'date_label'   => $sentAt->isToday() ? 'Today ' . $sentAt->format('d F') : ($sentAt->isYesterday() ? 'Yesterday' : $sentAt->format('d F Y')),
            ];
        });

        // Tags array
        $tags = collect($contact?->tags ?? [])->map(function ($tag) {
            return [
                'id'       => $tag->id,
                'name'     => $tag->tag_name,
                'color'    => $tag->color_code ?? '#6c757d',
            ];
        });

        // Determine title shown in details sheet (e.g. "DesignDemonz - Website Visitor")
        $title = $conversation->title;
        if (empty($title)) {
            $companyName = $conversation->company?->name ?? 'Company';
            $title = $companyName . ' - ' . $contactName;
        }

        return response()->json([
            'status' => true,
            'data'   => [
                'details' => [
                    'id'               => $conversation->id,
                    'conversation_no'  => $conversation->formatted_id, // e.g. "CONV-0008"
                    'title'            => $title,
                    'status'           => $conversation->status, // OPEN, PENDING, RESOLVED, CLOSED
                    'assigned_to'      => $conversation->assignedUser ? [
                        'id'           => $conversation->assignedUser->id,
                        'name'         => $conversation->assignedUser->display_name ?? trim($conversation->assignedUser->first_name . ' ' . $conversation->assignedUser->last_name),
                        'role'         => $conversation->assignedUser->role,
                    ] : null,
                    'assigned_by'      => $assignedByName, // e.g. "Anika Schleifer"
                    'created_on'       => $conversation->created_at ? Carbon::parse($conversation->created_at)->format('d M Y, h:i A') : null,
                    'last_activity'    => $conversation->last_message_at ? Carbon::parse($conversation->last_message_at)->format('d M Y, h:i A') : ($conversation->updated_at ? Carbon::parse($conversation->updated_at)->format('d M Y, h:i A') : null),
                    'contact' => [
                        'id'           => $contact?->id,
                        'name'         => $contactName,
                        'phone_number' => $phoneNumber,
                        'email'        => $contact?->email,
                        'avatar_text'  => mb_strtoupper(mb_substr($contactName, 0, 1)),
                        'tags'         => $tags,
                    ],
                ],
                'messages' => $messages,
            ],
        ], 200);
    }

    /**
     * Change Conversation Status.
     * Endpoint: POST /api/conversations/{id}/status
     * Body: { "status": "OPEN" | "PENDING" | "RESOLVED" | "CLOSED" }
     */
    #[
        OA\Post(
            path: "/conversations/{id}/status",
            summary: "Change Conversation Status",
            description: "Update the status of a conversation (OPEN, PENDING, RESOLVED, CLOSED).",
            security: [["bearerAuth" => []]],
            tags: ["Conversations"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, description: "Conversation ID", schema: new OA\Schema(type: "integer"))
            ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: ["status"],
                    properties: [
                        new OA\Property(property: "status", type: "string", enum: ["OPEN", "PENDING", "RESOLVED", "CLOSED"], example: "OPEN")
                    ]
                )
            )
        ),
        OA\Response(response: 200, description: "Status updated successfully"),
        OA\Response(response: 422, description: "Validation error")
    ]
    public function changeStatus(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $companyId = $user->company_id;

        $conversation = Conversation::where('tenant_id', $companyId)->find($id);

        if (!$conversation) {
            return response()->json([
                'status'  => false,
                'message' => 'Conversation not found.',
            ], 404);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'status' => 'required|string|in:OPEN,PENDING,RESOLVED,CLOSED,open,pending,resolved,closed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation error.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $newStatus = strtoupper($request->status);

        $conversation->update([
            'status'      => $newStatus,
            'resolved_at' => in_array($newStatus, ['RESOLVED', 'CLOSED']) ? now() : null,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Conversation status updated to ' . $newStatus,
            'data'    => [
                'id'     => $conversation->id,
                'status' => $conversation->status,
            ],
        ]);
    }

    /**
     * Send message in a conversation from Mobile App (Flutter).
     * Dispatches real-time WebSocket event via NewMessage event.
     * Endpoint: POST /api/conversations/{id}/messages
     */
    #[
        OA\Post(
            path: "/conversations/{id}/messages",
            summary: "Send Message (with optional attachments)",
            description: "Send text message or upload attachments. Automatically dispatches real-time WebSocket event and forwards to WhatsApp/Widget.",
            security: [["bearerAuth" => []]],
            tags: ["Conversations"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, description: "Conversation ID", schema: new OA\Schema(type: "integer"))
            ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\MediaType(
                    mediaType: "multipart/form-data",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "message_text", type: "string", example: "Hello, how can I assist you?"),
                            new OA\Property(property: "files[]", type: "array", items: new OA\Items(type: "string", format: "binary"), description: "Attachments (images, audio, video, documents)")
                        ]
                    )
                )
            )
        ),
        OA\Response(response: 200, description: "Message sent successfully"),
        OA\Response(response: 400, description: "Message text or files required"),
        OA\Response(response: 500, description: "Failed to send message")
    ]
    public function sendMessage(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $companyId = $user->company_id;

        $conversation = Conversation::where('tenant_id', $companyId)->find($id);

        if (!$conversation) {
            return response()->json([
                'status'  => false,
                'message' => 'Conversation not found.',
            ], 404);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'message_text' => 'nullable|string',
            'files'        => 'nullable|array',
            'files.*'      => 'file|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation error.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $text = $request->input('message_text');
        $files = $request->file('files') ?? [];

        if (empty($text) && empty($files)) {
            return response()->json([
                'status'  => false,
                'message' => 'Message text or files required.',
            ], 400);
        }

        $contact = $conversation->contact;

        // Determine employee/sender display name
        $employee = Employee::where('tenant_id', $companyId)->where('email', $user->email)->first();
        $senderName = $employee ? ($employee->display_name ?? trim($employee->first_name . ' ' . $employee->last_name)) : $user->name;

        $savedMessages = [];

        // 1. Web Widget Channel Message Handling
        if ($conversation->channel === 'web_widget') {
            if (!empty($text)) {
                $message = \App\Models\Message::create([
                    'tenant_id'                => $conversation->tenant_id,
                    'channel'                  => 'web_widget',
                    'conversation_id'          => $conversation->id,
                    'contact_id'               => $contact?->id,
                    'whatsapp_phone_number_id' => null,
                    'meta_message_id'          => 'web_' . (string) \Illuminate\Support\Str::uuid(),
                    'message_type'             => 'TEXT',
                    'direction'                => 'OUTBOUND',
                    'sender_type'              => 'EMPLOYEE',
                    'message_text'             => $text,
                    'status'                   => 'SENT',
                    'sent_at'                  => now(),
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
                        'tenant_id'                => $conversation->tenant_id,
                        'channel'                  => 'web_widget',
                        'conversation_id'          => $conversation->id,
                        'contact_id'               => $contact?->id,
                        'whatsapp_phone_number_id' => null,
                        'meta_message_id'          => 'web_' . (string) \Illuminate\Support\Str::uuid(),
                        'message_type'             => $messageType,
                        'direction'                => 'OUTBOUND',
                        'sender_type'              => 'EMPLOYEE',
                        'message_text'             => '',
                        'media_url'                => $mediaUrl,
                        'mime_type'                => $mimeType,
                        'file_name'                => $fileName,
                        'status'                   => 'SENT',
                        'sent_at'                  => now(),
                    ]);

                    $savedMessages[] = $message;
                    event(new \App\Events\NewMessage($message));
                }
            }
        } else {
            // 2. WhatsApp Channel Message Handling
            if (!$contact || !$contact->phone_number) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Contact does not have a valid WhatsApp phone number.',
                ], 400);
            }

            $outboundText = $text;
            if (!empty($outboundText)) {
                $outboundText = "*{$senderName}*: {$outboundText}";
            }

            // Send via WhatsApp Service
            $whatsAppService = $this->whatsAppServiceFor($conversation);

            try {
                $sentMessageIds = $whatsAppService->sendMultipartMessage($contact->phone_number, $outboundText, $files);
            } catch (\Exception $e) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Failed to send WhatsApp message: ' . $e->getMessage(),
                ], 500);
            }

            if (empty($sentMessageIds)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Failed to send message to Meta WhatsApp API.',
                ], 500);
            }

            foreach ($sentMessageIds as $sentMsg) {
                $type = $sentMsg['type'];
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

                $message = \App\Models\Message::create([
                    'tenant_id'                => $conversation->tenant_id,
                    'channel'                  => 'whatsapp',
                    'conversation_id'          => $conversation->id,
                    'contact_id'               => $contact->id,
                    'whatsapp_phone_number_id' => $conversation->whatsapp_phone_number_id,
                    'meta_message_id'          => $outboundMsgId,
                    'message_type'             => $type,
                    'direction'                => 'OUTBOUND',
                    'sender_type'              => 'EMPLOYEE',
                    'message_text'             => $type === 'TEXT' ? $text : '',
                    'media_url'                => $mediaUrl,
                    'mime_type'                => $mimeType,
                    'file_name'                => $fileName,
                    'status'                   => 'SENT',
                    'sent_at'                  => now(),
                ]);

                $savedMessages[] = $message;
                event(new \App\Events\NewMessage($message));
            }
        }

        // Update Conversation metadata
        if (!empty($savedMessages)) {
            $lastMsg = end($savedMessages);
            $preview = $lastMsg->message_text ?: ($lastMsg->file_name ?: 'Attachment');

            $conversation->update([
                'last_message_at'      => now(),
                'last_message_id'      => $lastMsg->id,
                'last_message_preview' => \Illuminate\Support\Str::limit(strip_tags(html_entity_decode($preview)), 50),
            ]);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Message sent successfully.',
            'data'    => collect($savedMessages)->map(function ($msg) {
                $sentAt = Carbon::parse($msg->sent_at ?? now());
                return [
                    'id'           => $msg->id,
                    'direction'    => $msg->direction,
                    'sender_type'  => $msg->sender_type,
                    'message_type' => $msg->message_type,
                    'message_text' => $msg->message_text,
                    'media_url'    => $msg->media_url,
                    'file_name'    => $msg->file_name,
                    'status'       => $msg->status,
                    'sent_at'      => $sentAt->toIso8601String(),
                    'time'         => $sentAt->format('h:i A'),
                ];
            }),
        ], 200);
    }

    /**
     * Resolve WhatsAppService for this specific conversation's registered number.
     */
    private function whatsAppServiceFor(Conversation $conversation): \App\Services\WhatsAppService
    {
        if ($conversation->whatsapp_phone_number_id) {
            $number = \App\Models\WhatsappNumber::where('phone_number_id', $conversation->whatsapp_phone_number_id)->first();
            if ($number) {
                return \App\Services\WhatsAppService::forNumber($number);
            }
        }
        return app(\App\Services\WhatsAppService::class);
    }
}


