<?php
$j = json_decode(file_get_contents('pybot_openapi.json'), true);
foreach(array_keys($j['paths']) as $p) {
    if(strpos($p, 'widget') !== false || strpos($p, 'faq') !== false) {
        echo $p . "\n";
    }
}
