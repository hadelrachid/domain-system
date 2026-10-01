<?php
$json = json_decode(file_get_contents('config/plugins.json'), true);
$json['clinic_pack'] = true;
$json['Database'] = true;
file_put_contents('config/plugins.json', json_encode($json, JSON_PRETTY_PRINT));
echo "Enabled plugins in config.\n";
