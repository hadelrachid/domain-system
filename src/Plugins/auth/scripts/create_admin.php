<?php

// Script auxiliar para criar o primeiro usuário
// Caminho absoluto para o bootstrap do sistema
$bootstrapPath = dirname(__DIR__, 3) . '/bootstrap.php';
if (!file_exists($bootstrapPath)) {
    // Se a pasta Plugins não for src/Plugins, tenta o nível correto
    $bootstrapPath = dirname(__DIR__, 4) . '/bootstrap.php';
}

$app = require_once $bootstrapPath;
$app->boot();

use DomainSystem\Plugins\Database\Connection;
use DomainSystem\Plugins\Database\QueryBuilder;

/** @var Connection $connection */
$connection = $app->getContainer()->make(Connection::class);
/** @var QueryBuilder $db */
$db = $app->getContainer()->make(QueryBuilder::class);

$email = "admin@daherclinica.com.br";
$password = "senha123";
$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $connection->getPdo()->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Limpa a tabela para testes se já existir
    $connection->getPdo()->exec("DELETE FROM users WHERE email = '$email'");

    $db->table('users')->insert([
        'name' => 'Administrador',
        'email' => $email,
        'password' => $hash
    ]);

    echo "=========================================\n";
    echo "Usuário administrador criado com sucesso!\n";
    echo "E-mail: $email\n";
    echo "Senha: $password\n";
    echo "=========================================\n";
} catch (\Exception $e) {
    if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
        echo "O usuário $email já existe no banco de dados SQLite.\n";
    } else {
        echo "Erro fatal no banco: " . $e->getMessage() . "\n";
    }
}
