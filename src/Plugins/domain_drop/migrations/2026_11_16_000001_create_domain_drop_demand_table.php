<?php

declare(strict_types=1);

/**
 * Migration: Criação da tabela de rastreamento de demanda e livro de presença do DomainDrop.
 */
return [
    'up' => function (PDO $pdo): void {
        $driver = (string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'mysql') {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `domain_drop_product_demand` (
                    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `product_id` VARCHAR(64) NOT NULL,
                    `product_title` VARCHAR(255) NOT NULL,
                    `category_slug` VARCHAR(64) NOT NULL,
                    `period_year_month` VARCHAR(7) NOT NULL,
                    `detail_clicks` INT UNSIGNED NOT NULL DEFAULT 0,
                    `buy_clicks` INT UNSIGNED NOT NULL DEFAULT 0,
                    `conversions` INT UNSIGNED NOT NULL DEFAULT 0,
                    `demand_score` INT UNSIGNED NOT NULL DEFAULT 0,
                    `last_searched_at` DATETIME NOT NULL,
                    UNIQUE KEY `uk_drop_demand_prod_period` (`product_id`, `period_year_month`),
                    INDEX `idx_drop_demand_period_score` (`period_year_month`, `demand_score` DESC),
                    INDEX `idx_drop_demand_category` (`category_slug`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        } else {
            // SQLite
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS domain_drop_product_demand (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    product_id TEXT NOT NULL,
                    product_title TEXT NOT NULL,
                    category_slug TEXT NOT NULL,
                    period_year_month TEXT NOT NULL,
                    detail_clicks INTEGER NOT NULL DEFAULT 0,
                    buy_clicks INTEGER NOT NULL DEFAULT 0,
                    conversions INTEGER NOT NULL DEFAULT 0,
                    demand_score INTEGER NOT NULL DEFAULT 0,
                    last_searched_at DATETIME NOT NULL,
                    CONSTRAINT uk_drop_demand_prod_period UNIQUE (product_id, period_year_month)
                );
                CREATE INDEX IF NOT EXISTS idx_drop_demand_period_score ON domain_drop_product_demand(period_year_month, demand_score);
                CREATE INDEX IF NOT EXISTS idx_drop_demand_category ON domain_drop_product_demand(category_slug);
            ");
        }
    },
    'down' => function (PDO $pdo): void {
        $pdo->exec("DROP TABLE IF EXISTS domain_drop_product_demand;");
    }
];
