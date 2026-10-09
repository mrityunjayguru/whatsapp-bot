<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$response = Illuminate\Support\Facades\Http::withHeaders([
    'X-Api-Key' => config('services.bot_api.key')
])->post('http://127.0.0.1:5000/whatsapp/numbers/1216945994830075/bot/reply', [
    'message' => 'test question',
    'profile_name' => 'Test User',
    'history' => []
]);
dump($response->json());
