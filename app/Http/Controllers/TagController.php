<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class TagController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = auth()->user()->company_id;
        $query = Tag::where('tenant_id', $companyId)->with('creator')->withCount('contacts')->latest();

        // 1. Search filter
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('tag_name', 'like', "%{$search}%")
                  ->orWhere('tag_id', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // 2. Status filter
        if ($status = $request->input('status')) {
            if ($status === 'active') {
                $query->where('status', 1);
            } elseif ($status === 'inactive') {
                $query->where('status', 0);
            }
        }

        // 3. Created Date filter
        if ($createdDate = $request->input('created_date')) {
            $query->whereDate('created_at', $createdDate);
        }

        $tags = $query->paginate(15)->withQueryString();

        return view('tags.index', compact('tags', 'request'));
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = auth()->user()->company_id;
        $request->validate([
            'tag_name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Validate uniqueness within the same company
        $exists = Tag::where('tenant_id', $companyId)->where('tag_name', $request->tag_name)->exists();
        if ($exists) {
            return redirect()->back()->withErrors(['tag_name' => 'The tag name has already been taken.'])->withInput();
        }

        // Generate #TAG-XXX format
        $lastTag = Tag::orderBy('id', 'desc')->first();
        $nextId = $lastTag ? $lastTag->id + 1 : 1;
        $tagId = '#TAG-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);

        Tag::create([
            'tenant_id' => $companyId,
            'tag_id' => $tagId,
            'tag_name' => $request->tag_name,
            'description' => $request->description,
            'status' => $request->has('status') ? 1 : 0,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('tags.index')->with('success', 'Tag created successfully.');
    }

    public function show($id): View
    {
        $companyId = auth()->user()->company_id;
        $tag = Tag::where('tenant_id', $companyId)->findOrFail($id);
        $tag->load('creator');
        
        $contactsQuery = $tag->contacts()
            ->where('contacts.tenant_id', $companyId)
            ->with(['tags', 'conversations' => function($q) {
                $q->latest();
            }]);

        if (auth()->id() !== 1) {
            $employee = \App\Models\Employee::where('email', auth()->user()->email)->first();
            if ($employee && $employee->role !== 'ADMIN') {
                $contactsQuery->whereHas('conversations', function($q) use ($employee) {
                    $q->where('assigned_tenant_user_id', $employee->id);
                });
            } elseif (!$employee) {
                $contactsQuery->whereHas('conversations', function($q) {
                    $q->where('assigned_tenant_user_id', auth()->id());
                });
            }
        }

        $contacts = $contactsQuery->paginate(10);
            
        return view('tags.show', compact('tag', 'contacts'));
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $companyId = auth()->user()->company_id;
        $tag = Tag::where('tenant_id', $companyId)->findOrFail($id);

        $request->validate([
            'tag_name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Validate uniqueness within the same company
        $exists = Tag::where('tenant_id', $companyId)
                     ->where('tag_name', $request->tag_name)
                     ->where('id', '!=', $tag->id)
                     ->exists();
        if ($exists) {
            return redirect()->back()->withErrors(['tag_name' => 'The tag name has already been taken.'])->withInput();
        }

        $tag->update([
            'tag_name' => $request->tag_name,
            'description' => $request->description,
            'status' => $request->has('status') ? 1 : 0,
        ]);

        return redirect()->back()->with('success', 'Tag updated successfully.');
    }

    public function destroy($id): RedirectResponse
    {
        $companyId = auth()->user()->company_id;
        $tag = Tag::where('tenant_id', $companyId)->findOrFail($id);
        $tag->delete();
        return redirect()->route('tags.index')->with('success', 'Tag deleted successfully.');
    }
}
