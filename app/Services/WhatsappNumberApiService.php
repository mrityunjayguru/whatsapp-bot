<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Talks to the Python bot API's /whatsapp/numbers/* endpoints (see
 * whatsapp_routes.py). Mirrors WidgetApiService exactly - same "Python's
 * own JSON storage is the source of truth, no mirrored Laravel table for
 * FAQs/bot-config" pattern - except this service ALSO manages each
 * number's bot persona (company name/links/products/templates) via the
 * /bot-config endpoints, since WhatsApp numbers don't fold that into the
 * same config object the way a widget's appearance settings do.
 *
 * Reuses the same services.bot_api config (url + key) as WidgetApiService/
 * BotConfigController - same Python server, just a different set of routes.
 */
class WhatsappNumberApiService
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.bot_api.url', env('CHATBOAT_URL', 'http://127.0.0.1:5000')), '/');
        $this->apiKey = (string) config('services.bot_api.key');
    }

    private function headers(): array
    {
        return $this->apiKey ? ['X-API-Key' => $this->apiKey] : [];
    }

    // -- numbers ---------------------------------------------------------

    public function listNumbers(): array
    {
        try {
            $response = Http::withHeaders($this->headers())->timeout(15)->get("{$this->baseUrl}/whatsapp/numbers");
            if ($response->successful()) {
                return $response->json() ?? [];
            }
            Log::error('WhatsappNumberApiService listNumbers failed', ['status' => $response->status(), 'body' => $response->body()]);
        } catch (\Throwable $e) {
            Log::error('WhatsappNumberApiService listNumbers exception', ['message' => $e->getMessage()]);
        }
        return [];
    }

    public function createNumber(string $phoneNumberId, ?string $displayNumber = null, ?string $label = null): ?array
    {
        $payload = ['phone_number_id' => $phoneNumberId];
        if ($displayNumber) {
            $payload['display_number'] = $displayNumber;
        }
        if ($label) {
            $payload['label'] = $label;
        }
        try {
            $response = Http::withHeaders($this->headers())->timeout(15)
                ->post("{$this->baseUrl}/whatsapp/numbers", $payload);
            if ($response->successful()) {
                return $response->json();
            }
            Log::error('WhatsappNumberApiService createNumber failed', ['status' => $response->status(), 'body' => $response->body()]);
        } catch (\Throwable $e) {
            Log::error('WhatsappNumberApiService createNumber exception', ['message' => $e->getMessage()]);
        }
        return null;
    }

    public function getNumberConfig(string $phoneNumberId): ?array
    {
        try {
            $response = Http::withHeaders($this->headers())->timeout(15)->get("{$this->baseUrl}/whatsapp/numbers/{$phoneNumberId}/config");
            if ($response->successful()) {
                return $response->json();
            }
            if ($response->status() !== 404) {
                Log::error('WhatsappNumberApiService getNumberConfig failed', ['status' => $response->status(), 'body' => $response->body()]);
            }
        } catch (\Throwable $e) {
            Log::error('WhatsappNumberApiService getNumberConfig exception', ['message' => $e->getMessage()]);
        }
        return null;
    }

    public function updateNumberConfig(string $phoneNumberId, array $data): ?array
    {
        try {
            $response = Http::withHeaders($this->headers())->timeout(15)
                ->put("{$this->baseUrl}/whatsapp/numbers/{$phoneNumberId}/config", $data);
            if ($response->successful()) {
                return $response->json();
            }
            Log::error('WhatsappNumberApiService updateNumberConfig failed', ['status' => $response->status(), 'body' => $response->body()]);
        } catch (\Throwable $e) {
            Log::error('WhatsappNumberApiService updateNumberConfig exception', ['message' => $e->getMessage()]);
        }
        return null;
    }

    public function deleteNumber(string $phoneNumberId): bool
    {
        try {
            $response = Http::withHeaders($this->headers())->timeout(15)->delete("{$this->baseUrl}/whatsapp/numbers/{$phoneNumberId}");
            if ($response->successful() || $response->status() === 404) {
                return true;
            }
            Log::error('WhatsappNumberApiService deleteNumber failed', ['status' => $response->status(), 'body' => $response->body()]);
        } catch (\Throwable $e) {
            Log::error('WhatsappNumberApiService deleteNumber exception', ['message' => $e->getMessage()]);
        }
        return false;
    }

    // -- per-number bot persona (company name/links/products/templates) -

    public function getBotConfig(string $phoneNumberId): ?array
    {
        try {
            $response = Http::withHeaders($this->headers())->timeout(15)->get("{$this->baseUrl}/whatsapp/numbers/{$phoneNumberId}/bot-config");
            if ($response->successful()) {
                return $response->json();
            }
            Log::error('WhatsappNumberApiService getBotConfig failed', ['status' => $response->status(), 'body' => $response->body()]);
        } catch (\Throwable $e) {
            Log::error('WhatsappNumberApiService getBotConfig exception', ['message' => $e->getMessage()]);
        }
        return null;
    }

    public function updateBotConfig(string $phoneNumberId, array $data): ?array
    {
        try {
            $response = Http::withHeaders($this->headers())->timeout(15)
                ->put("{$this->baseUrl}/whatsapp/numbers/{$phoneNumberId}/bot-config", $data);
            if ($response->successful()) {
                return $response->json();
            }
            Log::error('WhatsappNumberApiService updateBotConfig failed', ['status' => $response->status(), 'body' => $response->body()]);
        } catch (\Throwable $e) {
            Log::error('WhatsappNumberApiService updateBotConfig exception', ['message' => $e->getMessage()]);
        }
        return null;
    }

    // -- per-number FAQ knowledge base ------------------------------------

    public function listFaqSources(string $phoneNumberId): array
    {
        try {
            $response = Http::withHeaders($this->headers())->timeout(15)->get("{$this->baseUrl}/whatsapp/numbers/{$phoneNumberId}/faq/sources");
            if ($response->successful()) {
                return $response->json() ?? [];
            }
            Log::error('WhatsappNumberApiService listFaqSources failed', ['status' => $response->status(), 'body' => $response->body()]);
        } catch (\Throwable $e) {
            Log::error('WhatsappNumberApiService listFaqSources exception', ['message' => $e->getMessage()]);
        }
        return [];
    }

    public function getFaqSource(string $phoneNumberId, string $sourceId): ?array
    {
        try {
            $response = Http::withHeaders($this->headers())->timeout(15)->get("{$this->baseUrl}/whatsapp/numbers/{$phoneNumberId}/faq/sources/{$sourceId}");
            if ($response->successful()) {
                return $response->json();
            }
            if ($response->status() !== 404) {
                Log::error('WhatsappNumberApiService getFaqSource failed', ['status' => $response->status(), 'body' => $response->body()]);
            }
        } catch (\Throwable $e) {
            Log::error('WhatsappNumberApiService getFaqSource exception', ['message' => $e->getMessage()]);
        }
        return null;
    }

    public function uploadFaqText(string $phoneNumberId, string $name, string $text, ?string $sourceUrl = null, bool $sendAsLink = false, ?array $keywords = null, bool $isActive = true, ?string $linkText = null, ?string $attachmentUrl = null): ?array
    {
        $payload = ['name' => $name, 'text' => $text, 'send_as_link' => $sendAsLink, 'is_active' => $isActive];
        if ($sourceUrl) {
            $payload['source_url'] = $sourceUrl;
        }
        if (!empty($keywords)) {
            $payload['keywords'] = $keywords;
        }
        if ($linkText && $sourceUrl) {
            $payload['link_text'] = $linkText;
        }
        if ($attachmentUrl) {
            $payload['attachment_url'] = $attachmentUrl;
        }

        try {
            $response = Http::withHeaders($this->headers())->timeout(15)
                ->post("{$this->baseUrl}/whatsapp/numbers/{$phoneNumberId}/faq/upload/text", $payload);
            if ($response->successful()) {
                return $response->json();
            }
            Log::error('WhatsappNumberApiService uploadFaqText failed', ['status' => $response->status(), 'body' => $response->body()]);
        } catch (\Throwable $e) {
            Log::error('WhatsappNumberApiService uploadFaqText exception', ['message' => $e->getMessage()]);
        }
        return null;
    }

    public function updateFaqSource(string $phoneNumberId, string $sourceId, string $name, string $text, ?string $sourceUrl = null, bool $sendAsLink = false, ?array $keywords = null, bool $isActive = true, ?string $linkText = null, ?string $attachmentUrl = null): ?array
    {
        $payload = ['name' => $name, 'text' => $text, 'send_as_link' => $sendAsLink, 'is_active' => $isActive];
        if ($sourceUrl) {
            $payload['source_url'] = $sourceUrl;
        }
        if (!empty($keywords)) {
            $payload['keywords'] = $keywords;
        }
        if ($linkText && $sourceUrl) {
            $payload['link_text'] = $linkText;
        }
        // Omitted entirely (not sent as null) when no NEW file was
        // attached - same "keep whatever was already there" contract as
        // WidgetApiService::updateFaqSource(). See faq_store.py's
        // update_source().
        if ($attachmentUrl !== null) {
            $payload['attachment_url'] = $attachmentUrl;
        }

        try {
            $response = Http::withHeaders($this->headers())->timeout(15)
                ->put("{$this->baseUrl}/whatsapp/numbers/{$phoneNumberId}/faq/sources/{$sourceId}", $payload);
            if ($response->successful()) {
                return $response->json();
            }
            Log::error('WhatsappNumberApiService updateFaqSource failed', ['status' => $response->status(), 'body' => $response->body()]);
        } catch (\Throwable $e) {
            Log::error('WhatsappNumberApiService updateFaqSource exception', ['message' => $e->getMessage()]);
        }
        return null;
    }

    public function attachFile(string $phoneNumberId, string $filePath, string $filename): ?array
    {
        try {
            $response = Http::withHeaders($this->headers())->timeout(30)
                ->attach('file', file_get_contents($filePath), $filename)
                ->post("{$this->baseUrl}/whatsapp/numbers/{$phoneNumberId}/faq/files/attach");
            if ($response->successful()) {
                return $response->json();
            }
            Log::error('WhatsappNumberApiService attachFile failed', ['status' => $response->status(), 'body' => $response->body()]);
        } catch (\Throwable $e) {
            Log::error('WhatsappNumberApiService attachFile exception', ['message' => $e->getMessage()]);
        }
        return null;
    }

    public function deleteFaqSource(string $phoneNumberId, string $sourceId): bool
    {
        try {
            $response = Http::withHeaders($this->headers())->timeout(15)
                ->delete("{$this->baseUrl}/whatsapp/numbers/{$phoneNumberId}/faq/sources/{$sourceId}");
            if ($response->successful() || $response->status() === 404) {
                return true;
            }
            Log::error('WhatsappNumberApiService deleteFaqSource failed', ['status' => $response->status(), 'body' => $response->body()]);
        } catch (\Throwable $e) {
            Log::error('WhatsappNumberApiService deleteFaqSource exception', ['message' => $e->getMessage()]);
        }
        return false;
    }
}
