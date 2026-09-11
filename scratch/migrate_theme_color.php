<?php
require __DIR__ . '/../bootstrap.php';
$app = DomainSystem\Core\Application::getInstance();
$app->boot();
$db = $app->getContainer()->make(DomainSystem\Plugins\Database\Connection::class)->getPdo();
try {
    $db->exec("ALTER TABLE users ADD COLUMN theme_color VARCHAR(50) DEFAULT 'default'");
    echo "Coluna adicionada.\n";
} catch (\Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
