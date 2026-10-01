<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = Illuminate\Support\Facades\DB::table("users")->first();
echo json_encode($user, JSON_PRETTY_PRINT);
