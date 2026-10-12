<?php

declare(strict_types=1);

namespace DomainSystem\SystemApps\nav_menus\Repositories;

use DomainSystem\SystemApps\nav_menus\Contracts\MenuRepositoryInterface;
use DomainSystem\SystemApps\Database\Connection;
use PDO;
use PDOException;
use Throwable;

class PdoMenuRepository implements MenuRepositoryInterface
{
    private PDO $pdo;

    public function __construct(Connection|PDO $connection)
    {
        $this->pdo = $connection instanceof Connection ? $connection->getPdo() : $connection;
    }

    public function ensureTables(): void
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $autoIncrement = ($driver === 'sqlite') ? 'AUTOINCREMENT' : 'AUTO_INCREMENT';

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS theme_menus (
                id INTEGER PRIMARY KEY $autoIncrement,
                name VARCHAR(255) NOT NULL,
                location VARCHAR(100) NOT NULL UNIQUE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS theme_menu_items (
                id INTEGER PRIMARY KEY $autoIncrement,
                menu_id INTEGER NOT NULL,
                title VARCHAR(255) NOT NULL,
                url TEXT NULL,
                page_id INTEGER NULL,
                order_index INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Auto-seed: locais padrão ('header' e 'footer')
        $stmt = $this->pdo->query("SELECT location FROM theme_menus");
        $existing = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];

        if (!in_array('header', $existing, true)) {
            $insert = $this->pdo->prepare("INSERT INTO theme_menus (name, location) VALUES (?, ?)");
            $insert->execute(['Menu Principal', 'header']);
        }

        if (!in_array('footer', $existing, true)) {
            $insert = $this->pdo->prepare("INSERT INTO theme_menus (name, location) VALUES (?, ?)");
            $insert->execute(['Menu do Rodapé', 'footer']);
        }
    }

    public function getMenus(): array
    {
        $this->ensureTables();
        $stmt = $this->pdo->query("SELECT * FROM theme_menus ORDER BY name ASC");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function getMenuById(int $id): ?array
    {
        $this->ensureTables();
        $stmt = $this->pdo->prepare("SELECT * FROM theme_menus WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getMenuByLocation(string $location): ?array
    {
        $this->ensureTables();
        $stmt = $this->pdo->prepare("SELECT * FROM theme_menus WHERE location = ?");
        $stmt->execute([$location]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getMenuItems(int $menuId): array
    {
        $this->ensureTables();
        $stmt = $this->pdo->prepare("SELECT * FROM theme_menu_items WHERE menu_id = ? ORDER BY order_index ASC");
        $stmt->execute([$menuId]);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function saveMenu(string $name, string $location, ?int $id = null): array
    {
        $this->ensureTables();
        $name = trim($name);
        $location = trim($location);

        if (empty($name) || empty($location)) {
            return ['success' => false, 'message' => 'Nome e Localização são obrigatórios.'];
        }

        if ($id !== null && $id > 0) {
            $stmt = $this->pdo->prepare('UPDATE theme_menus SET name = ?, location = ? WHERE id = ?');
            $stmt->execute([$name, $location, $id]);
            return ['success' => true, 'message' => 'Menu atualizado com sucesso.', 'id' => $id];
        }

        $check = $this->pdo->prepare('SELECT id FROM theme_menus WHERE location = ?');
        $check->execute([$location]);
        if ($check->fetch()) {
            return ['success' => false, 'message' => 'Já existe um menu atribuído a esta localização.'];
        }

        $stmt = $this->pdo->prepare('INSERT INTO theme_menus (name, location) VALUES (?, ?)');
        $stmt->execute([$name, $location]);
        $newId = (int)$this->pdo->lastInsertId();
        return ['success' => true, 'message' => 'Menu criado com sucesso.', 'id' => $newId];
    }

    public function replaceMenuItems(int $menuId, array $items): bool
    {
        $this->ensureTables();

        $startedTransaction = false;
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
            $startedTransaction = true;
        }

        try {
            $deleteStmt = $this->pdo->prepare('DELETE FROM theme_menu_items WHERE menu_id = ?');
            $deleteStmt->execute([$menuId]);

            if (!empty($items)) {
                $insertStmt = $this->pdo->prepare('
                    INSERT INTO theme_menu_items (menu_id, title, url, page_id, order_index)
                    VALUES (?, ?, ?, ?, ?)
                ');

                foreach ($items as $index => $item) {
                    $title = trim((string)($item['title'] ?? ''));
                    if (empty($title)) {
                        continue;
                    }
                    $url = !empty($item['url']) ? (string)$item['url'] : null;
                    $pageId = !empty($item['page_id']) ? (int)$item['page_id'] : null;

                    $insertStmt->execute([
                        $menuId,
                        $title,
                        $url,
                        $pageId,
                        (int)$index
                    ]);
                }
            }

            if ($startedTransaction) {
                $this->pdo->commit();
            }
            return true;
        } catch (Throwable $t) {
            if ($startedTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return false;
        }
    }

    public function deleteMenu(int $id): bool
    {
        $this->ensureTables();

        $startedTransaction = false;
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
            $startedTransaction = true;
        }

        try {
            $stmtItems = $this->pdo->prepare('DELETE FROM theme_menu_items WHERE menu_id = ?');
            $stmtItems->execute([$id]);

            $stmtMenu = $this->pdo->prepare('DELETE FROM theme_menus WHERE id = ?');
            $stmtMenu->execute([$id]);

            if ($startedTransaction) {
                $this->pdo->commit();
            }
            return true;
        } catch (Throwable $t) {
            if ($startedTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return false;
        }
    }
}
