<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

if (!Schema::hasColumn('contacts', 'ip_address')) {
    Schema::table('contacts', function (Blueprint $table) {
        $table->string('ip_address')->nullable()->after('pincode');
    });
    echo "Column ip_address added.";
} else {
    echo "Column ip_address already exists.";
}
