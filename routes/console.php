<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('meta:test-lead', function () {
    $this->info('Testing Meta lead storage flow...');

    if (!\App\Models\User::find(1)) {
        $this->error('User ID 1 not found. Please create a user first.');
        return 1;
    }

    $extracted = [
        'meta_lead_id'  => 'TEST_' . time(),
        'meta_form_id'  => 'FORM_TEST_001',
        'created_time'  => now()->toIso8601String(),
        'full_name'     => 'Test Meta User',
        'first_name'    => 'Test',
        'last_name'     => 'Meta User',
        'phone_number'  => '9876543210',
        'whatsapp_number' => '9876543210',
        'email'         => 'test_meta_' . time() . '@example.com',
        'company_name'  => 'Meta Test Co.',
        'message'       => "Interested in your services.\nBudget: 50k",
        'job_title'     => 'Marketing Head',
        'raw_fields'    => [],
    ];

    $settings = \App\Models\MetaSetting::make([
        'company_id'         => null,
        'default_created_by' => (int) config('services.meta.default_user_id', 1),
    ]);

    $controller = app(\App\Http\Controllers\MetaWebhookController::class);
    $reflection = new \ReflectionClass($controller);
    $method = $reflection->getMethod('storeLead');
    $method->setAccessible(true);

    $lead = $method->invoke($controller, $extracted, $settings);

    if ($lead) {
        $this->info("SUCCESS! Lead created: {$lead->lead_id} (ID: {$lead->id})");
        $this->line("  Name: {$lead->lead_name}");
        $this->line("  Phone: {$lead->phone_number}");
        $this->line("  Email: {$lead->email}");
        $this->line("  Meta Lead ID: {$lead->meta_lead_id}");
        $this->line("  Source: " . ($lead->leadSource?->name ?? 'N/A'));
        $this->line("  Status: " . ($lead->leadStatus?->name ?? 'N/A'));
        return 0;
    }

    $this->error('Lead creation returned null.');
    return 1;
})->purpose('Test Meta lead storage flow by creating a dummy lead');
