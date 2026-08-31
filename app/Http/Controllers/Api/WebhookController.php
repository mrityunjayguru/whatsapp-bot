<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ChatBoatEntity;
use App\Models\ContactEntity;
use App\Models\ConversationEntity;
use App\Models\MessageEntity;
use App\Models\WebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;



class WebhookController extends Controller
{
  public function verifyWebhook(Request $request){
    $mode = $request->query('hub.mode');
    $token = $request->query('hub.verify_token');
    $challenge = $request->query('hub.challenge');

    Log::info('========== WEBHOOK VERIFICATION ==========');
    Log::info('Mode: ' . $mode);
    Log::info('Token received: ' . $token);
    Log::info('Token expected: ' . env('WEBHOOK_VERIFY_TOKEN'));
    Log::info('Challenge: ' . $challenge);
    Log::info('==========================================');

    if (
        $mode === 'subscribe' &&
        $token === env('WEBHOOK_VERIFY_TOKEN')
    ) {
        return response($challenge, 200);
    }

    return response()->json([
        'status' => 'error',
        'message' => 'Forbidden',
        'mode' => $mode,
        'token_match' => $token === env('WEBHOOK_VERIFY_TOKEN'),
    ], 403);
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
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'status' => 'success',

            'message' => 'Webhook received',

            'whatsapp_data' => [

                'phone_number' => $phoneNumber,

                'profile_name' => $profileName,

                'message_type' => $type,

                'message' => $messageBody,

            ]

        ]);
    }
}