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
        $companyId = auth()->user()->company_id;
        $botConfig = \App\Models\BotConfig::where('tenant_id', $companyId)->first();
        $config = $botConfig ? $botConfig->payload : $this->getDefaultConfig();

        return view('admin.bot-config', [
            'config' => $config,
        ]);
    }

    public function update(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'support_link' => 'required|string|max:500',
            'demo_link' => 'required|string|max:500',
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

        // Attempt to sync to Python
        try {
            $response = Http::withHeaders($this->authHeaders())
                ->timeout(10)
                ->put($this->apiUrl('/bot/config'), $validated);

            if ($response->failed()) {
                Log::error('BotConfig: failed to save config to Python API', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return back()->with('error', 'Saved locally, but failed to sync to Bot API (API returned ' . $response->status() . '). Please ensure the bot service is running.');
            }
        } catch (\Exception $e) {
            Log::error('BotConfig: Exception saving config to Python API', ['error' => $e->getMessage()]);
            return back()->with('error', 'Saved locally, but could not connect to Python Bot. Please check if the bot is online.');
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
