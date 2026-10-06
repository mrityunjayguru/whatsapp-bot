<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$w = Illuminate\Support\Facades\DB::table("widgets")->where("token", "88900fe886b24b17")->first();
echo json_encode($w);
