<?php

namespace App\Http\Controllers;

use App\Models\BotConfig;
use App\Models\Company;
use App\Models\WhatsappNumber;
use App\Services\WhatsappNumberApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BotConfigController extends Controller
{
    private const KEYS = [
        'greeting', 'human_handoff', 'fallback',
    ];

    private function companyFor(Request $request): Company
    {
        $ownId = auth()->user()->company_id;
        // Company users must never edit another tenant's settings.
        $id = $ownId ?: $request->input('company_id');
        abort_unless($id && ctype_digit((string) $id), 403, 'Select a company.');
        return Company::findOrFail($id);
    }

    private function defaults(Company $company): array
    {
        return [
            'company_name' => $company->name,
            'support_link' => '', 'demo_link' => '', 'products_link' => '',
            'products' => [],
            'templates' => [
                'greeting' => 'Hi! Welcome to {company_name}. How can I help you?',
                'human_handoff' => 'Connecting you with our team. Please wait.',
                'fallback' => "Sorry, I couldn't find an answer. Please rephrase your question.",
                'widget_greeting' => 'Hi! Welcome to {company_name}. How can I help you?',
                'widget_human_handoff' => 'Connecting you with our team. Please wait.',
                'widget_fallback' => "Sorry, I couldn't find an answer. Would you like human support?",
            ],
        ];
    }

    public function edit(Request $request)
    {
        $isCompanyUser = !is_null(auth()->user()->company_id);
        $companies = $isCompanyUser ? collect() : Company::orderBy('name')->get();
        $selectedCompany = $isCompanyUser
            ? Company::findOrFail(auth()->user()->company_id)
            : ($request->filled('company_id') ? Company::findOrFail($request->input('company_id')) : null);
        $config = null;
        if ($selectedCompany) {
            $stored = BotConfig::where('tenant_id', $selectedCompany->id)->first()?->payload ?? [];
            $defaults = $this->defaults($selectedCompany);
            $config = array_replace($defaults, $stored);
            $config['templates'] = array_replace($defaults['templates'], $stored['templates'] ?? []);
        }
        return view('admin.bot-config', compact('config', 'companies', 'selectedCompany', 'isCompanyUser'));
    }

    public function update(Request $request, WhatsappNumberApiService $api)
    {
        $company = $this->companyFor($request);
        $rules = [];
        foreach (self::KEYS as $key) {
            $rules["messages.$key"] = 'required|string|max:4000';
        }
        $validated = $request->validate($rules);
        $row = BotConfig::firstOrNew(['tenant_id' => $company->id]);
        $payload = array_replace($this->defaults($company), $row->payload ?? []);
        $payload['templates'] = array_replace(
            $this->defaults($company)['templates'],
            $payload['templates'] ?? [],
            $validated['messages']
        );
        // Keep all unrelated configuration and templates intact.
        $payload['company_name'] = $payload['company_name'] ?: $company->name;

        // Sync to Python BEFORE saving Laravel so a transport/API failure
        // does not show success while the live bot still has stale settings.
        try {
            $url = rtrim((string) config('services.bot_api.url', 'http://127.0.0.1:5000'), '/')
                . '/company/' . $company->id . '/bot/config';
            $key = (string) config('services.bot_api.key', '');
            $response = Http::withHeaders($key ? ['X-API-Key' => $key] : [])
                ->timeout(15)->put($url, $payload);
            if (!$response->successful()) {
                Log::error('Bot settings sync failed', ['status' => $response->status(), 'body' => $response->body()]);
                return back()->withInput()->with('error', 'Python bot rejected the update (HTTP ' . $response->status() . '). Nothing was saved.');
            }
        } catch (\Throwable $e) {
            Log::error('Bot settings API unavailable', ['message' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Python API unavailable. Nothing was saved.');
        }

        $row->payload = $payload;
        $row->save();

        // Mirror to per-number configs for other legacy consumers. Live
        // WhatsApp bot currently reads the company-level Python store.
        $warnings = [];
        foreach (WhatsappNumber::where('company_id', $company->id)->get() as $number) {
            try {
                self::syncNumber($number, $api, $payload);
            } catch (\Throwable $e) {
                Log::warning('Number mirror failed', ['number' => $number->phone_number_id, 'message' => $e->getMessage()]);
                $warnings[] = $number->display_number ?: $number->phone_number_id;
            }
        }
        return redirect()->route('bot-config.edit', $isQuery = auth()->user()->company_id ? [] : ['company_id' => $company->id])
            ->with('success', 'Bot messages saved.' .
                ($warnings ? ' Warning: number mirrors failed: ' . implode(', ', $warnings) : ''));
    }

    private static function syncNumber(WhatsappNumber $number, WhatsappNumberApiService $api, array $payload): void
    {
        $api->updateBotConfig($number->phone_number_id, $payload);
    }
}
