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

        // Check for expired widgets in the widgets table (multi-widget architecture)
        $expiredWidgets = \App\Models\Widget::where('is_active', true)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', $today)
            ->whereNotNull('token')
            ->get();

        if ($expiredWidgets->isEmpty()) {
            $this->info('No expired widgets found.');
            return 0;
        }

        foreach ($expiredWidgets as $widget) {
            $this->info("Disabling expired widget (Token: {$widget->token})");
            
            // 1. Update the Python API to turn this widget off
            $existingConfig = $api->getWidgetConfig($widget->token);
            if ($existingConfig) {
                $existingConfig['is_active'] = false;
                $api->updateWidgetConfig($widget->token, $existingConfig);
            }

            // 2. Update just this widget's row (NEVER deactivate the entire company)
            $widget->update(['is_active' => false]);
        }

        $this->info('Widget expiry check completed.');
        return 0;
    }
}
