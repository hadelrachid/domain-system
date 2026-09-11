<?php
define('BASE_PATH', dirname(__DIR__));
define('DOMAIN_SYSTEM_ROOT', dirname(__DIR__));
require_once BASE_PATH . '/vendor/autoload.php';

$app = require BASE_PATH . '/bootstrap.php';
$db = $app->getContainer()->make(\DomainSystem\Plugins\Database\Connection::class)->getPdo();

$stmt = $db->query("SELECT * FROM doctor_schedules");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
