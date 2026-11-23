<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Repositories;

use PDO;
use Throwable;

/**
 * Garante de forma idempotente e segura que as tabelas do DomainDrop
 * (incluindo clientes, endereços, reclamações e colunas de SLA) existam mesmo antes
 * de uma execução manual do CLI bin/migrate.php.
 */
final class DomainDropSchemaEnsurer
{
    /** @var \WeakMap<PDO, bool>|null */
    private static ?\WeakMap $ensuredConnections = null;

    public static function ensure(PDO $pdo): void
    {
        if (self::$ensuredConnections === null) {
            self::$ensuredConnections = new \WeakMap();
        }
        if (isset(self::$ensuredConnections[$pdo])) {
            return;
        }

        if (self::schemaAlreadyPresent($pdo)) {
            self::$ensuredConnections[$pdo] = true;
            return;
        }

        try {
            $migrationsDir = dirname(__DIR__) . '/migrations';
            foreach ([
                '2026_11_10_000001_create_domain_drop_tables.php',
                '2026_11_12_000001_create_customer_disputes_and_mediation.php',
                '2026_11_16_000001_create_domain_drop_demand_table.php',
                '2026_11_16_000002_create_domain_drop_demand_activity_logs_table.php',
                '2026_11_18_000001_add_shipping_address_to_customers.php',
            ] as $file) {
                $path = $migrationsDir . '/' . $file;
                if (is_file($path)) {
                    $migration = require $path;
                    if (is_array($migration) && isset($migration['up']) && is_callable($migration['up'])) {
                        ($migration['up'])($pdo);
                    }
                }
            }
            if (self::schemaAlreadyPresent($pdo)) {
                self::$ensuredConnections[$pdo] = true;
            }
        } catch (Throwable $e) {
            error_log('[DomainDropSchemaEnsurer] Falha ao garantir schema: ' . $e->getMessage());
        }
    }

    public static function resetCache(): void
    {
        self::$ensuredConnections = null;
    }

    private static function schemaAlreadyPresent(PDO $pdo): bool
    {
        try {
            $driver = (string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'mysql') {
                $c0 = $pdo->query("SHOW TABLES LIKE 'domain_drop_catalog'");
                $c1 = $pdo->query("SHOW TABLES LIKE 'domain_drop_customers'");
                $c2 = $pdo->query("SHOW TABLES LIKE 'domain_drop_disputes'");
                $c3 = $pdo->query("SHOW TABLES LIKE 'domain_drop_product_demand'");
                $c4 = $pdo->query("SHOW TABLES LIKE 'domain_drop_demand_activity_logs'");
                $cols = $pdo->query("SHOW COLUMNS FROM domain_drop_orders LIKE 'seller_origin'");
                $cAddr = $pdo->query("SHOW COLUMNS FROM domain_drop_customers LIKE 'shipping_zip'");
                return $c0 !== false && $c0->fetchColumn() !== false
                    && $c1 !== false && $c1->fetchColumn() !== false
                    && $c2 !== false && $c2->fetchColumn() !== false
                    && $c3 !== false && $c3->fetchColumn() !== false
                    && $c4 !== false && $c4->fetchColumn() !== false
                    && $cols !== false && $cols->fetchColumn() !== false
                    && $cAddr !== false && $cAddr->fetchColumn() !== false;
            }

            $c0 = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='domain_drop_catalog'");
            $c1 = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='domain_drop_customers'");
            $c2 = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='domain_drop_disputes'");
            $c3 = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='domain_drop_product_demand'");
            $c4 = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='domain_drop_demand_activity_logs'");
            $cols = $pdo->query("PRAGMA table_info(domain_drop_orders)");
            $colNames = $cols ? array_column($cols->fetchAll(PDO::FETCH_ASSOC), 'name') : [];

            $custCols = $pdo->query("PRAGMA table_info(domain_drop_customers)");
            $custColNames = $custCols ? array_column($custCols->fetchAll(PDO::FETCH_ASSOC), 'name') : [];

            return $c0 !== false && $c0->fetchColumn() !== false
                && $c1 !== false && $c1->fetchColumn() !== false
                && $c2 !== false && $c2->fetchColumn() !== false
                && $c3 !== false && $c3->fetchColumn() !== false
                && $c4 !== false && $c4->fetchColumn() !== false
                && in_array('seller_origin', $colNames, true)
                && in_array('shipping_zip', $custColNames, true);
        } catch (Throwable $e) {
            return false;
        }
    }
}
