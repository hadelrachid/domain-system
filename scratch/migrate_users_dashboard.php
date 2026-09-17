<?php
require 'bootstrap.php';
$app->boot();

$db = $app->getContainer()->make(\DomainSystem\Plugins\Database\Connection::class)->getPdo();

try {
    $db->exec("ALTER TABLE users ADD COLUMN dashboard_layout TEXT NULL");
    echo "Coluna dashboard_layout adicionada com sucesso.\n";
} catch (\PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "A coluna já existe.\n";
    } else {
        echo "Erro: " . $e->getMessage() . "\n";
    }
}
