<?php

use PDO;

return [
    'up' => function (PDO $db) {
        $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $autoInc = $driver === 'sqlite' ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT AUTO_INCREMENT PRIMARY KEY';

        $db->exec("
            CREATE TABLE IF NOT EXISTS academy_courses (
                id {$autoInc},
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL UNIQUE,
                thumbnail VARCHAR(255) NULL,
                description TEXT NULL,
                status VARCHAR(50) NOT NULL DEFAULT 'draft',
                link VARCHAR(255) NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
    },
    'down' => function (PDO $db) {
        $db->exec("DROP TABLE IF EXISTS academy_courses");
    },
];
