<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Repositories;

use DomainSystem\Plugins\academy\Contracts\CourseRepositoryInterface;
use PDO;

class PdoCourseRepository implements CourseRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->ensureTableExists();
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM academy_courses ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM academy_courses WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM academy_courses WHERE slug = ?");
        $stmt->execute([$slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findPublished(array $filters = []): array
    {
        $status = $filters['status'] ?? null;
        $limit = isset($filters['limit']) ? (int)$filters['limit'] : 12;
        if ($limit <= 0) {
            $limit = 12;
        }

        if (!empty($status)) {
            $stmt = $this->pdo->prepare("SELECT * FROM academy_courses WHERE status = ? ORDER BY id DESC LIMIT {$limit}");
            $stmt->execute([$status]);
        } else {
            $stmt = $this->pdo->prepare("SELECT * FROM academy_courses WHERE status IN ('published', 'soon') ORDER BY id DESC LIMIT {$limit}");
            $stmt->execute();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO academy_courses (title, slug, thumbnail, description, status, link)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['title'],
            $data['slug'],
            $data['thumbnail'] ?? null,
            $data['description'] ?? null,
            $data['status'] ?? 'draft',
            $data['link'] ?? null,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE academy_courses
            SET title = ?, slug = ?, thumbnail = ?, description = ?, status = ?, link = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['title'],
            $data['slug'],
            $data['thumbnail'] ?? null,
            $data['description'] ?? null,
            $data['status'] ?? 'draft',
            $data['link'] ?? null,
            $id
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM academy_courses WHERE id = ?");
        return $stmt->execute([$id]);
    }

    private function ensureTableExists(): void
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS academy_courses (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    title TEXT NOT NULL,
                    slug TEXT NOT NULL UNIQUE,
                    thumbnail TEXT NULL,
                    description TEXT NULL,
                    status TEXT NOT NULL DEFAULT 'draft',
                    link TEXT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
        } else {
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS academy_courses (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    slug VARCHAR(255) NOT NULL UNIQUE,
                    thumbnail VARCHAR(255) NULL,
                    description TEXT NULL,
                    status VARCHAR(50) NOT NULL DEFAULT 'draft',
                    link VARCHAR(255) NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }
    }
}
