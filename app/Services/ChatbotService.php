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
}
