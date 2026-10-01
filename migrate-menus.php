<?php
require 'bootstrap.php';
$app = DomainSystem\Core\Application::getInstance();
$db = $app->getContainer()->make(DomainSystem\Plugins\Database\Connection::class)->getPdo();
$db->exec('CREATE TABLE IF NOT EXISTS theme_menus (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(255), location VARCHAR(255) UNIQUE, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)');
$db->exec('CREATE TABLE IF NOT EXISTS theme_menu_items (id INTEGER PRIMARY KEY AUTOINCREMENT, menu_id INTEGER, title VARCHAR(255), url VARCHAR(255), page_id INTEGER, order_index INTEGER DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)');
echo 'Tabelas criadas com sucesso!';
