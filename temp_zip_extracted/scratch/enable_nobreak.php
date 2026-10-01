<?php
$c = json_decode(file_get_contents('config/plugins.json'), true);
$c['nobreak_shield'] = true;
file_put_contents('config/plugins.json', json_encode($c, JSON_PRETTY_PRINT));
echo 'Done';
