<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Repositories;

use PDO;
use Throwable;

/**
 * Garante de forma idempotente e segura que as tabelas do DomainDrop
 * (incluindo clientes, reclamações e colunas de SLA) existam mesmo antes
 * de uma execução manual do CLI bin/migrate.php.
 */
final class DomainDropSchemaEnsurer
{
    /** @var array<int, bool> */
    private static array $ensuredConnections = [];

    public static function ensure(PDO $pdo): void
    {
        $connId = spl_object_id($pdo);
        if (isset(self::$ensuredConnections[$connId])) {
            return;
        }
        self::$ensuredConnections[$connId] = true;

        if (self::schemaAlreadyPresent($pdo)) {
            return;
        }

        try {
            $migrationsDir = dirname(__DIR__) . '/migrations';
            foreach ([
                '2026_11_10_000001_create_domain_drop_tables.php',
                '2026_11_12_000001_create_customer_disputes_and_mediation.php',
            ] as $file) {
                $path = $migrationsDir . '/' . $file;
                if (is_file($path)) {
                    $migration = require $path;
                    if (is_array($migration) && isset($migration['up']) && is_callable($migration['up'])) {
                        ($migration['up'])($pdo);
                    }
                }
            }
        } catch (Throwable $e) {
            // Não mascara erro real se o banco estiver indisponível; o caller tratará.
        }
    }

    private static function schemaAlreadyPresent(PDO $pdo): bool
    {
        try {
            $driver = (string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'mysql') {
                $c1 = $pdo->query("SHOW TABLES LIKE 'domain_drop_customers'");
                $c2 = $pdo->query("SHOW TABLES LIKE 'domain_drop_disputes'");
                $cols = $pdo->query("SHOW COLUMNS FROM domain_drop_orders LIKE 'seller_origin'");
                return $c1 !== false && $c1->fetchColumn() !== false
                    && $c2 !== false && $c2->fetchColumn() !== false
                    && $cols !== false && $cols->fetchColumn() !== false;
            }

            $c1 = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='domain_drop_customers'");
            $c2 = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='domain_drop_disputes'");
            $cols = $pdo->query("PRAGMA table_info(domain_drop_orders)");
            $colNames = $cols ? array_column($cols->fetchAll(PDO::FETCH_ASSOC), 'name') : [];

            return $c1 !== false && $c1->fetchColumn() !== false
                && $c2 !== false && $c2->fetchColumn() !== false
                && in_array('seller_origin', $colNames, true);
        } catch (Throwable $e) {
            return false;
        }
    }
}
