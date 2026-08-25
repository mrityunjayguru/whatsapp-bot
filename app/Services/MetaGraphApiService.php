<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaGraphApiService
{
    private string $pageAccessToken;
    private string $graphVersion;
    private string $pageId;
    private ?int $companyId = null;

    public function __construct(
        ?string $pageAccessToken = null,
        ?string $graphVersion = null,
        ?string $pageId = null,
        ?int $companyId = null
    ) {
        $this->pageAccessToken = $pageAccessToken ?? (string) config('services.meta.page_access_token');
        $this->graphVersion    = $graphVersion    ?? (string) config('services.meta.graph_api_version');
        $this->pageId          = $pageId          ?? (string) config('services.meta.page_id');
        $this->companyId       = $companyId;
    }

    public function getBaseUrl(): string
    {
        return "https://graph.facebook.com/{$this->graphVersion}";
    }

    public function fetchLead(string $leadgenId): ?array
    {
        if (!$this->pageAccessToken) {
            Log::error('[MetaGraphAPI] Missing Page Access Token');
            return null;
        }

        $url = $this->getBaseUrl() . "/{$leadgenId}";

        try {
            $response = Http::asForm()->get($url, [
                'access_token' => $this->pageAccessToken,
                'fields'       => 'id,created_time,field_data,form_id,is_organic',
            ]);

            if (!$response->successful()) {
                Log::error('[MetaGraphAPI] Lead fetch failed', [
                    'leadgen_id' => $leadgenId,
                    'status'     => $response->status(),
                    'body'       => $response->body(),
                    'company_id' => $this->companyId,
                ]);
                return null;
            }

            $data = $response->json();
            Log::info('[MetaGraphAPI] Lead fetched', [
                'leadgen_id' => $leadgenId,
                'company_id' => $this->companyId,
                'form_id'    => $data['form_id'] ?? null,
            ]);

            return $data;
        } catch (\Throwable $e) {
            Log::error('[MetaGraphAPI] Lead fetch exception', [
                'leadgen_id' => $leadgenId,
                'company_id' => $this->companyId,
                'error'      => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function extractFields(array $leadData): array
    {
        $fieldData = $leadData['field_data'] ?? [];

        $mapped = [
            'meta_lead_id'  => $leadData['id'] ?? null,
            'meta_form_id'  => $leadData['form_id'] ?? null,
            'created_time'  => $leadData['created_time'] ?? null,
            'full_name'     => null,
            'first_name'    => null,
            'last_name'     => null,
            'phone_number'  => null,
            'email'         => null,
            'company_name'  => null,
            'street_address'=> null,
            'city'          => null,
            'state'         => null,
            'zip_code'      => null,
            'country'       => null,
            'job_title'     => null,
            'message'       => null,
            'raw_fields'    => $fieldData,
        ];

        foreach ($fieldData as $field) {
            $name  = $field['name'] ?? '';
            $value = $field['values'][0] ?? null;

            $value = is_array($value) ? implode(', ', $value) : $value;

            switch (strtolower($name)) {
                case 'full_name':
                case 'name':
                    $mapped['full_name'] = trim((string) $value);
                    break;
                case 'first_name':
                    $mapped['first_name'] = trim((string) $value);
                    break;
                case 'last_name':
                case 'surname':
                    $mapped['last_name'] = trim((string) $value);
                    break;
                case 'phone_number':
                case 'phone':
                case 'mobile_number':
                case 'whatsapp_number':
                    $phone = preg_replace('/\D+/', '', (string) $value);
                    if (str_starts_with($phone, '91') && strlen($phone) === 12) {
                        $phone = substr($phone, 2);
                    }
                    if (strlen($phone) === 10 && !str_starts_with($phone, '0')) {
                        $mapped['phone_number'] = $phone;
                    } else {
                        $mapped['phone_number'] = $phone;
                    }
                    if (strtolower($name) === 'whatsapp_number') {
                        $mapped['whatsapp_number'] = $mapped['phone_number'];
                    }
                    break;
                case 'email':
                case 'work_email':
                    $mapped['email'] = trim((string) $value);
                    break;
                case 'company_name':
                case 'company':
                    $mapped['company_name'] = trim((string) $value);
                    break;
                case 'street_address':
                case 'address':
                    $mapped['street_address'] = trim((string) $value);
                    break;
                case 'city':
                case 'city_name':
                    $mapped['city'] = trim((string) $value);
                    break;
                case 'state':
                case 'state_name':
                    $mapped['state'] = trim((string) $value);
                    break;
                case 'zip_code':
                case 'postal_code':
                    $mapped['zip_code'] = trim((string) $value);
                    break;
                case 'country':
                case 'country_name':
                    $mapped['country'] = trim((string) $value);
                    break;
                case 'job_title':
                case 'designation':
                    $mapped['job_title'] = trim((string) $value);
                    break;
                default:
                    if (is_string($value) && $value !== '') {
                        $appendLine = ucwords(str_replace('_', ' ', $name)) . ': ' . $value;
                        if ($mapped['message'] === null) {
                            $mapped['message'] = $appendLine;
                        } else {
                            $mapped['message'] .= "\n" . $appendLine;
                        }
                    }
                    break;
            }
        }

        if ($mapped['full_name'] === null) {
            $parts = [];
            if ($mapped['first_name']) $parts[] = $mapped['first_name'];
            if ($mapped['last_name'])  $parts[] = $mapped['last_name'];
            if (!empty($parts)) {
                $mapped['full_name'] = implode(' ', $parts);
            }
        }

        if ($mapped['full_name'] === null) {
            $mapped['full_name'] = 'Meta Enquiry';
        }

        return $mapped;
    }
}
