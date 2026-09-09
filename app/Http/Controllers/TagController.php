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
        $query = Tag::with('creator')->withCount('contacts')->latest();

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
        $request->validate([
            'tag_name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Generate #TAG-XXX format
        $lastTag = Tag::orderBy('id', 'desc')->first();
        $nextId = $lastTag ? $lastTag->id + 1 : 1;
        $tagId = '#TAG-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);

        Tag::create([
            'tag_id' => $tagId,
            'tag_name' => $request->tag_name,
            'description' => $request->description,
            'status' => $request->has('status') ? 1 : 0,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('tags.index')->with('success', 'Tag created successfully.');
    }
}
