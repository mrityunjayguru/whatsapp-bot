<?php

namespace App\Http\Controllers;

use App\Models\InterestedIn;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InterestedInController extends Controller
{
    public function index(): View
    {
        $ids = auth()->user()->companyUserIds();

        $interestedIns = InterestedIn::whereIn('created_by', $ids)
            ->latest()
            ->paginate(10);

        return view('interested-in.index', compact('interestedIns'));
    }

    public function create(): View
    {
        return view('interested-in.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'title'  => ['required', 'string', 'max:255', 'unique:interested_ins,title'],
            'status' => ['required', 'in:0,1'],
        ], [
            'title.required' => 'Title is required.',
            'title.unique'   => 'This interested in title already exists.',
            'status.required' => 'Status is required.',
        ]);

        InterestedIn::create([
            'title'      => $request->title,
            'status'     => $request->status,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('interested-in.index')
            ->with('success', 'Interested In created successfully.');
    }

    // Ownership check
    private function canManage(int $createdBy): bool
    {
        return in_array($createdBy, auth()->user()->companyUserIds());
    }

    public function edit(InterestedIn $interestedIn): View
    {
        abort_if(!$this->canManage($interestedIn->created_by), 403);

        return view('interested-in.edit', compact('interestedIn'));
    }

    public function update(Request $request, InterestedIn $interestedIn): RedirectResponse
    {
        abort_if(!$this->canManage($interestedIn->created_by), 403);

        $request->validate([
            'title'  => ['required', 'string', 'max:255', 'unique:interested_ins,title,' . $interestedIn->id],
            'status' => ['required', 'in:0,1'],
        ], [
            'title.required'  => 'Title is required.',
            'title.unique'    => 'This interested in title already exists.',
            'status.required' => 'Status is required.',
        ]);

        $interestedIn->update($request->only('title', 'status'));

        return redirect()->route('interested-in.index')
            ->with('success', 'Interested In updated successfully.');
    }

    public function destroy(InterestedIn $interestedIn): RedirectResponse
    {
        abort_if(!$this->canManage($interestedIn->created_by), 403);

        $interestedIn->delete();

        return redirect()->route('interested-in.index')
            ->with('success', 'Interested In deleted successfully.');
    }
}
