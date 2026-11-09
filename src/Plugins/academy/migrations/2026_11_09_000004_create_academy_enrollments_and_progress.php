<?php

declare(strict_types=1);

return [
    'up' => function (\PDO $db) {
        $driver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);

        if ($driver === 'mysql') {
            $db->exec("
                CREATE TABLE IF NOT EXISTS academy_enrollments (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    course_id INT NOT NULL,
                    status VARCHAR(32) NOT NULL DEFAULT 'active',
                    expires_at DATETIME NULL,
                    order_ref VARCHAR(128) NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_user_course (user_id, course_id),
                    INDEX idx_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $db->exec("
                CREATE TABLE IF NOT EXISTS academy_progress (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    student_id INT NOT NULL,
                    course_id INT NOT NULL,
                    lesson_slug VARCHAR(128) NOT NULL,
                    completed_at DATETIME NOT NULL,
                    notes_encrypted TEXT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_student_lesson (student_id, course_id, lesson_slug),
                    INDEX idx_student_course (student_id, course_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } else {
            $db->exec("
                CREATE TABLE IF NOT EXISTS academy_enrollments (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    course_id INTEGER NOT NULL,
                    status VARCHAR(32) NOT NULL DEFAULT 'active',
                    expires_at DATETIME NULL,
                    order_ref VARCHAR(128) NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
            $db->exec("CREATE UNIQUE INDEX IF NOT EXISTS uq_academy_enrollments_uc ON academy_enrollments(user_id, course_id)");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_academy_enrollments_st ON academy_enrollments(status)");

            $db->exec("
                CREATE TABLE IF NOT EXISTS academy_progress (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    student_id INTEGER NOT NULL,
                    course_id INTEGER NOT NULL,
                    lesson_slug VARCHAR(128) NOT NULL,
                    completed_at DATETIME NOT NULL,
                    notes_encrypted TEXT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
            $db->exec("CREATE UNIQUE INDEX IF NOT EXISTS uq_academy_prog_st_cs_ls ON academy_progress(student_id, course_id, lesson_slug)");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_academy_prog_st_cs ON academy_progress(student_id, course_id)");
        }
    },
    'down' => function (\PDO $db) {
        // Política de integridade: tabelas com dados de alunos não são dropadas em rollback simples
    },
];
