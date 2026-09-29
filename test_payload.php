<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$m = \App\Models\Message::create([
    'tenant_id' => 1,
    'channel' => 'web_widget',
    'conversation_id' => 1,
    'contact_id' => 1,
    'message_type' => 'TEXT',
    'direction' => 'OUTBOUND',
    'sender_type' => 'SYSTEM',
    'message_text' => 'ended conversation with Sapna Das',
    'status' => 'SENT',
    'meta_message_id' => 'test_id_1'
]);
echo json_encode((new \App\Events\NewMessage($m))->broadcastWith());
