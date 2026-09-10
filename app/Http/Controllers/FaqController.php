<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FaqController extends Controller
{
    public function index()
    {
        $faqs = Faq::orderBy('id', 'desc')->get();
        return view('pages.general.faq', compact('faqs'));
    }

    public function create()
    {
        return view('pages.faqs.create');
    }

    public function store(Request $request, \App\Services\FaqApiService $apiService)
    {
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

        $pythonSourceIds = [];

        // 1. Always upload the text as a source
        $textId = $apiService->uploadText($validated['question'], $validated['answer'], $validated['url'] ?? null, false);
        $validated['faq_hash_id'] = $textId;
        if ($textId) $pythonSourceIds[] = $textId;

        // 2. Upload Document if provided
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $docResult = $apiService->uploadDocument($file->getPathname(), $file->getClientOriginalName(), true);
            if ($docResult && isset($docResult['source_url'])) {
                $validated['attachment'] = $docResult['source_url'];
                if (isset($docResult['id'])) $pythonSourceIds[] = $docResult['id'];
            } else {
                // Fallback to local storage if API fails
                $path = $file->store('faqs', 'public');
                $validated['attachment'] = Storage::url($path);
            }
        }

        // 3. Upload URL if provided
        if (!empty($validated['url'])) {
            $urlId = $apiService->uploadUrl($validated['url'], $validated['question']);
            if ($urlId) $pythonSourceIds[] = $urlId;
        }

        $validated['python_source_ids'] = $pythonSourceIds;
        $validated['is_active'] = $request->has('is_active') ? true : false;

        Faq::create($validated);

        return redirect()->route('faqs.index')->with('success', 'FAQ created and synchronized successfully.');
    }

    public function edit(Faq $faq)
    {
        return view('pages.faqs.edit', compact('faq'));
    }

    public function update(Request $request, Faq $faq, \App\Services\FaqApiService $apiService)
    {
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

        $pythonSourceIds = [];

        // 1. Upload new text
        $textId = $apiService->uploadText($validated['question'], $validated['answer'], $validated['url'] ?? null, false);
        $validated['faq_hash_id'] = $textId;
        if ($textId) $pythonSourceIds[] = $textId;

        // 2. Upload Document
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $docResult = $apiService->uploadDocument($file->getPathname(), $file->getClientOriginalName(), true);
            if ($docResult && isset($docResult['source_url'])) {
                $validated['attachment'] = $docResult['source_url'];
                if (isset($docResult['id'])) $pythonSourceIds[] = $docResult['id'];
            } else {
                $path = $file->store('faqs', 'public');
                $validated['attachment'] = Storage::url($path);
            }
        }

        // 3. Upload URL
        if (!empty($validated['url'])) {
            $urlId = $apiService->uploadUrl($validated['url'], $validated['question']);
            if ($urlId) $pythonSourceIds[] = $urlId;
        }

        $validated['python_source_ids'] = $pythonSourceIds;
        $validated['is_active'] = $request->has('is_active') ? true : false;

        $faq->update($validated);

        return redirect()->route('faqs.index')->with('success', 'FAQ updated and synchronized successfully.');
    }

    public function destroy(Faq $faq, \App\Services\FaqApiService $apiService)
    {
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

    public function toggleStatus(Faq $faq)
    {
        $faq->update(['is_active' => !$faq->is_active]);
        return redirect()->route('faqs.index')->with('success', 'FAQ status updated successfully.');
    }
}
