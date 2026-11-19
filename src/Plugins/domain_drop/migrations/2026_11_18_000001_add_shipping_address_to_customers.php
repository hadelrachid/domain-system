<?php

declare(strict_types=1);

return [
    'up' => function (\PDO $db) {
        $driver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);

        if ($driver === 'mysql') {
            try {
                $colsStmt = $db->query("SHOW COLUMNS FROM domain_drop_customers");
                $existingCols = $colsStmt ? array_column($colsStmt->fetchAll(\PDO::FETCH_ASSOC), 'Field') : [];
            } catch (\Throwable $e) {
                $existingCols = [];
            }

            $alterColumns = [
                'shipping_zip'             => "ALTER TABLE domain_drop_customers ADD COLUMN shipping_zip VARCHAR(20) NULL",
                'shipping_address'         => "ALTER TABLE domain_drop_customers ADD COLUMN shipping_address VARCHAR(255) NULL",
                'shipping_number'          => "ALTER TABLE domain_drop_customers ADD COLUMN shipping_number VARCHAR(50) NULL",
                'shipping_complement'      => "ALTER TABLE domain_drop_customers ADD COLUMN shipping_complement VARCHAR(100) NULL",
                'shipping_neighborhood'    => "ALTER TABLE domain_drop_customers ADD COLUMN shipping_neighborhood VARCHAR(100) NULL",
                'shipping_city'            => "ALTER TABLE domain_drop_customers ADD COLUMN shipping_city VARCHAR(100) NULL",
                'shipping_state'           => "ALTER TABLE domain_drop_customers ADD COLUMN shipping_state VARCHAR(10) NULL",
                'default_shipping_address' => "ALTER TABLE domain_drop_customers ADD COLUMN default_shipping_address TEXT NULL",
            ];

            foreach ($alterColumns as $col => $sql) {
                if (!empty($existingCols) && !in_array($col, $existingCols, true)) {
                    $db->exec($sql);
                }
            }
        } else {
            // SQLite
            try {
                $colsStmt = $db->query("PRAGMA table_info(domain_drop_customers)");
                $existingCols = $colsStmt ? array_column($colsStmt->fetchAll(\PDO::FETCH_ASSOC), 'name') : [];
            } catch (\Throwable $e) {
                $existingCols = [];
            }

            $alterColumns = [
                'shipping_zip'             => "ALTER TABLE domain_drop_customers ADD COLUMN shipping_zip TEXT NULL",
                'shipping_address'         => "ALTER TABLE domain_drop_customers ADD COLUMN shipping_address TEXT NULL",
                'shipping_number'          => "ALTER TABLE domain_drop_customers ADD COLUMN shipping_number TEXT NULL",
                'shipping_complement'      => "ALTER TABLE domain_drop_customers ADD COLUMN shipping_complement TEXT NULL",
                'shipping_neighborhood'    => "ALTER TABLE domain_drop_customers ADD COLUMN shipping_neighborhood TEXT NULL",
                'shipping_city'            => "ALTER TABLE domain_drop_customers ADD COLUMN shipping_city TEXT NULL",
                'shipping_state'           => "ALTER TABLE domain_drop_customers ADD COLUMN shipping_state TEXT NULL",
                'default_shipping_address' => "ALTER TABLE domain_drop_customers ADD COLUMN default_shipping_address TEXT NULL",
            ];

            foreach ($alterColumns as $col => $sql) {
                if (!empty($existingCols) && !in_array($col, $existingCols, true)) {
                    $db->exec($sql);
                }
            }
        }
    },

    'down' => function (\PDO $db) {
        // Retenção de dados segura
    },
];
