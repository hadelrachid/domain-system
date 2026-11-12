<?php

declare(strict_types=1);

return [
    'up' => function (\PDO $db) {
        $driver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);

        if ($driver === 'mysql') {
            $db->exec("
                CREATE TABLE IF NOT EXISTS domain_drop_customers (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(150) NOT NULL,
                    email VARCHAR(190) NOT NULL,
                    cpf VARCHAR(20) NULL,
                    phone VARCHAR(30) NULL,
                    password_hash VARCHAR(255) NOT NULL,
                    status VARCHAR(32) NOT NULL DEFAULT 'active',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_dd_customer_email (email),
                    INDEX idx_dd_customer_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            $db->exec("
                CREATE TABLE IF NOT EXISTS domain_drop_disputes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    order_ref VARCHAR(64) NOT NULL,
                    customer_id INT NULL,
                    customer_email VARCHAR(190) NOT NULL,
                    reason VARCHAR(120) NOT NULL DEFAULT 'produto_danificado',
                    description TEXT NOT NULL,
                    attachments_json MEDIUMTEXT NULL,
                    seller_origin VARCHAR(8) NOT NULL DEFAULT 'CN',
                    seller_name VARCHAR(150) NULL,
                    seller_email VARCHAR(190) NULL,
                    seller_language VARCHAR(16) NULL,
                    translated_message MEDIUMTEXT NULL,
                    mediation_status VARCHAR(32) NOT NULL DEFAULT 'open',
                    mediation_channel VARCHAR(64) NULL,
                    mediation_sent_at DATETIME NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_dd_disp_order_ref (order_ref),
                    INDEX idx_dd_disp_email (customer_email),
                    INDEX idx_dd_disp_status (mediation_status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            try {
                $colsStmt = $db->query("SHOW COLUMNS FROM domain_drop_orders");
                $existingCols = $colsStmt ? array_column($colsStmt->fetchAll(\PDO::FETCH_ASSOC), 'Field') : [];
            } catch (\Throwable $e) {
                $existingCols = [];
            }

            if (!empty($existingCols)) {
                $alterColumns = [
                    'seller_origin'          => "ALTER TABLE domain_drop_orders ADD COLUMN seller_origin VARCHAR(8) NOT NULL DEFAULT 'BR'",
                    'seller_name'            => "ALTER TABLE domain_drop_orders ADD COLUMN seller_name VARCHAR(150) NULL",
                    'seller_email'           => "ALTER TABLE domain_drop_orders ADD COLUMN seller_email VARCHAR(190) NULL",
                    'expected_delivery_days' => "ALTER TABLE domain_drop_orders ADD COLUMN expected_delivery_days INT NOT NULL DEFAULT 7",
                    'sdk_delivered_at'       => "ALTER TABLE domain_drop_orders ADD COLUMN sdk_delivered_at DATETIME NULL",
                    'sdk_tracking_summary'   => "ALTER TABLE domain_drop_orders ADD COLUMN sdk_tracking_summary TEXT NULL",
                    'product_slug'           => "ALTER TABLE domain_drop_orders ADD COLUMN product_slug VARCHAR(255) NULL",
                    'product_title'          => "ALTER TABLE domain_drop_orders ADD COLUMN product_title VARCHAR(255) NULL",
                    'aliexpress_product_id'  => "ALTER TABLE domain_drop_orders ADD COLUMN aliexpress_product_id VARCHAR(64) NULL",
                ];

                foreach ($alterColumns as $col => $sql) {
                    if (!in_array($col, $existingCols, true)) {
                        $db->exec($sql);
                    }
                }
            }
        } else {
            // SQLite
            $db->exec("
                CREATE TABLE IF NOT EXISTS domain_drop_customers (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    email TEXT NOT NULL UNIQUE,
                    cpf TEXT NULL,
                    phone TEXT NULL,
                    password_hash TEXT NOT NULL,
                    status TEXT NOT NULL DEFAULT 'active',
                    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
                );
            ");

            $db->exec("
                CREATE TABLE IF NOT EXISTS domain_drop_disputes (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    order_ref TEXT NOT NULL,
                    customer_id INTEGER NULL,
                    customer_email TEXT NOT NULL,
                    reason TEXT NOT NULL DEFAULT 'produto_danificado',
                    description TEXT NOT NULL,
                    attachments_json TEXT NULL,
                    seller_origin TEXT NOT NULL DEFAULT 'CN',
                    seller_name TEXT NULL,
                    seller_email TEXT NULL,
                    seller_language TEXT NULL,
                    translated_message TEXT NULL,
                    mediation_status TEXT NOT NULL DEFAULT 'open',
                    mediation_channel TEXT NULL,
                    mediation_sent_at TEXT NULL,
                    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
                );
            ");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_dd_disp_order_ref ON domain_drop_disputes(order_ref);");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_dd_disp_email ON domain_drop_disputes(customer_email);");

            $colsStmt = $db->query("PRAGMA table_info(domain_drop_orders)");
            $existingCols = $colsStmt ? array_column($colsStmt->fetchAll(\PDO::FETCH_ASSOC), 'name') : [];

            $alterColumns = [
                'seller_origin'          => "ALTER TABLE domain_drop_orders ADD COLUMN seller_origin TEXT NOT NULL DEFAULT 'BR'",
                'seller_name'            => "ALTER TABLE domain_drop_orders ADD COLUMN seller_name TEXT NULL",
                'seller_email'           => "ALTER TABLE domain_drop_orders ADD COLUMN seller_email TEXT NULL",
                'expected_delivery_days' => "ALTER TABLE domain_drop_orders ADD COLUMN expected_delivery_days INTEGER NOT NULL DEFAULT 7",
                'sdk_delivered_at'       => "ALTER TABLE domain_drop_orders ADD COLUMN sdk_delivered_at TEXT NULL",
                'sdk_tracking_summary'   => "ALTER TABLE domain_drop_orders ADD COLUMN sdk_tracking_summary TEXT NULL",
                'product_slug'           => "ALTER TABLE domain_drop_orders ADD COLUMN product_slug TEXT NULL",
                'product_title'          => "ALTER TABLE domain_drop_orders ADD COLUMN product_title TEXT NULL",
                'aliexpress_product_id'  => "ALTER TABLE domain_drop_orders ADD COLUMN aliexpress_product_id TEXT NULL",
            ];

            foreach ($alterColumns as $col => $sql) {
                if (!empty($existingCols) && !in_array($col, $existingCols, true)) {
                    $db->exec($sql);
                }
            }
        }
    },

    'down' => function (\PDO $db) {
        $db->exec("DROP TABLE IF EXISTS domain_drop_disputes");
        $db->exec("DROP TABLE IF EXISTS domain_drop_customers");
    },
];
