<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadFollowUp;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        // Superadmin — no lead stats
        if ($user->role_id === 1) {
            return view('dashboard', ['stats' => null, 'upcomingFollowUps' => collect(), 'activities' => collect()]);
        }

        // Company owner or sub-user — scoped to company
        $ids   = $user->companyUserIds();
        $today = Carbon::today();
        $month = Carbon::now()->month;
        $year  = Carbon::now()->year;

        // Find "Interested" and "Won" and "Lost" status IDs by name
        $interestedIds = \App\Models\LeadStatus::whereIn('created_by', $ids)
            ->whereRaw('LOWER(name) = ?', ['interested'])
            ->pluck('id');

        $wonIds = \App\Models\LeadStatus::whereIn('created_by', $ids)
            ->whereRaw('LOWER(name) = ?', ['won'])
            ->pluck('id');

        $lostIds = \App\Models\LeadStatus::whereIn('created_by', $ids)
            ->whereRaw('LOWER(name) = ?', ['lost'])
            ->pluck('id');

        $stats = [
            // Leads created today
            'today_leads' => Lead::whereIn('created_by', $ids)
                ->whereDate('created_at', $today)
                ->count(),

            // Follow-ups scheduled for today
            'today_followups' => LeadFollowUp::whereHas('lead', fn($q) => $q->whereIn('created_by', $ids))
                ->whereDate('follow_up_date', $today)
                ->count(),

            // Pending follow-ups (follow_up_date < today and lead not closed/won/lost)
            'pending_followups' => Lead::whereIn('created_by', $ids)
                ->whereNotNull('follow_up_date')
                ->whereDate('follow_up_date', '<', $today)
                ->where('is_closed', false)
                ->whereNotIn('lead_status_id', $wonIds->merge($lostIds)->unique())
                ->count(),

            // Interested leads (all time)
            'interested_leads' => Lead::whereIn('created_by', $ids)
                ->whereIn('lead_status_id', $interestedIds)
                ->count(),

            // Won this month
            'won_this_month' => Lead::whereIn('created_by', $ids)
                ->whereIn('lead_status_id', $wonIds)
                ->whereMonth('updated_at', $month)
                ->whereYear('updated_at', $year)
                ->count(),

            // Lost this month
            'lost_this_month' => Lead::whereIn('created_by', $ids)
                ->whereIn('lead_status_id', $lostIds)
                ->whereMonth('updated_at', $month)
                ->whereYear('updated_at', $year)
                ->count(),
        ];

        // Today's upcoming follow-ups — sorted by time
        $upcomingFollowUps = Lead::with('leadStatus')
            ->whereIn('created_by', $ids)
            ->whereDate('follow_up_date', $today)
            ->whereNotNull('follow_up_time')
            ->where('is_closed', false)
            ->orderBy('follow_up_time')
            ->get();

        // Recent activities — latest 50, scoped to company
        $activities = \App\Models\LeadActivity::with(['lead', 'performer'])
            ->whereHas('lead', fn($q) => $q->whereIn('created_by', $ids))
            ->latest()
            ->limit(50)
            ->get();

        return view('dashboard', compact('stats', 'upcomingFollowUps', 'activities'));
    }

    public function followUps(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();

        if ($user->role_id === 1) {
            return response()->json([]);
        }

        $ids  = $user->companyUserIds();
        $date = $request->filled('date')
            ? \Carbon\Carbon::parse($request->date)->toDateString()
            : \Carbon\Carbon::today()->toDateString();

        $leads = Lead::whereIn('created_by', $ids)
            ->whereDate('follow_up_date', $date)
            ->where('is_closed', false)
            ->orderBy('follow_up_time')
            ->get(['id', 'lead_name', 'requirement', 'follow_up_time']);

        return response()->json($leads->map(fn($l) => [
            'id'          => $l->id,
            'lead_name'   => $l->lead_name,
            'requirement' => $l->requirement,
            'time'        => $l->follow_up_time
                ? \Carbon\Carbon::parse($l->follow_up_time)->format('h:i A')
                : null,
            'edit_url'    => $user->hasPermission('leads.edit')
                ? route('leads.edit', $l->id)
                : null,
        ]));
    }
}
