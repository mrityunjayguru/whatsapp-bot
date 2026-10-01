<?php
$f1 = 'c:\xampp\htdocs\chatbot\app\Http\Controllers\ConversationController.php';
$c = file_get_contents($f1);
$c = str_replace('"ended conversation"', '"Ended conversation"', $c);
$c = str_replace('"ended conversation with "', '"Ended conversation with "', $c);
file_put_contents($f1, $c);

$f2 = 'c:\xampp\htdocs\chatbot\app\Console\Commands\CloseIdleConversations.php';
$c = file_get_contents($f2);
$c = str_replace('"ended conversation"', '"Ended conversation"', $c);
file_put_contents($f2, $c);

$f3 = 'c:\xampp\htdocs\chatbot\public\widget.js';
$c = file_get_contents($f3);
$c = str_replace("txt.startsWith('ended conversation')", "txt.toLowerCase().startsWith('ended conversation')", $c);
$c = str_replace(".ws-event-bub{background:#e2e8f0;color:#475569;font-size:11px;font-weight:600;padding:4px 12px;border-radius:12px;text-align:center}", ".ws-event-bub{background:#e2e8f0;color:#000;font-size:12px;font-weight:bold;padding:4px 12px;border-radius:12px;text-align:center}", $c);
file_put_contents($f3, $c);

$f4 = 'c:\xampp\htdocs\chatbot\app\Models\Message.php';
$c = file_get_contents($f4);
$c = str_replace("str_starts_with(\$text, 'ended conversation')", "str_starts_with(strtolower(\$text), 'ended conversation')", $c);
file_put_contents($f4, $c);

$f5 = 'c:\xampp\htdocs\chatbot\resources\views\conversations\show.blade.php';
$c = file_get_contents($f5);
$c = str_replace("txt.startsWith('ended conversation')", "txt.toLowerCase().startsWith('ended conversation')", $c);
file_put_contents($f5, $c);

echo "Done";
