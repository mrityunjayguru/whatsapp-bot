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

        return $response->json();
    }

    public function sendMultipartMessage(string $to, ?string $message = null, array $files = [])
    {
        // 1. Send Text if present
        if (!empty($message)) {
            $this->sendMessage($to, $message);
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
                $mediaPayload['caption'] = $message;
            }
            if ($mediaType === 'document') {
                $mediaPayload['filename'] = $file->getClientOriginalName();
            }

            // Step 2C: Send media message
            Http::withToken($this->accessToken)
                ->post("{$this->baseUrl}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to'                => $to,
                    'type'              => $mediaType,
                    $mediaType          => $mediaPayload,
                ]);
        }
    }
}
