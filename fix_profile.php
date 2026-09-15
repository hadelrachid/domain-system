<?php
$app = require 'bootstrap.php';
$app->getDispatcher()->dispatch('kernel_pre_boot');
$app->boot();
$db = $app->getContainer()->make(\DomainSystem\Plugins\Database\Connection::class)->getPdo();
$db->exec("ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) NULL");
echo "OK";
