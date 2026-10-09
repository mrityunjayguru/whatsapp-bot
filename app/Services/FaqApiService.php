<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FaqApiService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected ?int $companyId = null;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.bot_api.url', env('CHATBOAT_URL', 'http://127.0.0.1:5000')), '/');
        $this->apiKey = (string) config('services.bot_api.key');
    }

    private function headers(): array
    {
        return [
            'X-Api-Key' => $this->apiKey,
            'Accept' => 'application/json',
        ];
    }

    public function setCompanyId(int $companyId): self
    {
        $this->companyId = $companyId;
        return $this;
    }

    private function getBaseUrl(): string
    {
        $cid = $this->companyId ?? (auth()->check() ? (auth()->user()->company_id ?? auth()->user()->tenant_id) : 'default');
        return "{$this->baseUrl}/company/{$cid}/faq";
    }

    public function uploadText(string $name, string $text, ?string $sourceUrl = null, bool $sendAsLink = false, ?array $keywords = null): ?string
    {
        $payload = [
            'name' => $name,
            'text' => $text,
            'send_as_link' => $sendAsLink,
        ];
        
        if ($sourceUrl) {
            $payload['source_url'] = $sourceUrl;
        }

        if (!empty($keywords)) {
            $payload['keywords'] = $keywords;
        }

        try {
            $response = Http::withHeaders($this->headers())->post("{$this->getBaseUrl()}/upload/text", $payload);
            
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
            $response = Http::withHeaders($this->headers())->attach(
                'file', file_get_contents($filePath), $filename
            )->post("{$this->getBaseUrl()}/upload/document", [
                'send_as_link' => $sendAsLink ? 'true' : 'false'
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                // Fix proxy URL if python returned without /pybot
                if (isset($data['source_url']) && str_contains($this->getBaseUrl(), '/pybot')) {
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
            $response = Http::withHeaders($this->headers())->post("{$this->getBaseUrl()}/{$endpoint}", $payload);
            
            if ($response->successful()) {
                return $response->json('id');
            }
            
            Log::error("FaqApiService {$endpoint} failed", ['response' => $response->body()]);
        } catch (\Exception $e) {
            Log::error("FaqApiService {$endpoint} exception", ['message' => $e->getMessage()]);
        }
        
        return null;
    }

    /**
     * Store a file as an attachment on ONE specific FAQ answer, without
     * indexing its content as its own separately-searchable source. Use
     * this instead of uploadDocument() for the "attach a file to this
     * question" flow - uploadDocument() creates an independent,
     * competing search entry, which is why an unrelated query could
     * previously win over the document and return the wrong link.
     */
    public function attachFile(string $filePath, string $filename): ?array
    {
        try {
            $response = Http::withHeaders($this->headers())->attach(
                'file', file_get_contents($filePath), $filename
            )->post("{$this->getBaseUrl()}/files/attach");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('FaqApiService attachFile failed', ['response' => $response->body()]);
        } catch (\Exception $e) {
            Log::error('FaqApiService attachFile exception', ['message' => $e->getMessage()]);
        }

        return null;
    }

    public function deleteSource(string $sourceId): bool
    {
        try {
            $response = Http::withHeaders($this->headers())->delete("{$this->getBaseUrl()}/sources/{$sourceId}");
            
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
