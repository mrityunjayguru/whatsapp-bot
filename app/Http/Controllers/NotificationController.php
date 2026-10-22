<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Employee;
use App\Models\Message;
use Carbon\Carbon;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Scope base query according to role & tenant
     */
    protected function applyUserScope($query)
    {
        $companyId = auth()->user()->company_id ?? auth()->user()->tenant_id;
        if (!is_null($companyId)) {
            $query->where('tenant_id', $companyId);
        }

        if (auth()->id() !== 1) {
            $employee = Employee::where('email', auth()->user()->email)->first();
            if ($employee && $employee->role !== 'ADMIN') {
                $query->where(function ($q) use ($employee) {
                    $q->where('assigned_tenant_user_id', $employee->id)
                      ->orWhereJsonContains('assignment_history', ['employee_id' => $employee->id])
                      ->orWhereJsonContains('assignment_history', ['employee_id' => (string)$employee->id]);
                });
            }
        }

        return $query;
    }

    /**
     * API for dropdown panel list and red-dot state
     */
    public function feed(Request $request)
    {
        $convQuery = Conversation::query()->with('contact');
        $this->applyUserScope($convQuery);

        // Fetch recent active conversations (activities)
        $recent = $convQuery
            ->whereNotNull('last_message_at')
            ->orderBy('last_message_at', 'desc')
            ->limit(10)
            ->get();

        $unreadCountQuery = Conversation::query()->where('unread_count', '>', 0);
        $this->applyUserScope($unreadCountQuery);
        $totalUnread = (int) $unreadCountQuery->sum('unread_count');

        $items = $recent->map(function ($c) {
            $sender = $c->contact?->name ?: ($c->contact?->phone_number ?: 'Customer');
            if (str_starts_with($sender, 'web:')) {
                $sender = 'Widget Visitor';
            }
            $timeCarbon = $c->last_message_at ? Carbon::parse($c->last_message_at) : $c->updated_at;

            return [
                'id' => $c->id,
                'sender' => $sender,
                'preview' => $c->last_message_preview ?: 'New activity in conversation',
                'channel' => strtolower($c->channel ?? 'whatsapp'),
                'unread_count' => (int) ($c->unread_count ?? 0),
                'is_unread' => ($c->unread_count ?? 0) > 0,
                'time_formatted' => $timeCarbon ? $timeCarbon->diffForHumans() : '',
                'date_formatted' => $timeCarbon ? $timeCarbon->format('d M Y, h:i A') : '',
                'url' => route('conversations.show', $c->id),
            ];
        });

        return response()->json([
            'unread_count' => $totalUnread,
            'has_unread' => $totalUnread > 0,
            'items' => $items,
        ]);
    }

    /**
     * Dedicated View All Notifications & Activities Page with date filter
     */
    public function index(Request $request)
    {
        $query = Conversation::query()->with(['contact', 'assignedUser']);
        $this->applyUserScope($query);

        // Only show conversations that have activity
        $query->where(function ($q) {
            $q->whereNotNull('last_message_at')
              ->orWhereNotNull('first_message_at');
        });

        // Date-based filtering
        if ($request->filled('date_from')) {
            $query->whereDate('last_message_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('last_message_at', '<=', $request->date_to);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('filter_unread') && $request->filter_unread === '1') {
            $query->where('unread_count', '>', 0);
        }

        $notifications = $query->orderBy('last_message_at', 'desc')->paginate(15)->withQueryString();

        return view('notifications.index', compact('notifications'));
    }
}
