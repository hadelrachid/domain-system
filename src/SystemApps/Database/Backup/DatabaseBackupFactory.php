<?php

declare(strict_types=1);

namespace DomainSystem\SystemApps\Database\Backup;

use DomainSystem\Core\Contracts\DatabaseConnectionInterface;
use DomainSystem\Core\Contracts\Update\DatabaseBackupInterface;
use PDO;

/**
 * Fábrica que seleciona o adaptador `DatabaseBackupInterface` conforme o driver PDO ativo.
 */
final class DatabaseBackupFactory
{
    public static function create(
        DatabaseConnectionInterface|PDO $connection,
        ?string $sqliteFilePath = null,
        int $mysqlMaxBytes = MysqlDatabaseBackup::DEFAULT_MAX_BYTES,
    ): DatabaseBackupInterface {
        $pdo = $connection instanceof DatabaseConnectionInterface ? $connection->getPdo() : $connection;
        $driver = strtolower((string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME));

        if ($driver === 'mysql') {
            return new MysqlDatabaseBackup($pdo, $mysqlMaxBytes);
        }

        return new SqliteDatabaseBackup($pdo, $sqliteFilePath);
    }
}
