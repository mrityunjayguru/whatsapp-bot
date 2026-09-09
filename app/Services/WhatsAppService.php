<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WhatsAppService
{
    protected string $accessToken;
    protected string $phoneNumberId;
    protected string $baseUrl;

    public function __construct()
    {
        $this->accessToken = config('services.whatsapp.access_token');
        $this->phoneNumberId = config('services.whatsapp.phone_number_id');
        $version = config('services.whatsapp.graph_version', 'v23.0');
        $this->baseUrl = "https://graph.facebook.com/{$version}/{$this->phoneNumberId}";
    }

    public function sendMessage(string $to, string $message)
    {
        $response = Http::withToken($this->accessToken)
            ->post("{$this->baseUrl}/messages", [
                'messaging_product' => 'whatsapp',
                'to'                => $to,
                'type'              => 'text',
                'text'              => [
                    'preview_url' => false,
                    'body'        => $message,
                ],
            ]);
            
        $data = $response->json();
        if (!$response->successful()) {
            \Illuminate\Support\Facades\Log::error('WhatsApp API Error', [
                'status' => $response->status(),
                'response' => $data,
            ]);
        }

        return $data;
    }

    public function sendMultipartMessage(string $to, ?string $message = null, array $files = [])
    {
        $sentMessageIds = [];

        // 1. Send Text if present
        if (!empty($message)) {
            $textResponse = $this->sendMessage($to, $message);
            if (isset($textResponse['messages'][0]['id'])) {
                $sentMessageIds[] = [
                    'type' => 'TEXT',
                    'id' => $textResponse['messages'][0]['id']
                ];
            }
        }

        // 2. Upload and send media files
        foreach ($files as $file) {
            if (!$file->isValid()) continue;

            // Step 2A: Upload media file to WhatsApp
            $uploadResponse = Http::withToken($this->accessToken)
                ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                ->post("{$this->baseUrl}/media", [
                    'messaging_product' => 'whatsapp',
                    'type'              => $file->getClientMimeType(),
                ]);

            $mediaId = $uploadResponse->json('id');
            if (!$mediaId) continue;

            $mimeType = $file->getClientMimeType();

            // Determine WhatsApp media type
            $mediaType = match (true) {
                str_starts_with($mimeType, 'image/') => 'image',
                str_starts_with($mimeType, 'video/') => 'video',
                str_starts_with($mimeType, 'audio/') => 'audio',
                default                              => 'document',
            };

            // Step 2B: Build media payload
            $mediaPayload = ['id' => $mediaId];
            if ($mediaType !== 'audio' && !empty($message)) {
                // If we want the caption on the media, we can add it. But we already sent text separately above.
                // It's usually better to just send them separately to avoid duplicating text if there are multiple files.
            }
            if ($mediaType === 'document') {
                $mediaPayload['filename'] = $file->getClientOriginalName();
            }

            // Step 2C: Send media message
            $mediaMsgResponse = Http::withToken($this->accessToken)
                ->post("{$this->baseUrl}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to'                => $to,
                    'type'              => $mediaType,
                    $mediaType          => $mediaPayload,
                ]);
                
            $mediaData = $mediaMsgResponse->json();
            if (isset($mediaData['messages'][0]['id'])) {
                $sentMessageIds[] = [
                    'type' => strtoupper($mediaType),
                    'id' => $mediaData['messages'][0]['id']
                ];
            }
        }
        
        return $sentMessageIds;
    }
}
