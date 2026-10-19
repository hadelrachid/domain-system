<?php

declare(strict_types=1);

namespace DomainSystem\Core\Database\Update;

use DomainSystem\Core\Contracts\DatabaseConnectionInterface;
use DomainSystem\Core\Contracts\Update\SchemaProbeInterface;
use PDO;
use Throwable;

/**
 * Sondagem agnóstica de tabelas e colunas via SQL ANSI (`SELECT 1 FROM t LIMIT 1`).
 *
 * Trata tanto retorno `false` quanto `PDOException` (SQLite e MySQL).
 */
final class PdoSchemaProbe implements SchemaProbeInterface
{
    private PDO $db;

    public function __construct(DatabaseConnectionInterface|PDO $connection)
    {
        $this->db = $connection instanceof DatabaseConnectionInterface ? $connection->getPdo() : $connection;
    }

    public function hasTable(string $table): bool
    {
        if (!$this->isValidIdentifier($table)) {
            return false;
        }

        try {
            $stmt = @$this->db->query("SELECT 1 FROM {$table} LIMIT 1");
            return $stmt !== false;
        } catch (Throwable) {
            return false;
        }
    }

    public function hasColumn(string $table, string $column): bool
    {
        if (!$this->isValidIdentifier($table) || !$this->isValidIdentifier($column)) {
            return false;
        }

        if (!$this->hasTable($table)) {
            return false;
        }

        try {
            $stmt = @$this->db->query("SELECT {$column} FROM {$table} LIMIT 1");
            return $stmt !== false;
        } catch (Throwable) {
            return false;
        }
    }

    private function isValidIdentifier(string $identifier): bool
    {
        return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $identifier) === 1;
    }
}
