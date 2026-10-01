<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$msg = \App\Models\Message::latest()->first();
echo json_encode(['text' => $msg->message_text, 'sender_type' => $msg->sender_type, 'isEvent' => \App\Models\Message::isConversationEvent($msg->sender_type, $msg->message_text)]);
