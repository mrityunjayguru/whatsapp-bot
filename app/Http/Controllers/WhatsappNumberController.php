<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\WhatsappNumber;
use App\Services\WhatsappNumberApiService;
use Illuminate\Http\Request;

/**
 * CRM management for client WhatsApp Business numbers - the WhatsApp
 * equivalent of WidgetController. Unlike widgets (which keep NO local
 * DB row at all - Python's own JSON is the sole source of truth), each
 * WhatsApp number DOES get a local `whatsapp_numbers` row, because it
 * has to hold a real secret (the Meta access_token) that Python never
 * sees or stores (see main.py's docstring: this service never talks to
 * WhatsApp directly). Availability (is_active/valid_from/expiry_date) is
 * intentionally stored on BOTH sides, same as the widgets fix: Laravel's
 * row is authoritative and is what MetaWebhookController checks before
 * ever forwarding a message, Python's own copy is a defense-in-depth
 * check on its /bot/reply endpoint in case something calls it directly.
 */
class WhatsappNumberController extends Controller
{
    public function index()
    {
        $numbers = WhatsappNumber::with('company')->orderByDesc('id')->get();

        if (!is_null(auth()->user()->company_id)) {
            $numbers = $numbers->where('company_id', auth()->user()->company_id)->values();
        }

        return view('whatsapp_numbers.index', compact('numbers'));
    }

    public function create()
    {
        $companies = Company::all();
        return view('whatsapp_numbers.create', compact('companies'));
    }

