<?php

declare(strict_types=1);

/**
 * Migration: Criação da tabela individual de trilha de acessos de demanda (Audit Trail por Usuário / Visitante Externo).
 */
return [
    'up' => function (PDO $pdo): void {
        $driver = (string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'mysql') {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `domain_drop_demand_activity_logs` (
                    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `product_id` VARCHAR(64) NOT NULL,
                    `product_title` VARCHAR(255) NOT NULL,
                    `category_slug` VARCHAR(64) NOT NULL,
                    `action_type` VARCHAR(32) NOT NULL,
                    `user_type` VARCHAR(32) NOT NULL DEFAULT 'externo',
                    `customer_id` INT UNSIGNED NULL,
                    `customer_name` VARCHAR(150) NULL,
                    `customer_email` VARCHAR(190) NULL,
                    `session_id` VARCHAR(100) NULL,
                    `ip_address` VARCHAR(45) NULL,
                    `created_at` DATETIME NOT NULL,
                    INDEX `idx_act_user_type` (`user_type`),
                    INDEX `idx_act_product` (`product_id`),
                    INDEX `idx_act_customer_email` (`customer_email`),
                    INDEX `idx_act_created_at` (`created_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        } else {
            // SQLite
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS domain_drop_demand_activity_logs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    product_id TEXT NOT NULL,
                    product_title TEXT NOT NULL,
                    category_slug TEXT NOT NULL,
                    action_type TEXT NOT NULL,
                    user_type TEXT NOT NULL DEFAULT 'externo',
                    customer_id INTEGER NULL,
                    customer_name TEXT NULL,
                    customer_email TEXT NULL,
                    session_id TEXT NULL,
                    ip_address TEXT NULL,
                    created_at DATETIME NOT NULL
                );
                CREATE INDEX IF NOT EXISTS idx_act_user_type ON domain_drop_demand_activity_logs(user_type);
                CREATE INDEX IF NOT EXISTS idx_act_product ON domain_drop_demand_activity_logs(product_id);
                CREATE INDEX IF NOT EXISTS idx_act_customer_email ON domain_drop_demand_activity_logs(customer_email);
                CREATE INDEX IF NOT EXISTS idx_act_created_at ON domain_drop_demand_activity_logs(created_at);
            ");
        }
    },
    'down' => function (PDO $pdo): void {
        $pdo->exec("DROP TABLE IF EXISTS domain_drop_demand_activity_logs;");
    }
];
