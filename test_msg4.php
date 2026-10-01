<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$msgs = \App\Models\Message::latest()->take(5)->get();
foreach ($msgs as $msg) {
    echo "ID: {$msg->id} | Text: {$msg->message_text} | Sender: {$msg->sender_type}\n";
}
