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
        $response = Http::withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]])
            ->withToken($this->accessToken)
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

    /**
     * Send a WhatsApp Reply Buttons interactive message - up to 3 tappable
     * options, used when the bot's /bot/reply returned `options` (several
     * FAQs matched closely). Each $buttons entry is ['id' => ..., 'title' => ...];
     * `id` comes back exactly as given when the customer taps it, and is
     * what MetaWebhookController passes to ChatbotService::selectFaqOption().
     */
    public function sendInteractiveButtons(string $to, string $bodyText, array $buttons)
    {
        // WhatsApp allows at most 3 reply buttons, each title capped at
        // 20 characters - the Python side already truncates titles to
        // that limit, but enforce it here too in case options ever come
        // from somewhere else.
        $buttons = array_slice($buttons, 0, 3);
        $payloadButtons = array_map(function ($button) {
            return [
                'type' => 'reply',
                'reply' => [
                    'id'    => (string) $button['id'],
                    'title' => mb_substr((string) $button['title'], 0, 20),
                ],
            ];
        }, $buttons);

        $response = Http::withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]])
            ->withToken($this->accessToken)
            ->post("{$this->baseUrl}/messages", [
                'messaging_product' => 'whatsapp',
                'to'                => $to,
                'type'              => 'interactive',
                'interactive'       => [
                    'type' => 'button',
                    'body' => ['text' => $bodyText],
                    'action' => [
                        'buttons' => $payloadButtons,
                    ],
                ],
            ]);

        $data = $response->json();
        if (!$response->successful()) {
            \Illuminate\Support\Facades\Log::error('WhatsApp Interactive Buttons API Error', [
                'status' => $response->status(),
                'response' => $data,
            ]);
        }

        return $data;
    }

    /**
     * Send a WhatsApp List Message - up to 10 tappable rows, each with a
     * short title AND a longer description, used when the bot's
     * /bot/reply returned `options` (several FAQs matched closely).
     * Unlike Reply Buttons (20-char titles, no second field), a List
     * Message row's description (up to ~72 chars) can show the FULL
     * question even when the title alone had to be truncated. Each
     * $rows entry is ['id' => ..., 'title' => ..., 'description' => ...];
     * `id` comes back exactly as given when the customer taps it, and is
     * what MetaWebhookController passes to ChatbotService::selectFaqOption().
     */
    public function sendInteractiveList(string $to, string $bodyText, array $rows, string $buttonText = 'View options', string $sectionTitle = 'Matching FAQs')
    {
        // WhatsApp allows at most 10 rows total, title capped at 24
        // characters, description at 72, and the trigger button text at
        // 20 - the Python side already truncates title/description to
        // those limits, but enforce it here too in case options ever
        // come from somewhere else.
        $rows = array_slice($rows, 0, 10);
        $payloadRows = array_map(function ($row) {
            $entry = [
                'id'    => (string) $row['id'],
                'title' => mb_substr((string) $row['title'], 0, 24),
            ];
            if (!empty($row['description'])) {
                $entry['description'] = mb_substr((string) $row['description'], 0, 72);
            }
            return $entry;
        }, $rows);

        $response = Http::withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]])
            ->withToken($this->accessToken)
            ->post("{$this->baseUrl}/messages", [
                'messaging_product' => 'whatsapp',
                'to'                => $to,
                'type'              => 'interactive',
                'interactive'       => [
                    'type' => 'list',
                    'body' => ['text' => $bodyText],
                    'action' => [
                        'button'   => mb_substr($buttonText, 0, 20),
                        'sections' => [
                            [
                                'title' => mb_substr($sectionTitle, 0, 24),
                                'rows'  => $payloadRows,
                            ],
                        ],
                    ],
                ],
            ]);

        $data = $response->json();
        if (!$response->successful()) {
            \Illuminate\Support\Facades\Log::error('WhatsApp Interactive List API Error', [
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
            $uploadResponse = Http::withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]])
                ->withToken($this->accessToken)
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
            $mediaMsgResponse = Http::withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]])
                ->withToken($this->accessToken)
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
                    'id' => $mediaData['messages'][0]['id'],
                    'file' => $file
                ];
            }
        }
        
        return $sentMessageIds;
    }
}
