<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FaqController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->company_id;
        $faqs = Faq::where('tenant_id', $companyId)->orderBy('id', 'desc')->get();
        return view('pages.general.faq', compact('faqs'));
    }

    public function create()
    {
        return view('pages.faqs.create');
    }

    public function store(Request $request, \App\Services\FaqApiService $apiService)
    {
        $companyId = auth()->user()->company_id;
        $validated = $request->validate([
            'question' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'keywords' => 'nullable|string|max:255',
            'match_type' => 'nullable|string|max:255',
            'priority' => 'nullable|string|max:255',
            'answer' => 'required|string',
            'attachment' => 'nullable|file|max:10240',
            'url' => 'nullable|url|max:255',
        ]);

        $sourceUrl = $validated['url'] ?? null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachResult = $apiService->attachFile($file->getPathname(), $file->getClientOriginalName());
            if ($attachResult && isset($attachResult['url'])) {
                $sourceUrl = $attachResult['url'];
                $validated['attachment'] = $attachResult['url'];
            } else {
                // Fallback to local storage if the API call itself failed
                $path = $file->store('faqs', 'public');
                $validated['attachment'] = Storage::url($path);
            }
        }

        $keywordList = !empty($validated['keywords'])
            ? array_filter(array_map('trim', explode(',', $validated['keywords'])))
            : [];
        // Note: You might want to update the API to pass tenant_id so vector DB is scoped too, 
        // but for now we just scope the DB.
        $textId = $apiService->uploadText($validated['question'], $validated['answer'], $sourceUrl, false, $keywordList);
        $validated['faq_hash_id'] = $textId;
        $validated['python_source_ids'] = $textId ? [$textId] : [];
        $validated['is_active'] = $request->has('is_active') ? true : false;
        $validated['tenant_id'] = $companyId;
        $validated['created_by'] = auth()->id();

        Faq::create($validated);

        return redirect()->route('faqs.index')->with('success', 'FAQ created and synchronized successfully.');
    }

    public function edit($id)
    {
        $companyId = auth()->user()->company_id;
        $faq = Faq::where('tenant_id', $companyId)->findOrFail($id);
        return view('pages.faqs.edit', compact('faq'));
    }

    public function update(Request $request, $id, \App\Services\FaqApiService $apiService)
    {
        $companyId = auth()->user()->company_id;
        $faq = Faq::where('tenant_id', $companyId)->findOrFail($id);

        $validated = $request->validate([
            'question' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'keywords' => 'nullable|string|max:255',
            'match_type' => 'nullable|string|max:255',
            'priority' => 'nullable|string|max:255',
            'answer' => 'required|string',
            'attachment' => 'nullable|file|max:10240',
            'url' => 'nullable|url|max:255',
        ]);

        // Delete old python sources
        if (is_array($faq->python_source_ids)) {
            foreach ($faq->python_source_ids as $sourceId) {
                $apiService->deleteSource($sourceId);
            }
        } elseif ($faq->faq_hash_id) {
            $apiService->deleteSource($faq->faq_hash_id);
        }

        $sourceUrl = $validated['url'] ?? null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachResult = $apiService->attachFile($file->getPathname(), $file->getClientOriginalName());
            if ($attachResult && isset($attachResult['url'])) {
                $sourceUrl = $attachResult['url'];
                $validated['attachment'] = $attachResult['url'];
            } else {
                $path = $file->store('faqs', 'public');
                $validated['attachment'] = Storage::url($path);
            }
        }

        $keywordList = !empty($validated['keywords'])
            ? array_filter(array_map('trim', explode(',', $validated['keywords'])))
            : [];
        $textId = $apiService->uploadText($validated['question'], $validated['answer'], $sourceUrl, false, $keywordList);
        $validated['faq_hash_id'] = $textId;
        $validated['python_source_ids'] = $textId ? [$textId] : [];
        $validated['is_active'] = $request->has('is_active') ? true : false;

        $faq->update($validated);

        return redirect()->route('faqs.index')->with('success', 'FAQ updated and synchronized successfully.');
    }

    public function destroy($id, \App\Services\FaqApiService $apiService)
    {
        $companyId = auth()->user()->company_id;
        $faq = Faq::where('tenant_id', $companyId)->findOrFail($id);

        // Delete python sources
        if (is_array($faq->python_source_ids)) {
            foreach ($faq->python_source_ids as $sourceId) {
                $apiService->deleteSource($sourceId);
            }
        } elseif ($faq->faq_hash_id) {
            $apiService->deleteSource($faq->faq_hash_id);
        }

        $faq->delete();
        return redirect()->route('faqs.index')->with('success', 'FAQ deleted and removed from bot successfully.');
    }

    public function toggleStatus($id)
    {
        $companyId = auth()->user()->company_id;
        $faq = Faq::where('tenant_id', $companyId)->findOrFail($id);
        
        $faq->update(['is_active' => !$faq->is_active]);
        return redirect()->route('faqs.index')->with('success', 'FAQ status updated successfully.');
    }
}
