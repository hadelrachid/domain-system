<?php
$f = dirname(__DIR__) . '/config/plugins.json';
$d = json_decode(file_get_contents($f), true);
$d['clinic_pack'] = true;
file_put_contents($f, json_encode($d, JSON_PRETTY_PRINT));
echo "clinic_pack reativado com sucesso!\n";
