<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FaqApiService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = env('CHATBOAT_URL', 'http://127.0.0.1:5000') . '/faq';
    }

    public function uploadText(string $name, string $text, ?string $sourceUrl = null, bool $sendAsLink = false): ?string
    {
        $payload = [
            'name' => $name,
            'text' => $text,
            'send_as_link' => $sendAsLink,
        ];
        
        if ($sourceUrl) {
            $payload['source_url'] = $sourceUrl;
        }

        try {
            $response = Http::post("{$this->baseUrl}/upload/text", $payload);
            
            if ($response->successful()) {
                return $response->json('id');
            }
            
            Log::error('FaqApiService uploadText failed', ['response' => $response->body()]);
        } catch (\Exception $e) {
            Log::error('FaqApiService uploadText exception', ['message' => $e->getMessage()]);
        }
        
        return null;
    }

    public function uploadDocument(string $filePath, string $filename, bool $sendAsLink = true): ?array
    {
        try {
            $response = Http::attach(
                'file', file_get_contents($filePath), $filename
            )->post("{$this->baseUrl}/upload/document", [
                'send_as_link' => $sendAsLink ? 'true' : 'false'
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                // Fix proxy URL if python returned without /pybot
                if (isset($data['source_url']) && str_contains($this->baseUrl, '/pybot')) {
                    $data['source_url'] = str_replace('.com/faq/files/', '.com/pybot/faq/files/', $data['source_url']);
                }
                return $data;
            }
            
            Log::error('FaqApiService uploadDocument failed', ['response' => $response->body()]);
        } catch (\Exception $e) {
            Log::error('FaqApiService uploadDocument exception', ['message' => $e->getMessage()]);
        }
        
        return null;
    }

    public function uploadUrl(string $url, ?string $name = null): ?string
    {
        $payload = ['url' => $url];
        if ($name) $payload['name'] = $name;

        // Simple check to determine if it's a Youtube video
        $endpoint = (str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be')) 
            ? 'upload/video' 
            : 'upload/url';

        try {
            $response = Http::post("{$this->baseUrl}/{$endpoint}", $payload);
            
            if ($response->successful()) {
                return $response->json('id');
            }
            
            Log::error("FaqApiService {$endpoint} failed", ['response' => $response->body()]);
        } catch (\Exception $e) {
            Log::error("FaqApiService {$endpoint} exception", ['message' => $e->getMessage()]);
        }
        
        return null;
    }

    public function deleteSource(string $sourceId): bool
    {
        try {
            $response = Http::delete("{$this->baseUrl}/sources/{$sourceId}");
            
            if ($response->successful() || $response->status() == 404) {
                return true;
            }
            
            Log::error("FaqApiService deleteSource failed", ['response' => $response->body()]);
        } catch (\Exception $e) {
            Log::error("FaqApiService deleteSource exception", ['message' => $e->getMessage()]);
        }
        
        return false;
    }
}
