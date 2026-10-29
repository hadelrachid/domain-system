<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\pages\Repositories;

use DomainSystem\SystemApps\Database\Connection;
use DomainSystem\Plugins\pages\Contracts\PageRepositoryInterface;
use DomainSystem\Core\Contracts\PageLookupInterface;
use InvalidArgumentException;
use PDOException;
use PDO;

class PageRepository implements PageRepositoryInterface, PageLookupInterface
{
    private const ALLOWED_COLUMNS = [
        'id',
        'slug',
        'title',
        'content',
        'theme',
        'template_file',
        'created_at',
    ];

    private PDO $db;

    public function __construct(Connection|PDO $connection)
    {
        $this->db = $connection instanceof Connection ? $connection->getPdo() : $connection;
    }

    private function validateColumns(array $data): void
    {
        if (empty($data)) {
            throw new InvalidArgumentException('Nenhum dado fornecido para persistência em pages.');
        }

        foreach (array_keys($data) as $col) {
            if (!in_array((string)$col, self::ALLOWED_COLUMNS, true)) {
                throw new InvalidArgumentException("Coluna inválida não permitida em pages: {$col}");
            }
        }
    }

    public function getSelectablePages(): array
    {
        try {
            $stmt = $this->db->query("SELECT id, title, slug FROM pages ORDER BY title ASC");
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getAll(): array
    {
        try {
            $stmt = $this->db->query("SELECT * FROM pages ORDER BY created_at DESC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function findById(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM pages WHERE id = :id LIMIT 1");
            $stmt->execute([":id" => $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function findBySlug(string $slug): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM pages WHERE slug = :slug LIMIT 1");
            $stmt->execute([":slug" => $slug]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function create(array $data): void
    {
        $this->validateColumns($data);
        $fields = implode(", ", array_keys($data));
        $placeholders = ":" . implode(", :", array_keys($data));
        $stmt = $this->db->prepare("INSERT INTO pages ($fields) VALUES ($placeholders)");
        $params = [];
        foreach ($data as $k => $v) {
            $params[":$k"] = $v;
        }
        $stmt->execute($params);
    }

    public function update(int $id, array $data): void
    {
        $this->validateColumns($data);
        $sets = [];
        $params = [":id" => $id];
        foreach ($data as $k => $v) {
            $sets[] = "$k = :$k";
            $params[":$k"] = $v;
        }
        $setString = implode(", ", $sets);
        $stmt = $this->db->prepare("UPDATE pages SET $setString WHERE id = :id");
        $stmt->execute($params);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare("DELETE FROM pages WHERE id = :id");
        $stmt->execute([":id" => $id]);
    }
}
