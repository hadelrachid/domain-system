<?php

declare(strict_types=1);

return [
    'up' => function (\PDO $db) {
        $driver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);

        if ($driver === 'mysql') {
            $db->exec("
                CREATE TABLE IF NOT EXISTS domain_drop_catalog (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    aliexpress_id VARCHAR(64) NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    slug VARCHAR(255) NOT NULL,
                    cost_price_usd DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    sale_price_brl DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    main_image_url TEXT NOT NULL,
                    images TEXT NULL,
                    category VARCHAR(64) NOT NULL DEFAULT 'Geral',
                    description MEDIUMTEXT NULL,
                    rating DECIMAL(3,2) NOT NULL DEFAULT 4.80,
                    orders_count INT NOT NULL DEFAULT 0,
                    attributes TEXT NULL,
                    status VARCHAR(32) NOT NULL DEFAULT 'published',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_dd_ae_id (aliexpress_id),
                    UNIQUE KEY uq_dd_slug (slug),
                    INDEX idx_dd_cat (category),
                    INDEX idx_dd_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $db->exec("
                CREATE TABLE IF NOT EXISTS domain_drop_orders (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    order_ref VARCHAR(64) NOT NULL,
                    customer_name VARCHAR(128) NOT NULL,
                    customer_email VARCHAR(128) NOT NULL,
                    customer_document VARCHAR(32) NULL,
                    shipping_address TEXT NULL,
                    aliexpress_order_id VARCHAR(64) NULL,
                    tracking_code VARCHAR(64) NULL,
                    status VARCHAR(32) NOT NULL DEFAULT 'pending',
                    total_paid_brl DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_dd_order_ref (order_ref),
                    INDEX idx_dd_order_st (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } else {
            $db->exec("
                CREATE TABLE IF NOT EXISTS domain_drop_catalog (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    aliexpress_id VARCHAR(64) NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    slug VARCHAR(255) NOT NULL,
                    cost_price_usd DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    sale_price_brl DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    main_image_url TEXT NOT NULL,
                    images TEXT NULL,
                    category VARCHAR(64) NOT NULL DEFAULT 'Geral',
                    description TEXT NULL,
                    rating DECIMAL(3,2) NOT NULL DEFAULT 4.80,
                    orders_count INT NOT NULL DEFAULT 0,
                    attributes TEXT NULL,
                    status VARCHAR(32) NOT NULL DEFAULT 'published',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
            $db->exec("CREATE UNIQUE INDEX IF NOT EXISTS uq_dd_ae_id ON domain_drop_catalog(aliexpress_id)");
            $db->exec("CREATE UNIQUE INDEX IF NOT EXISTS uq_dd_slug ON domain_drop_catalog(slug)");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_dd_cat ON domain_drop_catalog(category)");

            $db->exec("
                CREATE TABLE IF NOT EXISTS domain_drop_orders (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    order_ref VARCHAR(64) NOT NULL,
                    customer_name VARCHAR(128) NOT NULL,
                    customer_email VARCHAR(128) NOT NULL,
                    customer_document VARCHAR(32) NULL,
                    shipping_address TEXT NULL,
                    aliexpress_order_id VARCHAR(64) NULL,
                    tracking_code VARCHAR(64) NULL,
                    status VARCHAR(32) NOT NULL DEFAULT 'pending',
                    total_paid_brl DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
            $db->exec("CREATE UNIQUE INDEX IF NOT EXISTS uq_dd_order_ref ON domain_drop_orders(order_ref)");
        }
    },
    'down' => function (\PDO $db) {
        // Política de retenção de dados
    },
];
