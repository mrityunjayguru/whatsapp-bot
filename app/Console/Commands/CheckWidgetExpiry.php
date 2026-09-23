<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use App\Services\WidgetApiService;
use Carbon\Carbon;

class CheckWidgetExpiry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'widgets:check-expiry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for expired widgets and disable them via the Python API';

    /**
     * Execute the console command.
     */
    public function handle(WidgetApiService $api)
    {
        $today = Carbon::today()->toDateString();

        // Find all active companies that have an expiry_date in the past
        $expiredCompanies = Company::where('is_active', true)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', $today)
            ->whereNotNull('widget_token')
            ->get();

        if ($expiredCompanies->isEmpty()) {
            $this->info('No expired widgets found.');
            return 0;
        }

        foreach ($expiredCompanies as $company) {
            $this->info("Disabling expired widget for company: {$company->name} (Token: {$company->widget_token})");
            
            // 1. Update the Python API to turn it off
            $existingConfig = $api->getWidgetConfig($company->widget_token);
            if ($existingConfig) {
                $existingConfig['is_active'] = false;
                $api->updateWidgetConfig($company->widget_token, $existingConfig);
            }

            // 2. Update the local Laravel database
            $company->update(['is_active' => false]);
        }

        $this->info('Widget expiry check completed.');
        return 0;
    }
}
