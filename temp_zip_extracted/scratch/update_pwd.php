<?php
require 'bootstrap.php';
$app->boot();
$db = $app->getContainer()->make(\DomainSystem\Plugins\Database\Connection::class)->getPdo();
$hash = password_hash("admin123", PASSWORD_BCRYPT);
$db->exec("UPDATE users SET password = '$hash' WHERE email = 'admin@admin.com'");
echo "Password updated.\n";
