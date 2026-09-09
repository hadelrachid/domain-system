<?php
$env = parse_ini_file(__DIR__ . '/../.env');
$db = new PDO($env['DB_DSN'], $env['DB_USER'], $env['DB_PASS']);
print_r($db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN));
