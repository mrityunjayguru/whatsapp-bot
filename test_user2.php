<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = Illuminate\Support\Facades\DB::table("users")->where("id", ">", 1)->first();
echo json_encode($user, JSON_PRETTY_PRINT);
