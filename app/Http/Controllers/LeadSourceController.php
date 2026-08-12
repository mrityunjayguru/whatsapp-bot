<?php

namespace App\Http\Controllers;

use App\Models\LeadSource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadSourceController extends Controller
{
    public function index(): View
    {
        $ids = auth()->user()->companyUserIds();

        $leadSources = LeadSource::with('creator')
            ->whereIn('created_by', $ids)
            ->latest()
            ->paginate(10);

        return view('lead-source.index', compact('leadSources'));
    }

    public function create(): View
    {
        return view('lead-source.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'   => ['required', 'string', 'max:255', 'unique:lead_sources,name'],
            'status' => ['required', 'in:0,1'],
        ], [
            'name.required'   => 'Lead source name is required.',
            'name.unique'     => 'This lead source already exists.',
            'status.required' => 'Status is required.',
        ]);

        LeadSource::create([
            'name'       => $request->name,
            'status'     => $request->status,
            'created_by' => auth()->id(),   // auto-set logged-in company user
        ]);

        return redirect()->route('lead-source.index')
            ->with('success', 'Lead source created successfully.');
    }

    // Ownership check: allow company owner AND their sub-users to edit/delete
    private function canManage(int $createdBy): bool
    {
        return in_array($createdBy, auth()->user()->companyUserIds());
    }

    public function edit(LeadSource $leadSource): View
    {
        abort_if(!$this->canManage($leadSource->created_by), 403);

        return view('lead-source.edit', compact('leadSource'));
    }

    public function update(Request $request, LeadSource $leadSource): RedirectResponse
    {
        abort_if(!$this->canManage($leadSource->created_by), 403);

        $request->validate([
            'name'   => ['required', 'string', 'max:255', 'unique:lead_sources,name,' . $leadSource->id],
            'status' => ['required', 'in:0,1'],
        ], [
            'name.required'   => 'Lead source name is required.',
            'name.unique'     => 'This lead source already exists.',
            'status.required' => 'Status is required.',
        ]);

        $leadSource->update($request->only('name', 'status'));

        return redirect()->route('lead-source.index')
            ->with('success', 'Lead source updated successfully.');
    }

    public function destroy(LeadSource $leadSource): RedirectResponse
    {
        abort_if(!$this->canManage($leadSource->created_by), 403);

        $leadSource->delete();

        return redirect()->route('lead-source.index')
            ->with('success', 'Lead source deleted successfully.');
    }
}
