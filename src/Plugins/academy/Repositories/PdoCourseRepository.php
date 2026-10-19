<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Repositories;

use DomainSystem\Core\Contracts\DatabaseConnectionInterface;
use DomainSystem\Plugins\academy\Contracts\CourseRepositoryInterface;
use PDO;

class PdoCourseRepository implements CourseRepositoryInterface
{
    private PDO $pdo;

    public function __construct(DatabaseConnectionInterface|PDO $connection, bool $autoEnsureSchema = true)
    {
        $this->pdo = $connection instanceof DatabaseConnectionInterface
            ? $connection->getPdo()
            : $connection;

        if ($autoEnsureSchema) {
            $this->ensureTableExists();
        }
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

    public function ensureTableExists(): void
    {
        $migrationFile = dirname(__DIR__) . '/migrations/2026_10_15_000001_create_academy_courses.php';
        if (is_file($migrationFile)) {
            $migration = require $migrationFile;
            if (is_array($migration) && isset($migration['up']) && is_callable($migration['up'])) {
                ($migration['up'])($this->pdo);
            }
        }
    }
}
