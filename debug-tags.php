<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $user = \App\Models\User::where('email', 'cpsc@creativedemonz.com')->first();
    echo "User company_id: " . ($user ? $user->company_id : 'Not Found') . "\n";
    $tags = \App\Models\Tag::where('tenant_id', $user->company_id)->get();
    echo "Found tags for tenant " . $user->company_id . ": " . count($tags) . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
