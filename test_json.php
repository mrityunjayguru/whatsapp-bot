<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$employeeId = 2; // Assuming employee 2 exists
try {
    $count = Illuminate\Support\Facades\DB::table("conversations")
        ->whereJsonContains("assignment_history", ["employee_id" => (string)$employeeId])
        ->orWhereJsonContains("assignment_history", ["employee_id" => $employeeId])
        ->count();
    echo "Count: " . $count . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
