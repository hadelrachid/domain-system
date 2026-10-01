<?php
require __DIR__ . '/../public/index.php';

$pdo = new PDO($_ENV['DB_DSN'], $_ENV['DB_USER'], $_ENV['DB_PASS'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$stmt = $pdo->prepare("SELECT id FROM plugins WHERE slug = 'seo'");
$stmt->execute();
if (!$stmt->fetch()) {
    $stmt = $pdo->prepare("INSERT INTO plugins (slug, name, active, version) VALUES ('seo', 'SEO Middleware Global', 1, '1.0.0')");
    $stmt->execute();
    echo "Plugin SEO ativado!\n";
} else {
    echo "Plugin SEO já existe.\n";
}
