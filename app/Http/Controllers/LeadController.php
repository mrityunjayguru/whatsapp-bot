<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadFollowUp;
use App\Models\LeadNote;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\InterestedIn;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function index(Request $request): View
    {
        $ids    = auth()->user()->companyUserIds();
        $search = $request->input('search');

        // Filter inputs
        $filterStatus    = $request->input('status');
        $filterSource    = $request->input('source');
        $filterCreatedBy = $request->input('created_by');
        $filterFollowUp  = $request->input('follow_up_date');

        // Dropdown data for filters
        $statuses  = \App\Models\LeadStatus::whereIn('created_by', $ids)->where('status', 1)->orderBy('name')->get();
        $sources   = \App\Models\LeadSource::whereIn('created_by', $ids)->where('status', 1)->orderBy('name')->get();
        $creators  = \App\Models\User::whereIn('id', $ids)->orderBy('name')->get();

        $leads = Lead::with(['leadSource', 'leadStatus', 'recentFollowUps' => fn($q) => $q->whereNotNull('note')->latest()->limit(2)])
            ->whereIn('created_by', $ids)
            ->when($search, fn($q) => $q->where(fn($q) => $q
                ->where('lead_name', 'like', '%'.$search.'%')
                ->orWhere('phone_number', 'like', '%'.$search.'%')
                ->orWhere('company_name', 'like', '%'.$search.'%')
            ))
            ->when($filterStatus,    fn($q) => $q->where('lead_status_id', $filterStatus))
            ->when($filterSource,    fn($q) => $q->where('lead_source_id', $filterSource))
            ->when($filterCreatedBy, fn($q) => $q->where('created_by', $filterCreatedBy))
            ->when($filterFollowUp,  fn($q) => $q->whereDate('follow_up_date', $filterFollowUp))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('leads.index', compact('leads', 'search', 'statuses', 'sources', 'creators', 'filterStatus', 'filterSource', 'filterCreatedBy', 'filterFollowUp'));
    }

    public function create(): View
    {
        $ids = auth()->user()->companyUserIds();

        $leadSources = LeadSource::where('status', 1)
            ->whereIn('created_by', $ids)
            ->orderBy('name')
            ->get();

        $leadStatuses = LeadStatus::where('status', 1)
            ->whereIn('created_by', $ids)
            ->orderBy('name')
            ->get();

        $interestedIns = InterestedIn::whereIn('created_by', $ids)
            ->where('status', 1)
            ->orderBy('title')
            ->get();

        return view('leads.create', compact('leadSources', 'leadStatuses', 'interestedIns'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'lead_name'       => ['required', 'string', 'max:255'],
            'company_name'    => ['nullable', 'string', 'max:255'],
            'phone_number'    => ['required', 'string', 'max:20'],
            'whatsapp_number' => ['nullable', 'string', 'max:20'],
            'email'           => ['nullable', 'email', 'max:255'],
            'lead_source_id'  => ['required', 'exists:lead_sources,id'],
            'interested_in_id'=> ['nullable', 'exists:interested_ins,id'],
            'requirement'     => ['required', 'string'],
            'lead_status_id'  => ['required', 'exists:lead_statuses,id'],
            'follow_up_date'  => ['nullable', 'date'],
            'follow_up_time'  => ['nullable', 'date_format:H:i,H:i:s'],
            'follow_up_note'  => ['nullable', 'string', 'max:500'],
            'note'            => ['required', 'string'],
        ], [
            'lead_name.required'       => 'Lead name is required.',
            'phone_number.required'    => 'Phone number is required.',
            'lead_source_id.required'  => 'Lead source is required.',
            'lead_source_id.exists'    => 'Selected lead source is invalid.',
            'requirement.required'     => 'Requirement is required.',
            'lead_status_id.required'  => 'Lead status is required.',
            'lead_status_id.exists'    => 'Selected lead status is invalid.',
            'note.required'            => 'Notes / Description is required.',
        ]);

        $lead = Lead::create([
            'lead_id'          => Lead::generateLeadId(),
            'lead_name'        => $request->lead_name,
            'company_name'     => $request->company_name,
            'phone_number'     => $request->phone_number,
            'whatsapp_number'  => $request->whatsapp_number,
            'email'            => $request->email,
            'lead_source_id'   => $request->lead_source_id,
            'interested_in_id' => $request->interested_in_id,
            'requirement'      => $request->requirement,
            'lead_status_id'   => $request->lead_status_id,
            'follow_up_date'   => $request->follow_up_date,
            'follow_up_time'   => $request->follow_up_time,
            'created_by'       => auth()->id(),
            'updated_by'       => auth()->id(),
            'last_activity_at' => now(),
        ]);

        // Save initial follow-up to history if provided
        if ($request->filled('follow_up_date')) {
            LeadFollowUp::create([
                'lead_id'        => $lead->id,
                'follow_up_date' => $request->follow_up_date,
                'follow_up_time' => $request->follow_up_time,
                'note'           => $request->follow_up_note,
                'added_by'       => auth()->id(),
            ]);

            // Log activity: Follow-up Added
            LeadActivity::create([
                'lead_id'      => $lead->id,
                'type'         => 'follow_up_added',
            'description'  => 'Follow-up scheduled for ' . \Carbon\Carbon::parse($request->follow_up_date)->format('d M Y')
                                . ($request->follow_up_time ? ' at ' . \Carbon\Carbon::parse($request->follow_up_time)->format('h:i A') : '') . '.',
                'performed_by' => auth()->id(),
            ]);
        }

        LeadNote::create([
            'lead_id'  => $lead->id,
            'note'     => $request->note,
            'added_by' => auth()->id(),
        ]);

        // Log activity: Lead Created
        LeadActivity::create([
            'lead_id'      => $lead->id,
            'type'         => 'lead_created',
            'description'  => 'Lead ' . $lead->lead_id . ' was created.',
            'performed_by' => auth()->id(),
        ]);

        if ($request->has('save_and_add_another')) {
            return redirect()->route('leads.create')
                ->with('success', "Lead {$lead->lead_id} created successfully.");
        }

        return redirect()->route('leads.index')
            ->with('success', "Lead {$lead->lead_id} created successfully.");
    }

    public function export(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('leads.export'), 403);

        $ids    = auth()->user()->companyUserIds();
        $format = $request->input('format', 'csv');
        $selectedIds = $request->input('ids', []);  // specific lead IDs from checkboxes

        $leads = Lead::with(['leadSource', 'leadStatus'])
            ->whereIn('created_by', $ids)
            ->when(!empty($selectedIds), fn($q) => $q->whereIn('id', $selectedIds))
            ->when(empty($selectedIds) && $request->input('search'), fn($q) => $q->where(fn($q) => $q
                ->where('lead_name', 'like', '%'.$request->input('search').'%')
                ->orWhere('phone_number', 'like', '%'.$request->input('search').'%')
                ->orWhere('company_name', 'like', '%'.$request->input('search').'%')
            ))
            ->when(empty($selectedIds) && $request->input('status'),     fn($q) => $q->where('lead_status_id', $request->input('status')))
            ->when(empty($selectedIds) && $request->input('source'),     fn($q) => $q->where('lead_source_id', $request->input('source')))
            ->when(empty($selectedIds) && $request->input('created_by'), fn($q) => $q->where('created_by', $request->input('created_by')))
            ->latest()
            ->get();

        $headers = ['Lead ID', 'Name', 'Company', 'Phone', 'WhatsApp', 'Email', 'Source', 'Status', 'Follow-up Date', 'Requirement', 'Created At'];

        $rows = $leads->map(fn($lead) => [
            $lead->lead_id,
            $lead->lead_name,
            $lead->company_name ?? '',
            $lead->phone_number,
            $lead->whatsapp_number ?? '',
            $lead->email ?? '',
            $lead->leadSource->name ?? '',
            $lead->leadStatus->name ?? '',
            $lead->follow_up_date ? $lead->follow_up_date->format('d M Y') : '',
            str_replace(["\r\n", "\n", "\r"], ' ', $lead->requirement),
            $lead->created_at->format('d M Y'),
        ])->toArray();

        $filename = 'leads_' . now()->format('Y-m-d_His');

        if ($format === 'csv') {
            $callback = function () use ($headers, $rows) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, $headers);
                foreach ($rows as $row) {
                    fputcsv($handle, $row);
                }
                fclose($handle);
            };
            return response()->stream($callback, 200, [
                'Content-Type'        => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
            ]);
        }

        if ($format === 'excel') {
            $excelData = [$headers];
            foreach ($rows as $row) {
                $excelData[] = $row;
            }
            
            $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($excelData);
            $xlsx->downloadAs("{$filename}.xlsx");
            exit;
        }

        if ($format === 'pdf') {
            $html  = view('leads.export_pdf', compact('leads', 'headers', 'rows'))->render();
            return response($html, 200, [
                'Content-Type' => 'text/html; charset=utf-8',
            ]);
        }

        abort(400, 'Invalid export format.');
    }

    public function show(Lead $lead): View
    {
        $ids = auth()->user()->companyUserIds();
        abort_if(!in_array($lead->created_by, $ids), 403);

        $lead->load(['leadSource', 'leadStatus']);
        $followUps = $lead->followUps()->latest()->with('addedBy')->get();

        return view('leads.show', compact('lead', 'followUps'));
    }

    public function edit(Lead $lead): View
    {
        $ids = auth()->user()->companyUserIds();
        abort_if(!in_array($lead->created_by, $ids), 403);

        $leadSources = LeadSource::where('status', 1)
            ->whereIn('created_by', $ids)
            ->orderBy('name')
            ->get();

        $leadStatuses = LeadStatus::where('status', 1)
            ->whereIn('created_by', $ids)
            ->orderBy('name')
            ->get();

        $interestedIns = InterestedIn::whereIn('created_by', $ids)
            ->where('status', 1)
            ->orderBy('title')
            ->get();

        $notes     = $lead->notes()->latest()->with('addedBy')->get();
        $followUps = $lead->followUps()->latest()->with('addedBy')->get();

        return view('leads.edit', compact('lead', 'leadSources', 'leadStatuses', 'interestedIns', 'notes', 'followUps'));
    }

    public function update(Request $request, Lead $lead): RedirectResponse
    {
        $ids = auth()->user()->companyUserIds();
        abort_if(!in_array($lead->created_by, $ids), 403);

        $request->validate([
            'lead_name'       => ['required', 'string', 'max:255'],
            'company_name'    => ['nullable', 'string', 'max:255'],
            'phone_number'    => ['required', 'string', 'max:20'],
            'whatsapp_number' => ['nullable', 'string', 'max:20'],
            'email'           => ['nullable', 'email', 'max:255'],
            'lead_source_id'  => ['required', 'exists:lead_sources,id'],
            'interested_in_id'=> ['nullable', 'exists:interested_ins,id'],
            'requirement'     => ['required', 'string'],
            'lead_status_id'  => ['required', 'exists:lead_statuses,id'],
            'follow_up_date'  => ['nullable', 'date'],
            'follow_up_time'  => ['nullable', 'date_format:H:i,H:i:s'],
            'follow_up_note'  => ['nullable', 'string', 'max:500'],
            'note'            => ['nullable', 'string'],
        ], [
            'lead_name.required'      => 'Lead name is required.',
            'phone_number.required'   => 'Phone number is required.',
            'lead_source_id.required' => 'Lead source is required.',
            'requirement.required'    => 'Requirement is required.',
            'lead_status_id.required' => 'Lead status is required.',
            'lead_status_id.exists'   => 'Selected lead status is invalid.',
        ]);

        // Capture old values BEFORE update
        $oldDate     = $lead->follow_up_date?->format('Y-m-d');
        $oldTime     = $lead->follow_up_time ? substr($lead->follow_up_time, 0, 5) : null;
        $newTime     = $request->follow_up_time ? substr($request->follow_up_time, 0, 5) : null;
        $oldStatusId = $lead->lead_status_id;

        $lead->update([
            'lead_name'        => $request->lead_name,
            'company_name'     => $request->company_name,
            'phone_number'     => $request->phone_number,
            'whatsapp_number'  => $request->whatsapp_number,
            'email'            => $request->email,
            'lead_source_id'   => $request->lead_source_id,
            'interested_in_id' => $request->interested_in_id,
            'requirement'      => $request->requirement,
            'lead_status_id'   => $request->lead_status_id,
            'follow_up_date'   => $request->follow_up_date,
            'follow_up_time'   => $request->follow_up_time,
            'updated_by'       => auth()->id(),
            'last_activity_at' => now(),
        ]);

        // Save follow-up to history if a date is set
        if ($request->filled('follow_up_date')) {
            $dateChanged = $request->follow_up_date !== $oldDate;
            $timeChanged = $newTime !== $oldTime;
            $noteChanged = $request->filled('follow_up_note');

            if ($dateChanged || $timeChanged) {
                // Date or time changed — create new history entry
                LeadFollowUp::create([
                    'lead_id'        => $lead->id,
                    'follow_up_date' => $request->follow_up_date,
                    'follow_up_time' => $request->follow_up_time,
                    'note'           => $request->follow_up_note,
                    'added_by'       => auth()->id(),
                ]);

                // Log activity: Follow-up Added
                LeadActivity::create([
                    'lead_id'      => $lead->id,
                    'type'         => 'follow_up_added',
                    'description'  => 'Follow-up scheduled for ' . \Carbon\Carbon::parse($request->follow_up_date)->format('d M Y')
                                    . ($request->follow_up_time ? ' at ' . \Carbon\Carbon::parse($request->follow_up_time)->format('h:i A') : '')
                                    . ($request->follow_up_note ? ' — ' . $request->follow_up_note : '')
                                    . ' by ' . auth()->user()->name . '.',
                    'performed_by' => auth()->id(),
                ]);
            } elseif ($noteChanged) {
                // Only note changed — update latest existing entry
                $latest = LeadFollowUp::where('lead_id', $lead->id)->latest()->first();
                if ($latest) {
                    $latest->update(['note' => $request->follow_up_note]);
                } else {
                    LeadFollowUp::create([
                        'lead_id'        => $lead->id,
                        'follow_up_date' => $request->follow_up_date,
                        'follow_up_time' => $request->follow_up_time,
                        'note'           => $request->follow_up_note,
                        'added_by'       => auth()->id(),
                    ]);
                }
            }
            // Nothing changed — do nothing
        }

        // Log activity: Status Changed
        if ((int) $request->lead_status_id !== (int) $oldStatusId) {
            $newStatus = LeadStatus::find($request->lead_status_id);
            $oldStatus = LeadStatus::find($oldStatusId);
            LeadActivity::create([
                'lead_id'      => $lead->id,
                'type'         => 'status_changed',
                'description'  => 'Status changed from "' . ($oldStatus->name ?? 'N/A') . '" to "' . ($newStatus->name ?? 'N/A') . '".',
                'performed_by' => auth()->id(),
            ]);
        }

        // Update the latest note, or create if none exists
        if ($request->filled('note')) {
            $latestNote = $lead->notes()->latest()->first();
            if ($latestNote) {
                $latestNote->update([
                    'note'     => $request->note,
                    'added_by' => auth()->id(),
                ]);
            } else {
                LeadNote::create([
                    'lead_id'  => $lead->id,
                    'note'     => $request->note,
                    'added_by' => auth()->id(),
                ]);
            }
        }

        return redirect()->route('leads.edit', $lead)
            ->with('success', 'Lead updated successfully.');
    }

    public function calendarEvents(): \Illuminate\Http\JsonResponse
    {
        $ids = auth()->user()->companyUserIds();

        $leads = Lead::with('leadStatus')
            ->whereIn('created_by', $ids)
            ->whereNotNull('follow_up_date')
            ->get();

        $events = $leads->map(function ($lead) {
            $statusName = $lead->leadStatus->name ?? 'No Status';

            // color map same as index badge
            $colorMap = [
                'new'        => '#3b82f6',
                'contacted'  => '#6b7280',
                'interested' => '#f97316',
                'follow-up'  => '#f59e0b',
                'follow up'  => '#f59e0b',
                'quotation sent' => '#8b5cf6',
                'won'        => '#22c55e',
                'lost'       => '#ef4444',
                'not interested' => '#1f2937',
            ];
            $color = $colorMap[strtolower($statusName)] ?? '#6b7280';

            $start = $lead->follow_up_date->format('Y-m-d');
            if ($lead->follow_up_time) {
                $start .= 'T' . substr($lead->follow_up_time, 0, 5);
            }

            return [
                'id'    => $lead->id,
                'title' => $lead->lead_name,
                'start' => $start,
                'color' => $color,
                'url'   => route('leads.edit', $lead),
                'extendedProps' => [
                    'lead_id'  => $lead->lead_id,
                    'phone'    => $lead->phone_number,
                    'status'   => $statusName,
                ],
            ];
        });

        return response()->json($events);
    }

    public function close(Lead $lead): RedirectResponse
    {
        abort_if(!in_array($lead->created_by, auth()->user()->companyUserIds()), 403);

        $lead->update(['is_closed' => true]);

        // Log activity: Lead Closed
        LeadActivity::create([
            'lead_id'      => $lead->id,
            'type'         => 'lead_closed',
            'description'  => 'Lead ' . $lead->lead_id . ' was closed.',
            'performed_by' => auth()->id(),
        ]);

        return redirect()->route('leads.edit', $lead)
            ->with('success', "Lead {$lead->lead_id} has been closed.");
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        abort_if(!in_array($lead->created_by, auth()->user()->companyUserIds()), 403);

        $lead->delete();

        return redirect()->route('leads.index')
            ->with('success', 'Lead deleted successfully.');
    }
}
