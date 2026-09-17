<?php
require 'bootstrap.php';
$app->boot();
$db = $app->getContainer()->make(\DomainSystem\Plugins\Database\Connection::class)->getPdo();
$hash = password_hash("admin123", PASSWORD_BCRYPT);
$db->exec("UPDATE users SET password = '$hash' WHERE email = 'hadelrachid@gmail.com'");
echo "Senha do hadelrachid resetada com sucesso!\n";