    public function store(Request $request, WhatsappNumberApiService $api)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'phone_number_id' => 'required|string|max:64|unique:whatsapp_numbers,phone_number_id',
            'display_number' => 'nullable|string|max:40',
            'waba_id' => 'nullable|string|max:64',
            'access_token' => 'required|string',
            'graph_version' => 'nullable|string|max:20',
            'label' => 'nullable|string|max:255',
            'valid_from' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:valid_from',
        ]);

        // Register this number's own FAQ store + bot persona on the
        // Python side FIRST - if the bot service is down, nothing gets
        // created on either side (same ordering as WidgetController::store()).
        $registered = $api->createNumber($validated['phone_number_id'], $validated['display_number'] ?? null, $validated['label'] ?? null);
        if (!$registered) {
            return back()->withInput()->with('error', 'Could not register this number with the bot service - check it is running.');
        }

        $number = WhatsappNumber::create([
            'company_id' => $validated['company_id'],
            'phone_number_id' => $validated['phone_number_id'],
            'waba_id' => $validated['waba_id'] ?? null,
            'display_number' => $validated['display_number'] ?? null,
            'label' => $validated['label'] ?? null,
            'access_token' => $validated['access_token'],
            'graph_version' => $validated['graph_version'] ?: 'v23.0',
            'is_active' => true,
            'valid_from' => $validated['valid_from'] ?? null,
            'expiry_date' => $validated['expiry_date'] ?? null,
        ]);

        // Mirror the active window onto the Python side too - same
        // reasoning as WidgetController::store()'s call to
        // updateWidgetConfig() right after creating a widget.
        $api->updateNumberConfig($number->phone_number_id, [
            'valid_from' => $validated['valid_from'] ?? null,
            'expiry_date' => $validated['expiry_date'] ?? null,
        ]);

        return redirect()->route('whatsapp-numbers.edit', $number->phone_number_id)
            ->with('success', 'WhatsApp number registered. Configure its bot persona and add some FAQs below.');
    }

    public function edit(string $phoneNumberId, WhatsappNumberApiService $api)
    {
        $number = WhatsappNumber::where('phone_number_id', $phoneNumberId)->firstOrFail();
        $faqs = $api->listFaqSources($phoneNumberId);
        $isCompanyUser = !is_null(auth()->user()->company_id);
        $companies = $isCompanyUser ? collect() : Company::all();

        return view('whatsapp_numbers.edit', compact('number', 'faqs', 'companies', 'isCompanyUser'));
    }

    public function updateConfig(Request $request, string $phoneNumberId, WhatsappNumberApiService $api)
    {
        $number = WhatsappNumber::where('phone_number_id', $phoneNumberId)->firstOrFail();
        $isCompanyUser = !is_null(auth()->user()->company_id);

        $rules = [
            'display_number' => 'nullable|string|max:40',
            'waba_id' => 'nullable|string|max:64',
            'label' => 'nullable|string|max:255',
            'access_token' => 'nullable|string',
            'graph_version' => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
            'valid_from' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:valid_from',
        ];
        if (!$isCompanyUser) {
            $rules['company_id'] = 'nullable|exists:companies,id';
        }
        $validated = $request->validate($rules);

        if (!$isCompanyUser && $request->has('company_id')) {
            $number->company_id = $request->input('company_id') ?: null;
        }

        $number->display_number = $validated['display_number'] ?? null;
        $number->waba_id = $validated['waba_id'] ?? null;
        $number->label = $validated['label'] ?? null;
        $number->graph_version = $validated['graph_version'] ?: 'v23.0';
        // Blank access_token means "leave the existing secret alone" -
        // it's never pre-filled back into the edit form (same convention
        // as any password-style field), so there's no way to tell "left
        // untouched" from "deliberately cleared" the way there is for
        // valid_from/expiry_date below.
        if (!empty($validated['access_token'])) {
            $number->access_token = $validated['access_token'];
        }
        $number->is_active = $request->has('is_active');
        // Always clears on blank, same as Widget's valid_from/expiry_date
        // fix - these fields ARE always pre-filled, so a blank submission
        // is a deliberate clear, never "left untouched".
        $number->valid_from = $validated['valid_from'] ?? null;
        $number->expiry_date = $validated['expiry_date'] ?? null;
        $number->save();

        $api->updateNumberConfig($phoneNumberId, [
            'display_number' => $number->display_number ?? '',
            'label' => $number->label ?? '',
            'is_active' => $number->is_active,
            'valid_from' => $validated['valid_from'] ?? null,
            'expiry_date' => $validated['expiry_date'] ?? null,
        ]);

        return back()->with('success', 'WhatsApp number settings saved.');
    }

    public function destroy(string $phoneNumberId, WhatsappNumberApiService $api)
    {
        $number = WhatsappNumber::where('phone_number_id', $phoneNumberId)->first();
        $api->deleteNumber($phoneNumberId);
        if ($number) {
            $number->delete();
        }
        return redirect()->route('whatsapp-numbers.index')->with('success', 'WhatsApp number permanently removed, including all of its FAQs and bot config.');
    }

    // -- bot persona (company name/links/products/canned replies) --------

    public function editBotConfig(string $phoneNumberId, WhatsappNumberApiService $api)
    {
        $number = WhatsappNumber::where('phone_number_id', $phoneNumberId)->firstOrFail();
        $config = $api->getBotConfig($phoneNumberId);
        if (!$config) {
            return redirect()->route('whatsapp-numbers.edit', $phoneNumberId)->with('error', "Could not load this number's bot config - check the bot service is running.");
        }
        return view('whatsapp_numbers.bot-config', compact('number', 'config'));
    }

    public function updateBotConfig(Request $request, string $phoneNumberId, WhatsappNumberApiService $api)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'support_link' => 'required|string|max:500',
            'demo_link' => 'nullable|string|max:500',
            'products_link' => 'required|string|max:500',
            'products' => 'required|array',
            'products.*.key' => 'required|string|max:100',
            'products.*.name' => 'required|string|max:255',
            'products.*.type' => 'required|string|max:255',
            'products.*.price' => 'required|string|max:255',
            'products.*.link' => 'required|string|max:500',
            'products.*.keywords' => 'array',
            'products.*.keywords.*' => 'string|max:100',
            'templates' => 'required|array',
        ]);

        $result = $api->updateBotConfig($phoneNumberId, $validated);
        if (!$result) {
            return back()->withInput()->with('error', 'Could not save - check the bot service is running.');
        }

        return back()->with('success', "Bot config saved. Live on this number's very next customer message.");
    }

    // -- FAQs --------------------------------------------------------------

    public function storeFaq(Request $request, string $phoneNumberId, WhatsappNumberApiService $api)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
            'attachment' => 'nullable|file|max:10240',
            'url' => 'nullable|url|max:255',
            'link_text' => 'nullable|string|max:100',
            'keywords' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $sourceUrl = !empty($validated['url']) ? $validated['url'] : null;
        $attachmentUrl = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachResult = $api->attachFile($phoneNumberId, $file->getPathname(), $file->getClientOriginalName());
            if ($attachResult && isset($attachResult['url'])) {
                $attachmentUrl = $attachResult['url'];
            }
        }

        $keywordList = !empty($validated['keywords'])
            ? array_values(array_filter(array_map('trim', explode(',', $validated['keywords']))))
            : [];

        $isActive = $request->has('is_active');
        $linkText = !empty($validated['link_text']) ? $validated['link_text'] : null;

        $result = $api->uploadFaqText($phoneNumberId, $validated['question'], $validated['answer'], $sourceUrl, false, $keywordList, $isActive, $linkText, $attachmentUrl);

        if (!$result) {
            return back()->withInput()->with('error', 'Could not save that FAQ - check the bot service is running.');
        }

        return back()->with('success', 'FAQ added.');
    }

    public function editFaq(string $phoneNumberId, string $sourceId, WhatsappNumberApiService $api)
    {
        $number = WhatsappNumber::where('phone_number_id', $phoneNumberId)->firstOrFail();
        $faq = $api->getFaqSource($phoneNumberId, $sourceId);
        if (!$faq) {
            return redirect()->route('whatsapp-numbers.edit', $phoneNumberId)->with('error', 'FAQ not found.');
        }
        return view('whatsapp_numbers.faq-edit', compact('number', 'faq', 'sourceId', 'phoneNumberId'));
    }

    public function updateFaq(Request $request, string $phoneNumberId, string $sourceId, WhatsappNumberApiService $api)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
            'attachment' => 'nullable|file|max:10240',
            'url' => 'nullable|url|max:255',
            'link_text' => 'nullable|string|max:100',
            'keywords' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $faq = $api->getFaqSource($phoneNumberId, $sourceId);
        if (!$faq) {
            return redirect()->route('whatsapp-numbers.edit', $phoneNumberId)->with('error', 'FAQ not found.');
        }

        // Hyperlink URL/Link Text ARE always pre-filled on the edit form,
        // so blank means deliberately cleared - same fix as WidgetController.
        $submittedUrl = !empty($validated['url']) ? $validated['url'] : null;
        $sourceUrl = $submittedUrl;
        // null = "no new file this time, keep whatever attachment this
        // FAQ already had" - the Attachment file input is never
        // pre-filled, so there's no way to distinguish "untouched" from
        // "cleared" the way there is for url/link_text above.
        $attachmentUrl = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachResult = $api->attachFile($phoneNumberId, $file->getPathname(), $file->getClientOriginalName());
            if ($attachResult && isset($attachResult['url'])) {
                $attachmentUrl = $attachResult['url'];
            }
        }

        $keywordList = !empty($validated['keywords'])
            ? array_values(array_filter(array_map('trim', explode(',', $validated['keywords']))))
            : [];

        $isActive = $request->has('is_active');
        $linkText = !empty($validated['link_text']) ? $validated['link_text'] : null;

        $result = $api->updateFaqSource($phoneNumberId, $sourceId, $validated['question'], $validated['answer'], $sourceUrl, false, $keywordList, $isActive, $linkText, $attachmentUrl);

        if (!$result) {
            return redirect()->route('whatsapp-numbers.edit', $phoneNumberId)->with('error', 'Could not update FAQ - check the bot service is running.');
        }

        return redirect()->route('whatsapp-numbers.edit', $phoneNumberId)->with('success', 'FAQ updated.');
    }

    public function destroyFaq(string $phoneNumberId, string $sourceId, WhatsappNumberApiService $api)
    {
        $api->deleteFaqSource($phoneNumberId, $sourceId);
        return back()->with('success', 'FAQ removed.');
    }
}
