<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Settings page for the WhatsApp bot's name, links, products, and canned
 * replies. This controller doesn't store any of that itself - it's a thin
 * proxy to the Python bot API (see bot_config.py / main.py on the Python
 * side). Laravel's job here is just: show the form, and forward whatever
 * the CRM user saves to the Python service.
 *
 * Config used (add to config/services.php - see services_php_ADDITION.php):
 *   services.bot_api.url  - e.g. http://127.0.0.1:5000  (talk to Python
 *                            directly over localhost, not through the
 *                            public /pybot/ nginx path - faster and one
 *                            less thing exposed to the internet)
 *   services.bot_api.key  - shared secret, must match BOT_CONFIG_API_KEY
 *                            in the Python app's environment
 */
class BotConfigController extends Controller
{
    private function getDefaultConfig()
    {
        return [
            'company_name' => 'Default Company',
            'support_link' => 'https://example.com/support',
            'demo_link' => 'https://example.com/demo',
            'products_link' => 'https://example.com/products',
            'products' => [],
            'templates' => [],
        ];
    }

    public function edit()
    {
        $companyId = (auth()->user()->company_id ?? auth()->user()->tenant_id);
        $botConfig = \App\Models\BotConfig::where('tenant_id', $companyId)->first();
        $config = $botConfig ? $botConfig->payload : $this->getDefaultConfig();

        return view('admin.bot-config', [
            'config' => $config,
        ]);
    }

    public function update(Request $request, \App\Services\WhatsappNumberApiService $api)
    {
        $companyId = (auth()->user()->company_id ?? auth()->user()->tenant_id);
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'support_link' => 'required|string|max:500',
            'demo_link' => 'required|string|max:500',
            'products_link' => 'nullable|string|max:500',
            'products' => 'nullable|array',
            'products.*.key' => 'required|string|max:100',
            'products.*.name' => 'required|string|max:255',
            'products.*.type' => 'required|string|max:255',
            'products.*.price' => 'required|string|max:255',
            'products.*.link' => 'required|string|max:500',
            'products.*.keywords' => 'array',
            'products.*.keywords.*' => 'string|max:100',
            'templates' => 'required|array',
        ]);

        // Save to DB first
        $botConfig = \App\Models\BotConfig::where('tenant_id', $companyId)->first();
        if (!$botConfig) {
            $botConfig = new \App\Models\BotConfig();
            $botConfig->tenant_id = $companyId;
        }
        $botConfig->payload = $validated;
        $botConfig->save();

        // Include tenant_id in validated data before sending to Python
        $validated['tenant_id'] = $companyId;
        
        // Ensure products is an array
        $validated['products'] = $validated['products'] ?? [];

        $errors = [];

        // Attempt to sync to Python (widget / company-level bot)
        try {
            $response = Http::withHeaders($this->authHeaders())
                ->timeout(10)
                ->put($this->apiUrl("/company/{$companyId}/bot/config"), $validated);

            if ($response->failed()) {
                Log::error('BotConfig: failed to save config to Python API', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                $errors[] = 'Widget/company bot sync failed (API returned ' . $response->status() . ').';
            }
        } catch (\Exception $e) {
            Log::error('BotConfig: Exception saving config to Python API', ['error' => $e->getMessage()]);
            $errors[] = 'Widget/company bot sync: could not connect to Python Bot.';
        }

        // Also sync the updated BotConfig out to EVERY WhatsApp number
        // registered under this company.  Without this, the Admin Bot
        // Settings page (greeting, fallback, company_name, links etc.)
        // would only take effect on the widget side, and WhatsApp users
        // would keep seeing the old Track Route Pro / hardcoded defaults
        // forever - exactly the bug reported by the user.
        $companyNumbers = \App\Models\WhatsappNumber::where('company_id', $companyId)->get();
        foreach ($companyNumbers as $number) {
            try {
                // Ensure the number has the company_id synced
                $api->updateNumberConfig($number->phone_number_id, [
                    'company_id' => $number->company_id,
                    'display_number' => $number->display_number ?? '',
                    'label' => $number->label ?? '',
                    'is_active' => $number->is_active,
                    'valid_from' => $number->valid_from ? $number->valid_from->format('Y-m-d') : null,
                    'expiry_date' => $number->expiry_date ? $number->expiry_date->format('Y-m-d') : null,
                ]);
                WhatsappNumberController::syncBotConfigForNumber($number, $api);
            } catch (\Throwable $e) {
                Log::error('BotConfig: failed to sync bot config to WhatsApp number', [
                    'phone_number_id' => $number->phone_number_id,
                    'error' => $e->getMessage(),
                ]);
                $errors[] = "WhatsApp number {$number->display_number} bot sync failed.";
            }
        }

        if (!empty($errors)) {
            return back()->with('error', 'Saved locally. Issues: ' . implode(' ', $errors));
        }

        return back()->with('success', 'Bot config saved. Live on the next customer message.');
    }



    private function apiUrl(string $path): string
    {
        return rtrim(config('services.bot_api.url'), '/') . $path;
    }

    private function authHeaders(): array
    {
        $key = config('services.bot_api.key');
        return $key ? ['X-API-Key' => $key] : [];
    }
}
