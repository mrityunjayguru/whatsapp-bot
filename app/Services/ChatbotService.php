<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotService
{
    protected string $chatbotUrl;

    public function __construct()
    {
        // using chatboat.url based on existing config
        $this->chatbotUrl = config('services.chatboat.url');
    }

    public function getReply(string $message, ?string $phone = null, ?int $companyId = null, ?string $conversationId = null): ?string
    {
        $baseUrl = rtrim((string) config('services.chatbot.url', env('CHATBOAT_URL')), '/');
        
        // 1. Point to /bot/reply (matching FastAPI's endpoint)
        $url = $baseUrl . '/bot/reply';

        // 2. Prepare payload matching Python's schemas.py
        $payload = [
            'phone_number'    => $phone ?? '',
            'message'         => $message,
            'conversation_id' => $conversationId,
        ];
        
        if ($companyId !== null) {
            $payload['tenant_id'] = $companyId;
        }

        try {
            // 3. Add ngrok-skip-browser-warning header to bypass ngrok's free tier landing page
            $response = Http::withHeaders([
                'ngrok-skip-browser-warning' => 'true',
                'Accept'                     => 'application/json',
                'Content-Type'               => 'application/json',
            ])
            ->timeout((int) config('services.chatboat.timeout', 15))
            ->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();
                
                // FastAPI returns the text response (e.g. key 'reply' or 'response')
                return $data['reply'] ?? $data['response'] ?? $data['message'] ?? null;
            }

            Log::error('Chatbot API returned error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::error('Chatbot service connection failure', [
                'url'   => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Same call as getReply(), but returns the FULL decoded response
     * (reply, intent, should_handoff_to_human, options) instead of just
     * the reply string - needed so the caller can see `options` and send
     * WhatsApp Reply Buttons instead of plain text when several FAQs
     * matched closely. getReply() itself is untouched so any existing
     * caller keeps working exactly as before.
     */
    public function getFullReply(string $message, ?string $phone = null, ?int $companyId = null, ?string $conversationId = null): ?array
    {
        $baseUrl = rtrim((string) config('services.chatbot.url', env('CHATBOAT_URL')), '/');
        $url = $baseUrl . '/bot/reply';

        $payload = [
            'phone_number'    => $phone ?? '',
            'message'         => $message,
            'conversation_id' => $conversationId,
        ];
        
        if ($companyId !== null) {
            $payload['tenant_id'] = $companyId;
        }

        try {
            $response = Http::withHeaders([
                'ngrok-skip-browser-warning' => 'true',
                'Accept'                     => 'application/json',
                'Content-Type'               => 'application/json',
            ])
            ->timeout((int) config('services.chatboat.timeout', 15))
            ->post($url, $payload);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Chatbot API (full reply) returned error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::error('Chatbot service (full reply) connection failure', [
                'url'   => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Called when a customer taps one of the Reply Buttons sent for an
     * ambiguous FAQ match. `sourceId` is the button's id, which is
     * exactly the FAQ's id on the Python side - this returns that FAQ's
     * answer directly, no text matching involved.
     */
    public function selectFaqOption(string $sourceId): ?string
    {
        $baseUrl = rtrim((string) config('services.chatbot.url', env('CHATBOAT_URL')), '/');
        $url = $baseUrl . '/bot/faq/select';

        try {
            $response = Http::withHeaders([
                'ngrok-skip-browser-warning' => 'true',
                'Accept'                     => 'application/json',
                'Content-Type'               => 'application/json',
            ])
            ->timeout((int) config('services.chatboat.timeout', 15))
            ->post($url, ['source_id' => $sourceId]);

            if ($response->successful()) {
                return $response->json('reply');
            }

            Log::error('Chatbot API (select option) returned error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::error('Chatbot service (select option) connection failure', [
                'url'   => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

}
