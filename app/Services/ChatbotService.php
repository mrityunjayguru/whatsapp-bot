<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ChatbotService
{
    protected string $chatbotUrl;

    public function __construct()
    {
        // using chatboat.url based on existing config
        $this->chatbotUrl = config('services.chatboat.url');
    }

    public function getReply(array $requestBody)
    {
        $response = Http::acceptJson()->post($this->chatbotUrl, $requestBody);

        if ($response->failed()) {
            throw new \Exception("Chatbot API Error: " . $response->body());
        }

        return $response->json();
    }
}
