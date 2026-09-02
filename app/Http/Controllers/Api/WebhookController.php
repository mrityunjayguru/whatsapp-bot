<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ChatBoatEntity;
use App\Models\ContactEntity;
use App\Models\ConversationEntity;
use App\Models\MessageEntity;
use App\Models\ChatBoat;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\WhatsAppService;
use App\Services\ChatbotService;



class WebhookController extends Controller
{
    public function verifyWebhook(Request $request)
    {
        $verifyToken = env('META_WEBHOOK_VERIFY_TOKEN');

        $mode = $request->query('hub.mode');
        $token = $request->query('hub.verify_token');
        $challenge = $request->query('hub.challenge');

        Log::info('========== WEBHOOK VERIFICATION ==========');
        Log::info('Mode: ' . $mode);
        Log::info('Token received: ' . $token);
        Log::info('Token expected: ' . $verifyToken);
        Log::info('Challenge: ' . $challenge);
        Log::info('==========================================');

        if (
            $mode === 'subscribe' &&
            $token === $verifyToken
        ) {
            return response($challenge, 200)
                ->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }


    protected WhatsAppService $whatsappService;
    protected ChatbotService $chatbotService;

    public function __construct(WhatsAppService $whatsappService, ChatbotService $chatbotService)
    {
        $this->whatsappService = $whatsappService;
        $this->chatbotService = $chatbotService;
    }

    // Receive webhook data
    public function receiveWebhook(Request $request)
    {
        $payload = $request->getContent();

        Log::info('================ WEBHOOK RECEIVED ================');
        Log::info($payload);
        Log::info('===================================================');

        // Convert JSON to array
        $data = json_decode($payload, true);

        if (json_last_error() !== JSON_ERROR_NONE) {

            return response()->json([
                'status' => 'error',
                'message' => 'Invalid JSON',
                'error' => json_last_error_msg()
            ], 400);
        }

        /*
        |--------------------------------------------------------------------------
        | Get WhatsApp value
        |--------------------------------------------------------------------------
        */

        $value = data_get(
            $data,
            'entry.0.changes.0.value'
        );

        /*
        |--------------------------------------------------------------------------
        | Get message
        |--------------------------------------------------------------------------
        */

        $message = data_get(
            $value,
            'messages.0'
        );

        if (!$message) {

            return response()->json([
                'status' => 'success',
                'message' => 'No message found',
                'data' => $data
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Get message type
        |--------------------------------------------------------------------------
        */

        $type = data_get(
            $message,
            'type',
            ''
        );

        /*
        |--------------------------------------------------------------------------
        | Get text message
        |--------------------------------------------------------------------------
        */

        $messageBody = '';

        if ($type === 'text') {

            $messageBody = data_get(
                $message,
                'text.body',
                ''
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Get WhatsApp contact information
        |--------------------------------------------------------------------------
        */

        $phoneNumber = data_get(
            $value,
            'contacts.0.wa_id',
            ''
        );

        $profileName = data_get(
            $value,
            'contacts.0.profile.name',
            ''
        );

        /*
        |--------------------------------------------------------------------------
        | Integrate Chatbot Flow
        |--------------------------------------------------------------------------
        */

        if ($type === 'text' && !empty($messageBody)) {
            try {
                // 1. Prepare Request body for external chatbot
                $chatbotRequestPayload = [
                    'message' => $messageBody,
                    'phone_number' => $phoneNumber,
                    'profile_name' => $profileName,
                ];

                // 2. Call external chatbot API (Bypassed for testing)
                // $chatbotResponse = $this->chatbotService->getReply($chatbotRequestPayload);
                // $chatbotReplyText = data_get($chatbotResponse, 'reply', 'Sorry, I am unable to process your request at the moment.');
                
                // Mock response for testing without a real chatbot URL
                $chatbotResponse = ['reply' => "This is a test reply! You said: '{$messageBody}'"];
                $chatbotReplyText = $chatbotResponse['reply'];

                // 3. Send reply back to WhatsApp
                $this->whatsappService->sendMessage($phoneNumber, $chatbotReplyText);

                // 4. Log the interaction in database
                ChatBoat::create([
                    'phonenumber' => $phoneNumber,
                    'requestpayload' => json_encode($chatbotRequestPayload),
                    'responsepayload' => json_encode($chatbotResponse),
                    'payload' => json_encode($data),
                ]);

            } catch (\Exception $e) {
                Log::error('Chatbot Integration Error: ' . $e->getMessage());
                
                // Fallback message to user
                $this->whatsappService->sendMessage($phoneNumber, "We're experiencing technical difficulties. Please try again later.");
            }
        }

        return response()->json([

            'status' => 'success',

            'message' => 'Webhook received and processed',

        ]);
    }
}