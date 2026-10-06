<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$val = Illuminate\Support\Facades\DB::table("conversations")->whereNotNull("assignment_history")->value("assignment_history");
echo "History: " . $val . "\n";
