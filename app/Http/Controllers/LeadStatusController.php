<?php

namespace App\Http\Controllers;

use App\Models\LeadStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadStatusController extends Controller
{
    public function index(): View
    {
        $ids = auth()->user()->companyUserIds();

        $leadStatuses = LeadStatus::whereIn('created_by', $ids)
            ->latest()
            ->paginate(10);

        return view('lead-status.index', compact('leadStatuses'));
    }

    public function create(): View
    {
        return view('lead-status.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'   => ['required', 'string', 'max:255', 'unique:lead_statuses,name'],
            'status' => ['required', 'in:0,1'],
        ], [
            'name.required' => 'Lead status name is required.',
            'name.unique'   => 'This lead status already exists.',
            'status.required' => 'Status is required.',
        ]);

        LeadStatus::create([
            'name'       => $request->name,
            'status'     => $request->status,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('lead-status.index')
            ->with('success', 'Lead status created successfully.');
    }

    // Ownership check
    private function canManage(int $createdBy): bool
    {
        return in_array($createdBy, auth()->user()->companyUserIds());
    }

    public function edit(LeadStatus $leadStatus): View
    {
        abort_if(!$this->canManage($leadStatus->created_by), 403);

        return view('lead-status.edit', compact('leadStatus'));
    }

    public function update(Request $request, LeadStatus $leadStatus): RedirectResponse
    {
        abort_if(!$this->canManage($leadStatus->created_by), 403);

        $request->validate([
            'name'   => ['required', 'string', 'max:255', 'unique:lead_statuses,name,' . $leadStatus->id],
            'status' => ['required', 'in:0,1'],
        ], [
            'name.required'   => 'Lead status name is required.',
            'name.unique'     => 'This lead status already exists.',
            'status.required' => 'Status is required.',
        ]);

        $leadStatus->update($request->only('name', 'status'));

        return redirect()->route('lead-status.index')
            ->with('success', 'Lead status updated successfully.');
    }

    public function destroy(LeadStatus $leadStatus): RedirectResponse
    {
        abort_if(!$this->canManage($leadStatus->created_by), 403);

        $leadStatus->delete();

        return redirect()->route('lead-status.index')
            ->with('success', 'Lead status deleted successfully.');
    }
}
