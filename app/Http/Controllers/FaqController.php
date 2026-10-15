<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\WhatsappNumber;
use App\Services\WhatsappNumberApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FaqController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->company_id ?? auth()->user()->tenant_id;
        $faqs = Faq::where('tenant_id', $companyId)->orderBy('id', 'desc')->get();
        return view('pages.general.faq', compact('faqs'));
    }

    public function create()
    {
        return view('pages.faqs.create');
    }

    public function store(Request $request, \App\Services\FaqApiService $apiService)
    {
        $companyId = auth()->user()->company_id ?? auth()->user()->tenant_id;
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
                $validated['attachment'] = asset(Storage::url($path));
            }
        }

        $keywordList = !empty($validated['keywords'])
            ? array_filter(array_map('trim', explode(',', $validated['keywords'])))
            : [];

        $whatsappAnswer = self::formatHtmlForWhatsapp($validated['answer']);
        $textId = $apiService->uploadText($validated['question'], $whatsappAnswer, $sourceUrl, false, $keywordList);
        $validated['faq_hash_id'] = $textId;
        $validated['python_source_ids'] = $textId ? [$textId] : [];
        $validated['is_active'] = $request->has('is_active') ? true : false;
        $validated['tenant_id'] = $companyId;
        $validated['created_by'] = auth()->id();

        // Also sync to each WhatsApp number's own FAQ store
        $this->syncFaqToWhatsappNumbers($companyId, $validated['question'], $validated['answer'], $sourceUrl, $keywordList, null);

        Faq::create($validated);

        return redirect()->route('faqs.index')->with('success', 'FAQ created and synchronized successfully.');
    }

    public function edit($id)
    {
        $companyId = auth()->user()->company_id ?? auth()->user()->tenant_id;
        $faq = Faq::where('tenant_id', $companyId)->findOrFail($id);
        return view('pages.faqs.edit', compact('faq'));
    }

    public function update(Request $request, $id, \App\Services\FaqApiService $apiService)
    {
        $companyId = auth()->user()->company_id ?? auth()->user()->tenant_id;
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
                $validated['attachment'] = asset(Storage::url($path));
            }
        } elseif ($request->input('remove_attachment') == '1') {
            $validated['attachment'] = null;
            $sourceUrl = $validated['url'] ?? null;
        } else {
            // Preserve existing attachment
            $validated['attachment'] = $faq->attachment;
            $sourceUrl = $faq->attachment ? $faq->attachment : ($validated['url'] ?? null);
        }

        $keywordList = !empty($validated['keywords'])
            ? array_filter(array_map('trim', explode(',', $validated['keywords'])))
            : [];
        $whatsappAnswer = self::formatHtmlForWhatsapp($validated['answer']);
        $textId = $apiService->uploadText($validated['question'], $whatsappAnswer, $sourceUrl, false, $keywordList);
        $validated['faq_hash_id'] = $textId;
        $validated['python_source_ids'] = $textId ? [$textId] : [];
        $validated['is_active'] = $request->has('is_active') ? true : false;

        // Sync to each WhatsApp number's own FAQ store (old source IDs for deletion)
        $oldSourceIds = is_array($faq->python_source_ids) ? $faq->python_source_ids : ($faq->faq_hash_id ? [$faq->faq_hash_id] : []);
        $this->syncFaqToWhatsappNumbers($companyId, $validated['question'], $validated['answer'], $sourceUrl, $keywordList, $oldSourceIds);

        $faq->update($validated);

        return redirect()->route('faqs.index')->with('success', 'FAQ updated and synchronized successfully.');
    }

    public function destroy($id, \App\Services\FaqApiService $apiService)
    {
        $companyId = auth()->user()->company_id ?? auth()->user()->tenant_id;
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
        $companyId = auth()->user()->company_id ?? auth()->user()->tenant_id;
        $faq = Faq::where('tenant_id', $companyId)->findOrFail($id);
        
        $faq->update(['is_active' => !$faq->is_active]);
        return redirect()->route('faqs.index')->with('success', 'FAQ status updated successfully.');
    }

    /**
     * Converts rich text HTML from the editor into plain text with clickable links for WhatsApp.
     * WhatsApp does not support HTML tags like <a href="url">text</a>.
     * To make a link clickable in WhatsApp, the actual URL must appear in the text: e.g. "text (url)" or "url".
     */
    public static function formatHtmlForWhatsapp(string $html): string
    {
        // 1. Convert <a href="url">label</a> to label (url) or url
        $text = preg_replace_callback('/<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', function ($matches) {
            $url = trim($matches[1]);
            $label = trim(strip_tags($matches[2]));
            if (empty($url)) {
                return $label;
            }
            if (empty($label) || strcasecmp($label, $url) === 0) {
                return $url;
            }
            return "{$label} ({$url})";
        }, $html);

        // 2. Convert <br>, <p>, <div> to newlines
        $text = preg_replace('/<br\s*\/?>/i', "\n", $text);
        $text = preg_replace('/<\/p>\s*<p>/i', "\n\n", $text);
        $text = preg_replace('/<\/(p|div)>/i', "\n", $text);

        // 3. Convert <b>, <strong> to *bold* (WhatsApp formatting)
        $text = preg_replace('/<(b|strong)>(.*?)<\/(b|strong)>/is', '*$2*', $text);

        // 4. Convert <i>, <em> to _italic_ (WhatsApp formatting)
        $text = preg_replace('/<(i|em)>(.*?)<\/(i|em)>/is', '_$2_', $text);

        // 5. Decode entities and remove any remaining HTML tags
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = strip_tags($text);

        // 6. Clean up redundant blank lines
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        return trim($text);
    }

    /**
     * Sync a FAQ to each WhatsApp number's own per-number FAQ store on the Python side.
     * This is required because WhatsApp bot reply uses /whatsapp/numbers/{id}/bot/reply
     * which reads from the number's own store, NOT the shared company store.
     * Deduplicates by question name before uploading to prevent multiple copies.
     */
    private function syncFaqToWhatsappNumbers(int $companyId, string $question, string $answer, ?string $sourceUrl, array $keywords, ?array $oldSourceIds): void
    {
        $numbers = WhatsappNumber::where('company_id', $companyId)->get();
        if ($numbers->isEmpty()) {
            return;
        }

        $numApi = app(WhatsappNumberApiService::class);
        $plainAnswer = self::formatHtmlForWhatsapp($answer);

        foreach ($numbers as $number) {
            try {
                // Delete old sources (from previous save) from this number's store
                if (!empty($oldSourceIds)) {
                    foreach ($oldSourceIds as $oldId) {
                        $numApi->deleteFaqSource($number->phone_number_id, $oldId);
                    }
                }

                // DEDUPLICATION: list all existing sources and delete any with same question name
                $existingSources = $numApi->listFaqSources($number->phone_number_id);
                foreach ($existingSources as $source) {
                    $sourceName = $source['name'] ?? $source['title'] ?? '';
                    if (trim(strtolower($sourceName)) === trim(strtolower($question))) {
                        $sourceId = $source['id'] ?? $source['source_id'] ?? null;
                        if ($sourceId) {
                            $numApi->deleteFaqSource($number->phone_number_id, $sourceId);
                        }
                    }
                }

                // Upload the new/updated FAQ to this number's store
                $numApi->uploadFaqText(
                    $number->phone_number_id,
                    $question,
                    $plainAnswer,
                    $sourceUrl,
                    false,
                    $keywords
                );
            } catch (\Throwable $e) {
                Log::error('FaqController: failed to sync FAQ to WhatsApp number store', [
                    'phone_number_id' => $number->phone_number_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
