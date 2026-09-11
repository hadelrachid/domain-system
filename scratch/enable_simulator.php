<?php
$json = json_decode(file_get_contents('config/plugins.json'), true);
$json['dev_simulator'] = true;
file_put_contents('config/plugins.json', json_encode($json, JSON_PRETTY_PRINT));
echo "Enabled dev_simulator.\n";
