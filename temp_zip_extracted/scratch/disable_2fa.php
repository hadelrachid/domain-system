<?php
$env = parse_ini_file(__DIR__ . '/../.env');
$db = new PDO($env['DB_DSN'], $env['DB_USER'], $env['DB_PASS']);
$db->query("UPDATE users SET two_factor_type = 'none'");
echo "2FA desativado para todos.\n";
