<?php
require __DIR__ . '/../bootstrap.php';
$pdo = new PDO($_ENV['DB_DSN'], $_ENV['DB_USER'], $_ENV['DB_PASS']);
$stmt = $pdo->query("SELECT content FROM pages WHERE slug = 'tutoriais'");
echo $stmt->fetchColumn();
