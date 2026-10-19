<?php

declare(strict_types=1);

use PDO;

return [
    'up' => function (PDO $db) {
        $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $autoInc = $driver === 'sqlite' ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT AUTO_INCREMENT PRIMARY KEY';

        $db->exec("
            CREATE TABLE IF NOT EXISTS academy_modules (
                id {$autoInc},
                course_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                position INT NOT NULL DEFAULT 0,
                folder_slug VARCHAR(255) NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS academy_lessons (
                id {$autoInc},
                course_id INT NOT NULL,
                module_id INT NULL,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL,
                source_type VARCHAR(50) NOT NULL DEFAULT 'theme_file',
                source VARCHAR(255) NULL,
                content TEXT NULL,
                mobile_profile VARCHAR(50) NOT NULL DEFAULT 'drawer',
                position INT NOT NULL DEFAULT 0,
                status VARCHAR(50) NOT NULL DEFAULT 'published',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(course_id, slug)
            )
        ");

        // Garante colunas mobile_profile e folder_slug via SQL ANSI padrão (independente de ERRMODE ou driver)
        $hasMobileCol = false;
        try {
            $stmtMob = @$db->query("SELECT mobile_profile FROM academy_lessons LIMIT 1");
            $hasMobileCol = ($stmtMob !== false);
        } catch (\Throwable $e) {
            $hasMobileCol = false;
        }
        if (!$hasMobileCol) {
            try {
                $db->exec("ALTER TABLE academy_lessons ADD COLUMN mobile_profile VARCHAR(50) NOT NULL DEFAULT 'drawer'");
            } catch (\Throwable $inner) {
                // Coluna já existe ou não aplicável
            }
        }

        $hasFolderCol = false;
        try {
            $stmtFold = @$db->query("SELECT folder_slug FROM academy_modules LIMIT 1");
            $hasFolderCol = ($stmtFold !== false);
        } catch (\Throwable $e) {
            $hasFolderCol = false;
        }
        if (!$hasFolderCol) {
            try {
                $db->exec("ALTER TABLE academy_modules ADD COLUMN folder_slug VARCHAR(255) NULL");
            } catch (\Throwable $inner) {
                // Coluna já existe ou não aplicável
            }
        }
    },
    'down' => function (PDO $db) {
        $db->exec("DROP TABLE IF EXISTS academy_lessons");
        $db->exec("DROP TABLE IF EXISTS academy_modules");
    },
];
