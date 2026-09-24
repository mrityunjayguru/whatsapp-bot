<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadNote;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\MetaSetting;
use App\Services\MetaGraphApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MetaWebhookController extends Controller
{
    // public function verify(Request $request)
    // {
    //     $mode      = $request->input('hub_mode');
    //     $token     = $request->input('hub_verify_token');
    //     $challenge = $request->input('hub_challenge');

    //     Log::info('[MetaWebhook] Verification attempt', [
    //         'mode'      => $mode,
    //         'has_token' => filled($token),
    //         'has_challenge' => filled($challenge),
    //     ]);

    //     $settings = $this->findAnySettings();
    //     $verifyToken = null;

    //     if ($settings) {
    //         $verifyToken = $settings->credential('webhook_verify_token');
    //     }
    
    //     if (!$verifyToken) {
    //         $verifyToken = config('services.meta.webhook_verify');
    //     }

    //     if ($mode === 'subscribe' && $token === $verifyToken) {
    //         Log::info('[MetaWebhook] Verified successfully');
    //         return response($challenge, 200)->header('Content-Type', 'text/plain');
    //     }

    //     Log::warning('[MetaWebhook] Verification failed', [
    //         'mode'      => $mode,
    //         'expected'  => $verifyToken,
    //         'got'       => $token,
    //     ]);

    //     abort(403, 'Invalid verification token.');
    // }

    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        \Log::info('[MetaWebhook] Verification', [
            'mode' => $mode,
            'token_received' => $token,
            'token_expected' => config('services.meta.webhook_verify_token'),
            'challenge' => $challenge,
        ]);

        if (
            $mode === 'subscribe' &&
            $token === config('services.meta.webhook_verify_token')
        ) {
            return response($challenge, 200)
                ->header('Content-Type', 'text/plain');
        }

        return response('INVALID VERIFICATION TOKEN', 403);
    }

    public function handle(Request $request, MetaGraphApiService $graphService)
    {
        $payload = $request->all();

        Log::info('[MetaWebhook] Incoming event', [
            'ip'      => $request->ip(),
            'payload' => json_encode($payload),
        ]);

        $object = $payload['object'] ?? null;
        
        // Allow both Meta Lead Gen Pages and WhatsApp Business Accounts
        if ($object !== 'page' && $object !== 'whatsapp_business_account') {
            Log::warning('[MetaWebhook] Ignoring unsupported object', ['object' => $object]);
            return response('IGNORED', 200);
        }

        // Process WhatsApp Business Account Messages
        if ($object === 'whatsapp_business_account') {
            $entries = $payload['entry'] ?? [];

            foreach ($entries as $entry) {
                foreach ($entry['changes'] ?? [] as $change) {
                    $value = $change['value'] ?? [];

                    if (!empty($value['messages'])) {
                        foreach ($value['messages'] as $msg) {
                            $msgType = $msg['type'] ?? '';
                            $fromPhone = $msg['from'] ?? null;
                            $textMessage = null;
                            $tappedOptionId = null;

                            if ($msgType === 'text') {
                                $textMessage = $msg['text']['body'] ?? '';
                            } elseif ($msgType === 'interactive' && in_array($msg['interactive']['type'] ?? '', ['list_reply', 'button_reply'], true)) {
                                // Customer tapped one of the options we sent
                                // for an ambiguous FAQ match - sent as a
                                // WhatsApp List Message (list_reply), or a
                                // Reply Buttons message if that's ever used
                                // instead (button_reply) - either way, the
                                // tapped id IS the exact FAQ's source_id;
                                // its title is what we show as "what they
                                // sent" in the conversation timeline.
                                $interactiveType = $msg['interactive']['type'];
                                $tappedOptionId = $msg['interactive'][$interactiveType]['id'] ?? null;
                                $textMessage = $msg['interactive'][$interactiveType]['title'] ?? '(selected an option)';
                            } else {
                                // Unsupported message type (image, audio,
                                // location, etc.) - skip, same as before.
                                continue;
                            }

                            $profileName = $value['contacts'][0]['profile']['name'] ?? '';

                            $metadata = $change['value']['metadata'] ?? [];
                            $connectedNumber = $metadata['display_phone_number'] ?? null;
                            $connectedNumberId = $metadata['phone_number_id'] ?? null;

                            // Prevent self-reply loop (if someone sends a message from the bot's own number via WhatsApp Web)
                            $connectedNumberClean = preg_replace('/\D/', '', (string)$connectedNumber);
                            $fromPhoneClean = preg_replace('/\D/', '', (string)$fromPhone);
                            if ($fromPhoneClean !== '' && $fromPhoneClean === $connectedNumberClean) {
                                \Illuminate\Support\Facades\Log::info('Ignored message from self.', ['phone' => $fromPhone]);
                                continue;
                            }

                            // Existing Tenant Resolution Logic
                            // Identifies which company (tenant) owns this contact. 
                            // Resolved automatically from the connected WhatsApp Business number.
                            $tenantId = 1001;
                            
                            $company = null;
                            if ($connectedNumber) {
                                // Try to find by contact_number matching display_phone_number
                                $company = \App\Models\Company::where('contact_number', $connectedNumber)->first();
                            }
                            if (!$company) {
                                // Fallback to the latest company
                                $company = \App\Models\Company::latest('id')->first();
                            }
                            if ($company) {
                                $tenantId = $company->id;
                            }

                            if ($fromPhone) {
                                $contact = \App\Models\Contact::firstOrCreate(
                                    ['phone_number' => $fromPhone],
                                    [
                                        'whatsapp_profile_name' => $profileName,
                                        'tenant_id' => $tenantId
                                    ]
                                );

                                $conversation = \App\Models\Conversation::where('contact_id', $contact->id)
                                    ->where('whatsapp_phone_number_id', $connectedNumberId ?? 0)
                                    ->orderBy('id', 'desc')
                                    ->first();

                                if (!$conversation || $conversation->status === 'CLOSED') {
                                    $conversation = \App\Models\Conversation::create([
                                        'contact_id' => $contact->id,
                                        'whatsapp_phone_number_id' => $connectedNumberId ?? 0,
                                        'tenant_id' => $tenantId,
                                        'title' => $profileName ?: $fromPhone,
                                        'status' => 'OPEN',
                                        'unread_count' => 0,
                                        'first_message_at' => now(),
                                    ]);
                                } elseif ($conversation->status === 'RESOLVED') {
                                    // Auto-reopen if resolved
                                    $conversation->status = 'OPEN';
                                    $conversation->save();
                                }

                                if ($textMessage) {
                                    $metaMessageId = $msg['id'] ?? uniqid('wam_');
                                    
                                    // Check if we already processed this message (Meta webhook retry protection)
                                    $existingMessage = \App\Models\Message::where('meta_message_id', $metaMessageId)->first();
                                    if ($existingMessage) {
                                        \Illuminate\Support\Facades\Log::info('Duplicate webhook event received, skipping.', ['meta_message_id' => $metaMessageId]);
                                        continue;
                                    }

                                    $inboundMessage = \App\Models\Message::create([
                                        'tenant_id' => $tenantId,
                                        'conversation_id' => $conversation->id,
                                        'contact_id' => $contact->id,
                                        'whatsapp_phone_number_id' => $connectedNumberId ?? 0,
                                        'meta_message_id' => $metaMessageId,
                                        'reply_to_meta_message_id' => $msg['context']['id'] ?? null,
                                        'message_type' => 'TEXT',
                                        'direction' => 'INBOUND',
                                        'sender_type' => 'CUSTOMER',
                                        'message_text' => $textMessage,
                                        'status' => 'RECEIVED',
                                        'sent_at' => isset($msg['timestamp']) ? \Carbon\Carbon::createFromTimestamp($msg['timestamp'])->timezone(config('app.timezone')) : now(),
                                    ]);

                                    $conversation->update([
                                        'unread_count' => $conversation->unread_count + 1,
                                        'last_message_at' => now(),
                                        'last_message_id' => $inboundMessage->id,
                                        'last_message_preview' => \Illuminate\Support\Str::limit($textMessage, 50),
                                    ]);

                                    event(new \App\Events\NewMessage($inboundMessage));

                                    // If a human agent has already taken this
                                    // conversation over, the bot stays silent -
                                    // just the inbound message above gets
                                    // saved+broadcast, same as the widget side
                                    // (see WidgetMessageController::send()).
                                    // Previously the bot replied to EVERY
                                    // message regardless of assignment.
                                    if (!$conversation->assigned_tenant_user_id && !$conversation->bot_stopped) {
                                    // 1. Fetch the bot's response - either the
                                    // exact answer for a tapped option, or a
                                    // normal text-matched reply (which may
                                    // itself come back with `options` when
                                    // several FAQs match closely).
                                    $chatbotService = app(\App\Services\ChatbotService::class);

                                    if ($tappedOptionId !== null) {
                                        $replyText = $chatbotService->selectFaqOption($tappedOptionId);
                                        $replyOptions = null;
                                    } else {
                                        $botResponse = $chatbotService->getFullReply(
                                            message: $textMessage,
                                            phone: $fromPhone
                                        );
                                        $replyText = $botResponse['reply'] ?? null;
                                        $replyOptions = $botResponse['options'] ?? null;

                                        // The visitor explicitly asked for a
                                        // human (bot_engine.py's human_handoff
                                        // intent, keyword-matched on "agent",
                                        // "human", etc. - not just "bot
                                        // couldn't answer") - fire the same
                                        // instant, app-wide alert the widget
                                        // side already uses, so an agent sees
                                        // this immediately wherever they are
                                        // in the CRM.
                                        if (($botResponse['intent'] ?? null) === 'human_handoff') {
                                            event(new \App\Events\HumanSupportRequested(
                                                $conversation->id,
                                                $conversation->company->name ?? 'WhatsApp',
                                                $textMessage,
                                            ));
                                        }
                                    }

                                    // 2. Send the reply - as a tappable List
                                    // Message when the bot found 2-3 close
                                    // matches (a List Message row can show
                                    // the full question as its description,
                                    // even when Reply Buttons' 20-char title
                                    // limit would cut it off), otherwise as
                                    // plain text (unchanged from before).
                                    $whatsappResponse = null;
                                    $sentText = null;

                                    if (!empty($replyOptions)) {
                                        $sentText = 'I found a few things that might match - which one did you mean?';
                                        $rows = array_map(
                                            fn($o) => [
                                                'id' => $o['id'],
                                                'title' => $o['title'],
                                                'description' => $o['description'] ?? null,
                                            ],
                                            $replyOptions
                                        );
                                        $whatsappResponse = app(\App\Services\WhatsAppService::class)
                                            ->sendInteractiveList($fromPhone, $sentText, $rows);
                                    } elseif (!empty($replyText)) {
                                        $sentText = $replyText;
                                        $whatsappResponse = app(\App\Services\WhatsAppService::class)
                                            ->sendMessage($fromPhone, $replyText);
                                    }

                                    if ($whatsappResponse !== null) {
                                        // Log the interaction
                                        \App\Models\ChatBoat::create([
                                            'phonenumber' => $fromPhone,
                                            'requestpayload' => json_encode(['message' => $textMessage, 'phone_number' => $fromPhone]),
                                            'responsepayload' => $sentText,
                                            'payload' => json_encode($payload),
                                        ]);

                                        $isFailed = isset($whatsappResponse['error']);
                                        $outboundMsgId = $whatsappResponse['messages'][0]['id'] ?? uniqid('sys_');

                                        $outboundMessage = \App\Models\Message::create([
                                            'tenant_id' => $tenantId,
                                            'conversation_id' => $conversation->id,
                                            'contact_id' => $contact->id,
                                            'whatsapp_phone_number_id' => $connectedNumberId ?? 0,
                                            'meta_message_id' => $outboundMsgId,
                                            'message_type' => 'TEXT',
                                            'direction' => 'OUTBOUND',
                                            'sender_type' => 'SYSTEM',
                                            'message_text' => $sentText,
                                            'status' => $isFailed ? 'FAILED' : 'SENT',
                                            'failure_reason' => $isFailed ? json_encode($whatsappResponse['error']) : null,
                                            'sent_at' => now(),
                                        ]);

                                        $conversation->update([
                                            'last_message_at' => now(),
                                            'last_message_id' => $outboundMessage->id,
                                            'last_message_preview' => \Illuminate\Support\Str::limit($sentText, 50),
                                        ]);

                                        event(new \App\Events\NewMessage($outboundMessage));
                                    }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            return response('EVENT_RECEIVED', 200);
        }

        $entries = $payload['entry'] ?? [];
        $processed = 0;

        foreach ($entries as $entry) {
            $pageId = $entry['id'] ?? null;
            $changes = $entry['changes'] ?? [];

            foreach ($changes as $change) {
                $field = $change['field'] ?? null;
                if ($field !== 'leadgen') {
                    continue;
                }

                $value = $change['value'] ?? [];
                $leadgenId = $value['leadgen_id'] ?? null;
                $formId    = $value['form_id']    ?? null;
                $entryPageId = $value['page_id'] ?? $pageId;

                if (!$leadgenId) {
                    Log::warning('[MetaWebhook] Missing leadgen_id', ['change' => $change]);
                    continue;
                }

                $settings = $this->findSettingsByPageId($entryPageId);
                if (!$settings) {
                    Log::warning('[MetaWebhook] No Meta settings found for page', ['page_id' => $entryPageId]);
                    continue;
                }

                $companyId = $settings->company_id;

                $service = new MetaGraphApiService(
                    pageAccessToken: (string) $settings->credential('page_access_token'),
                    graphVersion:    (string) $settings->credential('graph_api_version'),
                    pageId:          (string) $settings->credential('page_id'),
                    companyId:       (int) $companyId
                );

                $rawLead = $service->fetchLead($leadgenId);
                if (!$rawLead) {
                    continue;
                }

                $extracted = $service->extractFields($rawLead);

                try {
                    $this->storeLead($extracted, $settings);
                    $processed++;
                } catch (\Throwable $e) {
                    Log::error('[MetaWebhook] Lead store exception', [
                        'leadgen_id' => $leadgenId,
                        'company_id' => $companyId,
                        'error'      => $e->getMessage(),
                        'trace'      => $e->getTraceAsString(),
                    ]);
                }
            }
        }

        return response()->json([
            'success'   => true,
            'processed' => $processed,
        ]);
    }

    private function findAnySettings(): ?MetaSetting
    {
        return MetaSetting::query()
            ->whereNotNull('page_access_token')
            ->orWhereNotNull('app_id')
            ->first();
    }

    private function findSettingsByPageId(?string $pageId): ?MetaSetting
    {
        if ($pageId) {
            $settings = MetaSetting::where('page_id', $pageId)->first();
            if ($settings) {
                return $settings;
            }
        }

        $fallback = MetaSetting::whereNotNull('page_access_token')->first();
        if (!$fallback && filled(config('services.meta.page_access_token'))) {
            return MetaSetting::make([
                'company_id'          => null,
                'app_id'              => config('services.meta.app_id'),
                'page_id'             => config('services.meta.page_id'),
                'page_access_token'   => config('services.meta.page_access_token'),
                'webhook_verify_token'=> config('services.meta.webhook_verify'),
                'graph_api_version'   => config('services.meta.graph_api_version'),
                'default_created_by'  => (int) config('services.meta.default_user_id'),
            ]);
        }

        return $fallback;
    }

    private function storeLead(array $extracted, MetaSetting $settings): ?Lead
    {
        $metaLeadId = (string) ($extracted['meta_lead_id'] ?? '');

        if ($metaLeadId !== '' && Lead::where('meta_lead_id', $metaLeadId)->exists()) {
            Log::info('[MetaWebhook] Duplicate lead skipped', ['meta_lead_id' => $metaLeadId]);
            return null;
        }

        $phone = trim((string) ($extracted['phone_number'] ?? ''));
        if ($phone === '') {
            Log::warning('[MetaWebhook] Skipping lead without phone', ['meta_lead_id' => $metaLeadId]);
            return null;
        }

        $companyId = $settings->company_id;

        $ownerUserId = null;
        if ($companyId) {
            $company = \App\Models\Company::find($companyId);
            if ($company) {
                $ownerUserId = $company->user_id;
            }
        }

        $createdBy = (int) ($settings->default_created_by ?? config('services.meta.default_user_id', 1));

        $leadSourceId = null;
        $statusSourceQuery = LeadSource::where('name', 'Facebook');
        if ($ownerUserId) {
            $statusSourceQuery->where(function ($q) use ($ownerUserId) {
                $q->whereIn('created_by', function ($subQ) use ($ownerUserId) {
                    $subQ->select('id')->from('users')
                        ->where('id', $ownerUserId)
                        ->orWhere('created_by', $ownerUserId);
                });
            });
        }
        $source = $statusSourceQuery->first();
        if (!$source) {
            $source = LeadSource::firstOrCreate(
                ['name' => 'Facebook'],
                ['name' => 'Facebook', 'status' => 1, 'created_by' => $createdBy]
            );
        }
        $leadSourceId = $source->id;

        $leadStatusId = null;
        $statusQuery = LeadStatus::where('name', 'New');
        if ($ownerUserId) {
            $statusQuery->where(function ($q) use ($ownerUserId) {
                $q->whereIn('created_by', function ($subQ) use ($ownerUserId) {
                    $subQ->select('id')->from('users')
                        ->where('id', $ownerUserId)
                        ->orWhere('created_by', $ownerUserId);
                });
            });
        }
        $statusNew = $statusQuery->first();
        if (!$statusNew) {
            $statusNew = LeadStatus::firstOrCreate(
                ['name' => 'New', 'created_by' => $createdBy],
                ['name' => 'New', 'status' => 1]
            );
        }
        $leadStatusId = $statusNew->id;

        $name     = trim((string) ($extracted['full_name'] ?? 'Meta Enquiry'));
        if ($name === '') {
            $name = 'Meta Enquiry';
        }
        $email    = $extracted['email']    ?? null;
        $company  = $extracted['company_name'] ?? null;
        $message  = $extracted['message']  ?? '';

        $requirement = '';
        if ($message !== '') {
            $requirement = "Enquired via Meta Instant Form.\n\nAdditional Info:\n{$message}";
        } else {
            $requirement = "Enquired via Meta Instant Form.";
        }

        if (!empty($extracted['job_title'])) {
            $requirement .= "\nJob Title: {$extracted['job_title']}";
        }

        return DB::transaction(function () use (
            $extracted,
            $name,
            $phone,
            $email,
            $company,
            $requirement,
            $leadSourceId,
            $leadStatusId,
            $createdBy,
            $metaLeadId
        ) {
            $lead = Lead::create([
                'lead_id'          => Lead::generateLeadId(),
                'meta_lead_id'     => $metaLeadId !== '' ? $metaLeadId : null,
                'meta_form_id'     => $extracted['meta_form_id'] ?? null,
                'lead_name'        => $name,
                'company_name'     => $company,
                'phone_number'     => $phone,
                'whatsapp_number'  => $extracted['whatsapp_number'] ?? null,
                'email'            => $email,
                'lead_source_id'   => $leadSourceId,
                'lead_status_id'   => $leadStatusId,
                'requirement'      => $requirement,
                'created_by'       => $createdBy,
                'updated_by'       => $createdBy,
                'last_activity_at' => now(),
            ]);

            LeadNote::create([
                'lead_id'  => $lead->id,
                'note'     => "Lead received automatically via Meta Instant Form."
                              . ($metaLeadId ? "\nMeta Lead ID: {$metaLeadId}" : '')
                              . (!empty($extracted['created_time']) ? "\nSubmitted At: {$extracted['created_time']}" : ''),
                'added_by' => $createdBy,
            ]);

            LeadActivity::create([
                'lead_id'      => $lead->id,
                'type'         => 'lead_created',
                'description'  => "Lead {$lead->lead_id} was created automatically from Meta Instant Form submission.",
                'performed_by' => $createdBy,
            ]);

            Log::info('[MetaWebhook] Lead stored', [
                'lead_id'      => $lead->lead_id,
                'meta_lead_id' => $metaLeadId,
                'created_by'   => $createdBy,
            ]);

            return $lead;
        });
    }
}
