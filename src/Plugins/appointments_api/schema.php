<?php
require_once dirname(__DIR__, 3) . "/bootstrap.php";
$app = \DomainSystem\Core\Application::getInstance();
$app->boot();

$db = $app->getContainer()->make(\DomainSystem\Plugins\Database\Connection::class);
$pdo = $db->getPdo();

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS doctors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            specialty VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS doctor_schedules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            doctor_id INT NOT NULL,
            day_of_week INT NOT NULL, -- 0=Sunday, 1=Monday, ...
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            slot_duration INT NOT NULL DEFAULT 30, -- minutes
            FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS appointments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            doctor_id INT NOT NULL,
            patient_name VARCHAR(255) NOT NULL,
            patient_email VARCHAR(255) NOT NULL,
            patient_phone VARCHAR(50) NOT NULL,
            appointment_date DATE NOT NULL,
            appointment_time TIME NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'Aguardando Confirmacao',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    echo "Tabelas criadas com sucesso no MySQL!\n";

} catch (\Exception $e) {
    echo "Erro ao criar tabelas: " . $e->getMessage() . "\n";
}

