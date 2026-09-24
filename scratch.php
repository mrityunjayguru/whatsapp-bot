<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$companies = \App\Models\Company::select('id', 'name', 'bot_usage_type', 'widget_token')->get();
foreach($companies as $c) {
    echo "ID: {$c->id}, Name: {$c->name}, Bot: {$c->bot_usage_type}, Token: {$c->widget_token}\n";
}