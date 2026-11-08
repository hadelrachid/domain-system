<?php

declare(strict_types=1);

return [
    'up' => function (\PDO $db) {
        $driver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);

        $colNames = [];
        if ($driver === 'sqlite') {
            $stmt = $db->query("PRAGMA table_info(users)");
            if ($stmt) {
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                $colNames = array_map('strtolower', array_column($rows, 'name'));
            }
        } else {
            $stmt = $db->query("SHOW COLUMNS FROM users");
            if ($stmt) {
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                $colNames = array_map('strtolower', array_column($rows, 'Field'));
            }
        }

        if (!in_array('username', $colNames, true)) {
            try {
                $db->exec("ALTER TABLE users ADD COLUMN username VARCHAR(100) NULL");
            } catch (\Throwable $e) {}
        }

        if (!in_array('status', $colNames, true)) {
            try {
                $db->exec("ALTER TABLE users ADD COLUMN status VARCHAR(20) DEFAULT 'active'");
            } catch (\Throwable $e) {}
        }
    },
    'down' => function (\PDO $db) {
        // Regra de integridade aditiva: colunas de dados não são dropadas em rollback
    },
];
