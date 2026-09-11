<?php
define('DOMAIN_SYSTEM_ROOT', dirname(__DIR__));
require_once DOMAIN_SYSTEM_ROOT . '/vendor/autoload.php';

try {
    $app = require DOMAIN_SYSTEM_ROOT . '/bootstrap.php';
    $app->boot(); // Boot to run plugins
    echo "Application bootstrapped successfully.\n";
    
    // Test DB Connection
    $db = $app->getContainer()->make(\DomainSystem\Plugins\Database\Connection::class)->getPdo();
    echo "Connected to MySQL successfully!\n";
    
    // Check tables
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables in DB: " . implode(", ", $tables) . "\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
