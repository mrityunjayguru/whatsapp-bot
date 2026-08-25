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
    public function verify(Request $request)
    {
        $mode      = $request->input('hub_mode');
        $token     = $request->input('hub_verify_token');
        $challenge = $request->input('hub_challenge');

        Log::info('[MetaWebhook] Verification attempt', [
            'mode'      => $mode,
            'has_token' => filled($token),
            'has_challenge' => filled($challenge),
        ]);

        $settings = $this->findAnySettings();
        $verifyToken = null;

        if ($settings) {
            $verifyToken = $settings->credential('webhook_verify_token');
        }
        if (!$verifyToken) {
            $verifyToken = config('services.meta.webhook_verify');
        }

        if ($mode === 'subscribe' && $token === $verifyToken) {
            Log::info('[MetaWebhook] Verified successfully');
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning('[MetaWebhook] Verification failed', [
            'mode'      => $mode,
            'expected'  => $verifyToken,
            'got'       => $token,
        ]);

        abort(403, 'Invalid verification token.');
    }

    public function handle(Request $request, MetaGraphApiService $graphService)
    {
        $payload = $request->all();

        Log::info('[MetaWebhook] Incoming event', [
            'ip'      => $request->ip(),
            'payload' => $payload,
        ]);

        $object = $payload['object'] ?? null;
        if ($object !== 'page') {
            Log::warning('[MetaWebhook] Ignoring non-page object', ['object' => $object]);
            return response('IGNORED', 200);
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
