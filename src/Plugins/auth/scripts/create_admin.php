<?php

// Script auxiliar para criar o primeiro usuário
$dbPath = dirname(__DIR__, 4) . '/domain-system/database.sqlite'; // Ou pegar a conexão do kernel
// Como estamos fora do escopo, vamos carregar o bootstrap do kernel
$app = require_once dirname(__DIR__, 4) . '/domain-system/bootstrap.php';
$app->boot();

use DomainSystem\Plugins\Database\QueryBuilder;

$db = $app->getContainer()->make(QueryBuilder::class);

$email = "admin@daherclinica.com.br";
$password = "senha123";
$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $db->getPdo()->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $stmt = $db->getPdo()->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    $stmt->execute(['Administrador', $email, $hash]);

    echo "Usuário administrador criado com sucesso!\n";
    echo "E-mail: $email\n";
    echo "Senha: $password\n";
} catch (\Exception $e) {
    if (strpos($e->getMessage(), 'Integrity constraint violation') !== false) {
        echo "O usuário $email já existe.\n";
    } else {
        echo "Erro: " . $e->getMessage() . "\n";
    }
}
