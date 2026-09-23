<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$employees = \App\Models\Employee::all();
foreach ($employees as $emp) {
    \App\Models\User::firstOrCreate(
        ['email' => $emp->email],
        ['name' => $emp->display_name, 'password' => $emp->password_hash]
    );
}
echo "Done sync\n";
