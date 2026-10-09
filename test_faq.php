<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$api = app(\App\Services\FaqApiService::class);
$api->setCompanyId(1001);
dump($api->uploadText('test question', 'test answer', null, false, ['test']));
