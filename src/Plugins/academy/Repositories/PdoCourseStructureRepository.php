<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Repositories;

use DomainSystem\Plugins\academy\Contracts\CourseStructureRepositoryInterface;
use PDO;
use Throwable;

class PdoCourseStructureRepository implements CourseStructureRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->ensureTables();
    }

    public function getModules(int $courseId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM academy_modules 
            WHERE course_id = ? 
            ORDER BY position ASC, id ASC
        ");
        $stmt->execute([$courseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function createModule(int $courseId, string $title, int $position = 0): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO academy_modules (course_id, title, position) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$courseId, $title, $position]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updateModule(int $moduleId, array $data): bool
    {
        $fields = [];
        $values = [];

        if (isset($data['title'])) {
            $fields[] = 'title = ?';
            $values[] = $data['title'];
        }
        if (isset($data['position'])) {
            $fields[] = 'position = ?';
            $values[] = (int)$data['position'];
        }

        if (empty($fields)) {
            return false;
        }

        $values[] = $moduleId;
        $sql = "UPDATE academy_modules SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($values);
    }

    public function deleteModule(int $moduleId): bool
    {
        // Ao excluir módulo, desvincula as aulas para não perdê-las (ou exclui dependendo da regra)
        $stmt = $this->pdo->prepare("UPDATE academy_lessons SET module_id = NULL WHERE module_id = ?");
        $stmt->execute([$moduleId]);

        $del = $this->pdo->prepare("DELETE FROM academy_modules WHERE id = ?");
        return $del->execute([$moduleId]);
    }

    public function getLessons(int $courseId, ?int $moduleId = null): array
    {
        if ($moduleId !== null) {
            $stmt = $this->pdo->prepare("
                SELECT * FROM academy_lessons 
                WHERE course_id = ? AND module_id = ? 
                ORDER BY position ASC, id ASC
            ");
            $stmt->execute([$courseId, $moduleId]);
        } else {
            $stmt = $this->pdo->prepare("
                SELECT * FROM academy_lessons 
                WHERE course_id = ? 
                ORDER BY position ASC, id ASC
            ");
            $stmt->execute([$courseId]);
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getLessonById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM academy_lessons WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getLessonBySlug(int $courseId, string $slug): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM academy_lessons WHERE course_id = ? AND slug = ?");
        $stmt->execute([$courseId, $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function createLesson(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO academy_lessons (
                course_id, module_id, title, slug, source_type, source, content, position, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['course_id'],
            $data['module_id'] ?? null,
            $data['title'],
            $data['slug'],
            $data['source_type'] ?? 'theme_file',
            $data['source'] ?? null,
            $data['content'] ?? null,
            $data['position'] ?? 0,
            $data['status'] ?? 'published',
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function updateLesson(int $id, array $data): bool
    {
        $allowed = ['title', 'slug', 'module_id', 'source_type', 'source', 'content', 'position', 'status'];
        $fields = [];
        $values = [];

        foreach ($allowed as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "{$col} = ?";
                $values[] = $data[$col];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $values[] = $id;
        $sql = "UPDATE academy_lessons SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($values);
    }

    public function deleteLesson(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM academy_lessons WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function replaceCourseStructure(int $courseId, array $structure): bool
    {
        $this->pdo->beginTransaction();
        try {
            // Remove aulas e módulos antigos do curso
            $delLessons = $this->pdo->prepare("DELETE FROM academy_lessons WHERE course_id = ?");
            $delLessons->execute([$courseId]);

            $delModules = $this->pdo->prepare("DELETE FROM academy_modules WHERE course_id = ?");
            $delModules->execute([$courseId]);

            $modulePos = 1;
            $lessonPosGlobal = 1;

            foreach ($structure as $modData) {
                $modTitle = trim($modData['title'] ?? 'Módulo Geral');
                $moduleId = $this->createModule($courseId, $modTitle, $modulePos++);

                $lessons = $modData['lessons'] ?? [];
                if (is_array($lessons)) {
                    foreach ($lessons as $lesson) {
                        $this->createLesson([
                            'course_id'   => $courseId,
                            'module_id'   => $moduleId,
                            'title'       => trim($lesson['title'] ?? 'Aula'),
                            'slug'        => trim($lesson['slug'] ?? ('aula-' . $lessonPosGlobal)),
                            'source_type' => $lesson['source_type'] ?? 'theme_file',
                            'source'      => $lesson['source'] ?? $lesson['theme_file'] ?? null,
                            'content'     => $lesson['content'] ?? null,
                            'position'    => $lessonPosGlobal++,
                            'status'      => $lesson['status'] ?? 'published',
                        ]);
                    }
                }
            }

            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function ensureTables(): void
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS academy_modules (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    course_id INTEGER NOT NULL,
                    title TEXT NOT NULL,
                    position INTEGER NOT NULL DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS academy_lessons (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    course_id INTEGER NOT NULL,
                    module_id INTEGER NULL,
                    title TEXT NOT NULL,
                    slug TEXT NOT NULL,
                    source_type TEXT NOT NULL DEFAULT 'theme_file',
                    source TEXT NULL,
                    content TEXT NULL,
                    position INTEGER NOT NULL DEFAULT 0,
                    status TEXT NOT NULL DEFAULT 'published',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );
            ");
        } else {
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS academy_modules (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    course_id INT NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    position INT NOT NULL DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_acad_mod_course (course_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS academy_lessons (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    course_id INT NOT NULL,
                    module_id INT NULL,
                    title VARCHAR(255) NOT NULL,
                    slug VARCHAR(255) NOT NULL,
                    source_type VARCHAR(50) NOT NULL DEFAULT 'theme_file',
                    source VARCHAR(255) NULL,
                    content LONGTEXT NULL,
                    position INT NOT NULL DEFAULT 0,
                    status VARCHAR(50) NOT NULL DEFAULT 'published',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_acad_less_course (course_id),
                    INDEX idx_acad_less_slug (course_id, slug)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        }
    }
}
