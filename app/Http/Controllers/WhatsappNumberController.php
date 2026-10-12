<?php

namespace App\Http\Controllers;

use App\Models\BotConfig;
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
    public static function syncBotConfigForNumber(WhatsappNumber $number, WhatsappNumberApiService $api): void
    {
        $botConfigRow = BotConfig::where('tenant_id', $number->company_id)->first();
        $company = $number->company;

        $templates = [
            'greeting' => $botConfigRow?->payload['templates']['greeting']
                ?? 'Hi {greeting_prefix} {name}! 👋 Welcome to {company_name}!',
            'fallback' => $botConfigRow?->payload['templates']['fallback']
                ?? "I'm sorry, I couldn't find a direct answer for that. Can you rephrase?",
            'demo_booking' => $botConfigRow?->payload['templates']['demo_booking']
                ?? 'Sure! Click here to book a demo at your convenience: {demo_link}',
            'human_handoff' => $botConfigRow?->payload['templates']['human_handoff']
                ?? "Sure! I'll notify a human agent right away. They'll be with you shortly.",
            'invalid_booking_date' => $botConfigRow?->payload['templates']['invalid_booking_date']
                ?? 'Please pick a date within the next 30 days.',
            'booking_confirmed' => $botConfigRow?->payload['templates']['booking_confirmed']
                ?? "✅ Demo booked for {date} at {time}!\nYou'll get a confirmation email at {email} shortly. 📧",
        ];

        $companyName = $botConfigRow?->payload['company_name'] ?? ($company?->name ?? 'My Company');
        $supportLink = $botConfigRow?->payload['support_link'] ?? '';
        $demoLink = $botConfigRow?->payload['demo_link'] ?? '';
        $productsLink = $botConfigRow?->payload['products_link'] ?? '';
        $products = $botConfigRow?->payload['products'] ?? [];
        if (!is_array($products)) {
            $products = [];
        }

        $api->updateBotConfig($number->phone_number_id, [
            'company_name' => $companyName,
            'support_link' => $supportLink,
            'demo_link' => $demoLink,
            'products_link' => $productsLink,
            'products' => $products,
            'templates' => $templates,
        ]);
    }
    public function index()
    {
        $numbers = WhatsappNumber::with('company')->orderByDesc('id')->get();

        if (!is_null((auth()->user()->company_id ?? auth()->user()->tenant_id))) {
            $numbers = $numbers->where('company_id', (auth()->user()->company_id ?? auth()->user()->tenant_id))->values();
        }

        return view('whatsapp_numbers.index', compact('numbers'));
    }

    public function create()
    {
        $companies = Company::where('bot_usage_type', 'whatsapp')->get();
        return view('whatsapp_numbers.create', compact('companies'));
    }

    public function store(Request $request, WhatsappNumberApiService $api)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'phone_number_id' => 'nullable|string|max:64|unique:whatsapp_numbers,phone_number_id',
            'display_number' => 'nullable|string|max:40',
            'waba_id' => 'nullable|string|max:64',
            'access_token' => 'nullable|string',
            'graph_version' => 'nullable|string|max:20',
            'webhook_verify_token' => 'nullable|string|max:255',
            'label' => 'nullable|string|max:255',
            'valid_from' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:valid_from',
        ]);

        $phoneNumberId = !empty($validated['phone_number_id']) ? $validated['phone_number_id'] : (string) (time() . rand(100, 999));

        $verifyToken = $validated['webhook_verify_token'] ?? null;
        if (empty($verifyToken)) {
            $verifyToken = \Illuminate\Support\Str::random(32);
        }

        $number = WhatsappNumber::create([
            'company_id' => $validated['company_id'],
            'phone_number_id' => $phoneNumberId,
            'waba_id' => $validated['waba_id'] ?? null,
            'display_number' => $validated['display_number'] ?? null,
            'label' => $validated['label'] ?? null,
            'access_token' => $validated['access_token'] ?? null,
            'graph_version' => $validated['graph_version'] ?: 'v23.0',
            'webhook_verify_token' => $verifyToken,
            'is_active' => true,
            'valid_from' => $validated['valid_from'] ?? now()->format('Y-m-d'),
            'expiry_date' => $validated['expiry_date'] ?? now()->addMonths(2)->format('Y-m-d'),
        ]);

        $api->createNumber(
            $number->phone_number_id,
            $number->display_number ?? null,
            $number->label ?? null
        );

        $api->updateNumberConfig($number->phone_number_id, [
            'company_id' => $number->company_id,
            'display_number' => $number->display_number ?? '',
            'label' => $number->label ?? '',
            'is_active' => $number->is_active,
            'valid_from' => $number->valid_from ? $number->valid_from->format('Y-m-d') : null,
            'expiry_date' => $number->expiry_date ? $number->expiry_date->format('Y-m-d') : null,
        ]);

        // Seed the new number's bot persona (greeting, fallback,
        // company_name, links, products) from the company's BotConfig row
        // (or sensible defaults) right now, so the very first WhatsApp
        // message it receives already shows the custom greeting instead
        // of Track Route Pro's shared default.
        try {
            self::syncBotConfigForNumber($number, $api);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[WhatsappNumberController@store] Initial bot-persona sync failed', [
                'phone_number_id' => $number->phone_number_id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route('whatsapp-numbers.edit', $number->phone_number_id)
            ->with('success', 'WhatsApp number registered. Configure its bot persona and add some FAQs below.');
    }

    public function edit(string $phoneNumberId, WhatsappNumberApiService $api)
    {
        $number = WhatsappNumber::where('phone_number_id', $phoneNumberId)->firstOrFail();
        $faqs = $api->listFaqSources($phoneNumberId);
        $isCompanyUser = !is_null((auth()->user()->company_id ?? auth()->user()->tenant_id));
        $companies = $isCompanyUser ? collect() : Company::where('bot_usage_type', 'whatsapp')->get();
        $botConfig = $api->getBotConfig($phoneNumberId);

        return view('whatsapp_numbers.edit', compact('number', 'faqs', 'companies', 'isCompanyUser', 'botConfig'));
    }

    public function updateConfig(Request $request, string $phoneNumberId, WhatsappNumberApiService $api)
    {
        $number = WhatsappNumber::where('phone_number_id', $phoneNumberId)->firstOrFail();
        $isCompanyUser = !is_null((auth()->user()->company_id ?? auth()->user()->tenant_id));

        $rules = [
            'phone_number_id' => 'required|string|max:64|unique:whatsapp_numbers,phone_number_id,' . $number->id,
            'display_number' => 'nullable|string|max:40',
            'waba_id' => 'nullable|string|max:64',
            'label' => 'nullable|string|max:255',
            'access_token' => 'nullable|string',
            'graph_version' => 'nullable|string|max:20',
            'webhook_verify_token' => 'nullable|string|max:255',
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
        } elseif ($isCompanyUser && is_null($number->company_id)) {
            $number->company_id = auth()->user()->company_id ?? auth()->user()->tenant_id;
        }
        if (false) {
            $number->company_id = $request->input('company_id') ?: null;
        }

        $number->display_number = $validated['display_number'] ?? null;
        $number->waba_id = $validated['waba_id'] ?? null;
        $number->label = $validated['label'] ?? null;
        $number->graph_version = $validated['graph_version'] ?: 'v23.0';
        if (!empty($validated['webhook_verify_token'])) {
            $number->webhook_verify_token = $validated['webhook_verify_token'];
        } elseif (empty($number->webhook_verify_token)) {
            $number->webhook_verify_token = \Illuminate\Support\Str::random(32);
        }
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
        $newPhoneNumberId = $validated['phone_number_id'];
        $phoneNumberIdChanged = ($phoneNumberId !== $newPhoneNumberId);

        if ($phoneNumberIdChanged) {
            $number->phone_number_id = $newPhoneNumberId;
        }

        $number->save();

        if ($phoneNumberIdChanged) {
            // Tell Python to create the new one and delete the old one
            $api->createNumber($newPhoneNumberId, $number->display_number ?? null, $number->label ?? null);
            $api->deleteNumber($phoneNumberId);
        }

        $api->updateNumberConfig($newPhoneNumberId, [
            'company_id' => $number->company_id,
            'display_number' => $number->display_number ?? '',
            'label' => $number->label ?? '',
            'is_active' => $number->is_active,
            'valid_from' => $validated['valid_from'] ?? null,
            'expiry_date' => $validated['expiry_date'] ?? null,
        ]);

        // Whenever number-level settings change (especially if company_id
        // was swapped to a different company), re-sync the bot persona
        // from the BotConfig row too so greeting/fallback/links stay in
        // sync on the Python side.
        try {
            self::syncBotConfigForNumber($number, $api);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[WhatsappNumberController@updateConfig] Bot-persona sync failed', [
                'phone_number_id' => $newPhoneNumberId,
                'error' => $e->getMessage(),
            ]);
        }

        if ($phoneNumberIdChanged) {
            return redirect()->route('whatsapp-numbers.edit', $newPhoneNumberId)->with('success', 'WhatsApp number settings saved and Phone Number ID updated.');
        }

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

    public function updateMessages(Request $request, string $phoneNumberId, WhatsappNumberApiService $api)
    {
        $validated = $request->validate([
            'greeting' => 'required|string',
            'fallback' => 'required|string',
        ]);

        $config = $api->getBotConfig($phoneNumberId);
        if (!$config) {
            return back()->with('error', 'Could not load bot config.');
        }

        $config['templates']['greeting'] = $validated['greeting'];
        $config['templates']['fallback'] = $validated['fallback'];

        $result = $api->updateBotConfig($phoneNumberId, $config);
        if (!$result) {
            return back()->withInput()->with('error', 'Could not save messages - check the bot service is running.');
        }

        return back()->with('success', 'Greeting and Fallback messages updated successfully.');
    }

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
