<?php
require 'bootstrap.php';
$app->boot();
$db = $app->getContainer()->make(\DomainSystem\Plugins\Database\Connection::class)->getPdo();
$stmt = $db->query("SELECT email, password, role FROM users");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
