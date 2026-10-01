<?php
require 'vendor/autoload.php';
$env = parse_ini_file('.env', false, INI_SCANNER_RAW);
$dsn = $env['DB_DSN'];
$user = $env['DB_USER'];
$pass = $env['DB_PASS'];
$pdo = new PDO($dsn, $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
try {
    $pdo->exec("ALTER TABLE pages ADD COLUMN theme VARCHAR(255) NULL");
    $pdo->exec("ALTER TABLE pages ADD COLUMN template_file VARCHAR(255) NULL");
    echo "OK";
} catch (Exception $e) {
    echo $e->getMessage();
}
