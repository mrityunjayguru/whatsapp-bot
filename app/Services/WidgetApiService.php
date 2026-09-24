<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Talks to the Python bot API's /widgets/* endpoints (see widget_routes.py).
 * Deliberately does NOT keep a mirrored Laravel-side table for widgets or
 * their FAQs - Python's own JSON storage (one file per widget) is the
 * single source of truth, the same way faq_store.py already is for the
 * WhatsApp bot's FAQs. Keeping a second copy in Laravel's DB is exactly
 * what caused the FaqController/faq_store drift we had to clean up
 * earlier - this service avoids repeating that by never storing a copy.
 *
 * Reuses the same services.bot_api config (url + key) as
 * BotConfigController/FaqApiService - same Python server, just a
 * different set of routes on it.
 */
class WidgetApiService
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

    // -- visitor messaging (relayed to Python's /api/web/message) --------

    /**
     * Send one visitor message to this widget's bot and get back its
     * reply. Called by WidgetMessageController for every inbound message
     * from an embedded widget - NOT called directly by the widget's own
     * JS anymore (that now talks to our own /api/widget/message, which
     * persists the conversation and calls this internally, then
     * broadcasts via the existing Reverb pipe).
     */
    public function sendMessage(string $token, string $sessionId, string $message): ?array
    {
        try {
            $response = Http::timeout(15)
                ->post("{$this->baseUrl}/api/web/message?token=" . urlencode($token), [
                    'session_id' => $sessionId,
                    'message' => $message,
                ]);
            if ($response->successful()) {
                return $response->json();
            }
            
            Log::error('WidgetApiService sendMessage failed', ['status' => $response->status(), 'body' => $response->body()]);
        } catch (\Throwable $e) {
            Log::error('WidgetApiService sendMessage exception', ['message' => $e->getMessage()]);
        }
        return null;
    }

    /**
     * Get the exact answer for a tapped option from a previous
     * sendMessage() response's `options` list - called server-to-server
     * from WidgetMessageController::select(), which is what the widget
     * now calls instead of hitting this Python endpoint directly. That
     * used to bypass Laravel entirely, so a clicked option's question
     * and answer were never saved as Messages - invisible in the CRM's
     * conversation history, unlike every other message.
     */
    public function selectOption(string $token, string $sourceId): ?array
    {
        try {
            $response = Http::timeout(15)
                ->post("{$this->baseUrl}/api/web/select?token=" . urlencode($token), [
                    'source_id' => $sourceId,
                ]);
            if ($response->successful()) {
                return $response->json();
            }
            Log::error('WidgetApiService selectOption failed', ['status' => $response->status(), 'body' => $response->body()]);
        } catch (\Throwable $e) {
            Log::error('WidgetApiService selectOption exception', ['message' => $e->getMessage()]);
        }
        return null;
    }

    // -- widgets -------------------------------------------------------

    public function listWidgets(): array
    {
        try {
            $response = Http::withHeaders($this->headers())->timeout(15)->get("{$this->baseUrl}/widgets");
            if ($response->successful()) {
                return $response->json() ?? [];
            }
            Log::error('WidgetApiService listWidgets failed', ['status' => $response->status(), 'body' => $response->body()]);
        } catch (\Throwable $e) {
            Log::error('WidgetApiService listWidgets exception', ['message' => $e->getMessage()]);
        }
        return [];
    }

    public function createWidget(string $siteName, ?string $contactEmail = null): ?array
    {
        $payload = ['site_name' => $siteName];
        if ($contactEmail) {
            $payload['contact_email'] = $contactEmail;
        }
        try {
            $response = Http::withHeaders($this->headers())->timeout(15)
                ->post("{$this->baseUrl}/widgets", $payload);
            if ($response->successful()) {
                return $response->json();
            }
            Log::error('WidgetApiService createWidget failed', ['status' => $response->status(), 'body' => $response->body()]);
        } catch (\Throwable $e) {
            Log::error('WidgetApiService createWidget exception', ['message' => $e->getMessage()]);
        }
        return null;
    }

    public function getWidgetConfig(string $token): ?array
    {
        try {
            $response = Http::withHeaders($this->headers())->timeout(15)->get("{$this->baseUrl}/widgets/{$token}/config");
            if ($response->successful()) {
                return $response->json();
            }
            if ($response->status() !== 404) {
                Log::error('WidgetApiService getWidgetConfig failed', ['status' => $response->status(), 'body' => $response->body()]);
            }
        } catch (\Throwable $e) {
            Log::error('WidgetApiService getWidgetConfig exception', ['message' => $e->getMessage()]);
        }
        return null;
    }

    public function updateWidgetConfig(string $token, array $data): ?array
    {
        try {
            $response = Http::withHeaders($this->headers())->timeout(15)
                ->put("{$this->baseUrl}/widgets/{$token}/config", $data);
            if ($response->successful()) {
                return $response->json();
            }
            Log::error('WidgetApiService updateWidgetConfig failed', ['status' => $response->status(), 'body' => $response->body()]);
        } catch (\Throwable $e) {
            Log::error('WidgetApiService updateWidgetConfig exception', ['message' => $e->getMessage()]);
        }
        return null;
    }

    public function deleteWidget(string $token): bool
    {
        try {
            $response = Http::withHeaders($this->headers())->timeout(15)->delete("{$this->baseUrl}/widgets/{$token}");
            if ($response->successful() || $response->status() === 404) {
                return true;
            }
            Log::error('WidgetApiService deleteWidget failed', ['status' => $response->status(), 'body' => $response->body()]);
        } catch (\Throwable $e) {
            Log::error('WidgetApiService deleteWidget exception', ['message' => $e->getMessage()]);
        }
        return false;
    }

    // -- per-widget FAQ knowledge base ----------------------------------

    public function listFaqSources(string $token): array
    {
        try {
            $response = Http::withHeaders($this->headers())->timeout(15)->get("{$this->baseUrl}/widgets/{$token}/faq/sources");
            if ($response->successful()) {
                return $response->json() ?? [];
            }
            Log::error('WidgetApiService listFaqSources failed', ['status' => $response->status(), 'body' => $response->body()]);
        } catch (\Throwable $e) {
            Log::error('WidgetApiService listFaqSources exception', ['message' => $e->getMessage()]);
        }
        return [];
    }

    /**
     * Full detail for ONE FAQ (answer text + keywords included) - the
     * plain listing above deliberately only carries title/attachment/
     * type, since that's all the knowledge-base table needs. An edit
     * form needs this instead.
     */
    public function getFaqSource(string $token, string $sourceId): ?array
    {
        try {
            $response = Http::withHeaders($this->headers())->timeout(15)->get("{$this->baseUrl}/widgets/{$token}/faq/sources/{$sourceId}");
            if ($response->successful()) {
                return $response->json();
            }
            if ($response->status() !== 404) {
                Log::error('WidgetApiService getFaqSource failed', ['status' => $response->status(), 'body' => $response->body()]);
            }
        } catch (\Throwable $e) {
            Log::error('WidgetApiService getFaqSource exception', ['message' => $e->getMessage()]);
        }
        return null;
    }

    public function uploadFaqText(string $token, string $name, string $text, ?string $sourceUrl = null, bool $sendAsLink = false, ?array $keywords = null): ?array
    {
        $payload = ['name' => $name, 'text' => $text, 'send_as_link' => $sendAsLink];
        if ($sourceUrl) {
            $payload['source_url'] = $sourceUrl;
        }
        if (!empty($keywords)) {
            $payload['keywords'] = $keywords;
        }

        try {
            $response = Http::withHeaders($this->headers())->timeout(15)
                ->post("{$this->baseUrl}/widgets/{$token}/faq/upload/text", $payload);
            if ($response->successful()) {
                return $response->json();
            }
            Log::error('WidgetApiService uploadFaqText failed', ['status' => $response->status(), 'body' => $response->body()]);
        } catch (\Throwable $e) {
            Log::error('WidgetApiService uploadFaqText exception', ['message' => $e->getMessage()]);
        }
        return null;
    }

    /**
     * Edit an existing FAQ IN PLACE - same id afterwards. Use this
     * instead of delete+uploadFaqText for edits: that older pattern
     * assigned a brand new id on every edit, which both broke any
     * stale/bookmarked edit link ("FAQ not found") and was a genuine
     * data-loss risk if the re-create step ever failed right after the
     * delete had already succeeded.
     */
    public function updateFaqSource(string $token, string $sourceId, string $name, string $text, ?string $sourceUrl = null, bool $sendAsLink = false, ?array $keywords = null): ?array
    {
        $payload = ['name' => $name, 'text' => $text, 'send_as_link' => $sendAsLink];
        if ($sourceUrl) {
            $payload['source_url'] = $sourceUrl;
        }
        if (!empty($keywords)) {
            $payload['keywords'] = $keywords;
        }

        try {
            $response = Http::withHeaders($this->headers())->timeout(15)
                ->put("{$this->baseUrl}/widgets/{$token}/faq/sources/{$sourceId}", $payload);
            if ($response->successful()) {
                return $response->json();
            }
            Log::error('WidgetApiService updateFaqSource failed', ['status' => $response->status(), 'body' => $response->body()]);
        } catch (\Throwable $e) {
            Log::error('WidgetApiService updateFaqSource exception', ['message' => $e->getMessage()]);
        }
        return null;
    }

    /**
     * Store a file as an attachment on ONE specific FAQ answer, without
     * indexing its content as its own searchable entry - same pattern as
     * FaqApiService::attachFile() for the WhatsApp bot's FAQs.
     */
    public function attachFile(string $token, string $filePath, string $filename): ?array
    {
        try {
            $response = Http::withHeaders($this->headers())->timeout(30)
                ->attach('file', file_get_contents($filePath), $filename)
                ->post("{$this->baseUrl}/widgets/{$token}/faq/files/attach");
            if ($response->successful()) {
                return $response->json();
            }
            Log::error('WidgetApiService attachFile failed', ['status' => $response->status(), 'body' => $response->body()]);
        } catch (\Throwable $e) {
            Log::error('WidgetApiService attachFile exception', ['message' => $e->getMessage()]);
        }
        return null;
    }

    public function deleteFaqSource(string $token, string $sourceId): bool
    {
        try {
            $response = Http::withHeaders($this->headers())->timeout(15)
                ->delete("{$this->baseUrl}/widgets/{$token}/faq/sources/{$sourceId}");
            if ($response->successful() || $response->status() === 404) {
                return true;
            }
            Log::error('WidgetApiService deleteFaqSource failed', ['status' => $response->status(), 'body' => $response->body()]);
        } catch (\Throwable $e) {
            Log::error('WidgetApiService deleteFaqSource exception', ['message' => $e->getMessage()]);
        }
        return false;
    }
}
