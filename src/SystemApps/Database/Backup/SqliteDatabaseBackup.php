<?php

declare(strict_types=1);

namespace DomainSystem\SystemApps\Database\Backup;

use DomainSystem\Core\Contracts\DatabaseConnectionInterface;
use DomainSystem\Core\Contracts\Update\DatabaseBackupInterface;
use DomainSystem\Core\Update\Exception\RollbackFailedException;
use DomainSystem\Core\Update\Value\DumpResult;
use PDO;
use RuntimeException;
use Throwable;

/**
 * Backup e restauração de banco SQLite via `PRAGMA wal_checkpoint(TRUNCATE)` e cópia de arquivo.
 */
final class SqliteDatabaseBackup implements DatabaseBackupInterface
{
    private PDO $pdo;
    private ?string $sqliteFilePath;

    public function __construct(
        DatabaseConnectionInterface|PDO $connection,
        ?string $sqliteFilePath = null,
    ) {
        $this->pdo = $connection instanceof DatabaseConnectionInterface ? $connection->getPdo() : $connection;
        $this->sqliteFilePath = $sqliteFilePath;
    }

    public function isSupported(): bool
    {
        $file = $this->resolveDatabaseFile();
        return $file !== null && is_file($file) && is_readable($file);
    }

    public function dump(string $destinationFile): DumpResult
    {
        $sourceFile = $this->resolveDatabaseFile();
        if ($sourceFile === null || !is_file($sourceFile)) {
            throw new RuntimeException('Arquivo de banco de dados SQLite não encontrado para dump.');
        }

        $destDir = dirname($destinationFile);
        if (!is_dir($destDir) && !@mkdir($destDir, 0755, true) && !is_dir($destDir)) {
            throw new RuntimeException(sprintf('Não foi possível criar diretório para dump SQLite: "%s".', $destDir));
        }

        try {
            @$this->pdo->exec('PRAGMA wal_checkpoint(TRUNCATE)');
        } catch (Throwable) {
            // Segue mesmo que o modo WAL não esteja ativo
        }

        if (!@copy($sourceFile, $destinationFile)) {
            throw new RuntimeException(sprintf('Falha ao copiar banco SQLite de "%s" para "%s".', $sourceFile, $destinationFile));
        }

        $tablesCount = 0;
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'");
            if ($stmt !== false) {
                $tablesCount = (int)$stmt->fetchColumn();
            }
        } catch (Throwable) {
            $tablesCount = 0;
        }

        $sizeBytes = (int)(filesize($destinationFile) ?: 0);

        return new DumpResult(
            dumpPath: $destinationFile,
            sizeBytes: $sizeBytes,
            tablesCount: $tablesCount,
        );
    }

    public function restore(string $dumpFile): void
    {
        if (!is_file($dumpFile) || !is_readable($dumpFile)) {
            throw new RollbackFailedException(sprintf('Arquivo de dump SQLite não encontrado: "%s".', $dumpFile));
        }

        $targetFile = $this->resolveDatabaseFile();
        if ($targetFile === null || $targetFile === '') {
            throw new RollbackFailedException('Caminho do banco SQLite de destino desconhecido para restauração.');
        }

        try {
            @$this->pdo->exec('PRAGMA wal_checkpoint(TRUNCATE)');
        } catch (Throwable) {
        }

        // Remove arquivos auxiliares WAL/SHM caso existam antes de restaurar a cópia íntegra
        foreach ([$targetFile . '-wal', $targetFile . '-shm'] as $walFile) {
            if (is_file($walFile)) {
                @unlink($walFile);
            }
        }

        if (!@copy($dumpFile, $targetFile)) {
            throw new RollbackFailedException(sprintf(
                'Falha ao restaurar cópia do banco SQLite de "%s" para "%s".',
                $dumpFile,
                $targetFile
            ));
        }
    }

    private function resolveDatabaseFile(): ?string
    {
        if ($this->sqliteFilePath !== null && trim($this->sqliteFilePath) !== '') {
            return $this->sqliteFilePath;
        }

        try {
            $stmt = $this->pdo->query('PRAGMA database_list');
            if ($stmt !== false) {
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rows as $row) {
                    if (($row['name'] ?? '') === 'main' && !empty($row['file'])) {
                        return (string)$row['file'];
                    }
                }
            }
        } catch (Throwable) {
        }

        return null;
    }
}
