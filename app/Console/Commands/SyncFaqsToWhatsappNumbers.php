<?php

namespace App\Console\Commands;

use App\Models\Faq;
use App\Models\WhatsappNumber;
use App\Services\WhatsappNumberApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncFaqsToWhatsappNumbers extends Command
{
    protected $signature = 'faq:sync-to-whatsapp {--company= : Company ID to sync (all companies if not set)} {--wipe : Wipe ALL existing sources first before re-uploading}';
    protected $description = 'Sync all existing FAQs from company store to each WhatsApp number own store';

    public function handle(WhatsappNumberApiService $numApi): int
    {
        $companyId = $this->option('company');
        $wipe = $this->option('wipe');

        $numbersQuery = WhatsappNumber::query();
        if ($companyId) {
            $numbersQuery->where('company_id', $companyId);
        }
        $numbers = $numbersQuery->get();

        if ($numbers->isEmpty()) {
            $this->error('No WhatsApp numbers found.');
            return 1;
        }

        foreach ($numbers as $number) {
            $this->info("Processing number: {$number->phone_number_id} (company: {$number->company_id})");

            // Wipe ALL existing sources to remove duplicates
            $existingSources = $numApi->listFaqSources($number->phone_number_id);
            $this->info("  -> Found " . count($existingSources) . " existing sources. Deleting all...");
            foreach ($existingSources as $source) {
                $sourceId = $source['id'] ?? $source['source_id'] ?? null;
                if ($sourceId) {
                    $numApi->deleteFaqSource($number->phone_number_id, $sourceId);
                }
            }
            $this->info("  -> Cleared existing sources.");

            $faqs = Faq::where('tenant_id', $number->company_id)->where('is_active', true)->get();

            if ($faqs->isEmpty()) {
                $this->warn("  -> No active FAQs for company {$number->company_id}");
                continue;
            }

            foreach ($faqs as $faq) {
                try {
                    $keywords = [];
                    if (!empty($faq->keywords)) {
                        $keywords = array_filter(array_map('trim', explode(',', $faq->keywords)));
                    }
                    $sourceUrl = $faq->attachment ?? $faq->url ?? null;
                    $plainAnswer = \App\Http\Controllers\FaqController::formatHtmlForWhatsapp($faq->answer);

                    $result = $numApi->uploadFaqText(
                        $number->phone_number_id,
                        $faq->question,
                        $plainAnswer,
                        $sourceUrl,
                        false,
                        $keywords
                    );

                    if ($result) {
                        $this->info("  -> Synced: {$faq->question}");
                    } else {
                        $this->warn("  -> FAILED: {$faq->question}");
                    }
                } catch (\Throwable $e) {
                    $this->error("  -> Error syncing FAQ [{$faq->id}]: " . $e->getMessage());
                    Log::error('SyncFaqsToWhatsappNumbers failed', ['faq_id' => $faq->id, 'error' => $e->getMessage()]);
                }
            }

            $this->info("  -> Done for {$number->phone_number_id}");
        }

        $this->info('Sync complete!');
        return 0;
    }
}
