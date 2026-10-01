<?php
$env = parse_ini_file(__DIR__ . '/../.env');
$db = new PDO($env['DB_DSN'], $env['DB_USER'], $env['DB_PASS']);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$db->exec("
    CREATE TABLE IF NOT EXISTS financial_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(20) NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        description TEXT NOT NULL,
        due_date DATE NOT NULL,
        status VARCHAR(20) DEFAULT 'PENDING',
        patient_id INTEGER NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");
echo "Done.\n";
