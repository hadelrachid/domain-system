<?php

declare(strict_types=1);

namespace DomainSystem\Core\Database;

use DomainSystem\Core\Contracts\DatabaseConnectionInterface;
use PDO;
use Throwable;

/**
 * Runner de migrações versionadas do Kernel.
 *
 * Cada plugin pode ter uma pasta `migrations/` com arquivos no formato:
 *   YYYY_MM_DD_HHMMSS_descricao.php
 *
 * Cada arquivo deve retornar um array associativo com:
 *   ['up' => function(PDO $pdo) {...}, 'down' => function(PDO $pdo) {...}]
 *
 * Comportamento de falha (Auto-Heal e Atualizador):
 *   A execução por escopo/plugin para imediatamente na primeira falha (`stopped_at`),
 *   sem registrar a migration defeituosa em `kernel_migrations` e sem executar as
 *   migrations seguintes daquele escopo.
 */
class MigrationRunner
{
    private PDO $db;
    private string $migrationsTable = 'kernel_migrations';

    public function __construct(DatabaseConnectionInterface|PDO $connection)
    {
        $this->db = $connection instanceof DatabaseConnectionInterface ? $connection->getPdo() : $connection;
        $this->ensureMigrationsTable();
    }

    private function ensureMigrationsTable(): void
    {
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $autoInc = $driver === 'sqlite' ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT AUTO_INCREMENT PRIMARY KEY';

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS {$this->migrationsTable} (
                id {$autoInc},
                plugin VARCHAR(100) NOT NULL,
                migration VARCHAR(255) NOT NULL,
                executed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(plugin, migration)
            )
        ");
    }

    /**
     * Executa as migrations pendentes de um plugin em ordem lexicográfica,
     * interrompendo imediatamente na primeira falha.
     *
     * @return array{executed: array<int, string>, errors: array<int, string>, stopped_at: ?string}
     */
    public function runForPlugin(string $pluginName, string $migrationsPath): array
    {
        if (!is_dir($migrationsPath)) {
            return ['executed' => [], 'errors' => [], 'stopped_at' => null];
        }

        $files = glob($migrationsPath . '/*.php') ?: [];
        sort($files);

        $executed = [];
        $errors = [];
        $stoppedAt = null;

        foreach ($files as $file) {
            $migrationName = basename($file, '.php');

            if ($this->alreadyExecuted($pluginName, $migrationName)) {
                continue;
            }

            try {
                $migration = require $file;
                if (!is_array($migration) || !isset($migration['up']) || !is_callable($migration['up'])) {
                    $errors[] = "Formato inválido: {$migrationName}";
                    $stoppedAt = $migrationName;
                    break;
                }

                $migration['up']($this->db);
                $this->recordExecution($pluginName, $migrationName);
                $executed[] = $migrationName;
            } catch (Throwable $e) {
                $errors[] = "{$migrationName}: {$e->getMessage()}";
                $stoppedAt = $migrationName;
                break;
            }
        }

        return [
            'executed'   => $executed,
            'errors'     => $errors,
            'stopped_at' => $stoppedAt,
        ];
    }

    private function alreadyExecuted(string $plugin, string $migration): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM {$this->migrationsTable} WHERE plugin = ? AND migration = ?"
        );
        $stmt->execute([$plugin, $migration]);
        return (bool)$stmt->fetch();
    }

    private function recordExecution(string $plugin, string $migration): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->migrationsTable} (plugin, migration) VALUES (?, ?)"
        );
        $stmt->execute([$plugin, $migration]);
    }

    public function getExecutedMigrations(?string $plugin = null): array
    {
        if ($plugin !== null) {
            $stmt = $this->db->prepare("SELECT * FROM {$this->migrationsTable} WHERE plugin = ? ORDER BY executed_at DESC");
            $stmt->execute([$plugin]);
        } else {
            $stmt = $this->db->query("SELECT * FROM {$this->migrationsTable} ORDER BY executed_at DESC");
        }
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }
}
