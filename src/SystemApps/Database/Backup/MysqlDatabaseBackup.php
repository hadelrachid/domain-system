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
 * Backup e restauração de banco MySQL em streaming (lotes de 500 linhas).
 *
 * Limite seguro padrão: 200 MB estimados (`isSupported() === false` acima disso).
 */
final class MysqlDatabaseBackup implements DatabaseBackupInterface
{
    public const DEFAULT_MAX_BYTES = 209715200; // 200 MB
    private const BATCH_SIZE = 500;

    private PDO $pdo;
    private int $maxSupportedBytes;

    public function __construct(
        DatabaseConnectionInterface|PDO $connection,
        int $maxSupportedBytes = self::DEFAULT_MAX_BYTES,
    ) {
        $this->pdo = $connection instanceof DatabaseConnectionInterface ? $connection->getPdo() : $connection;
        $this->maxSupportedBytes = $maxSupportedBytes;
    }

    public function isSupported(): bool
    {
        try {
            $stmt = $this->pdo->query(
                'SELECT COALESCE(SUM(data_length + index_length), 0) FROM information_schema.TABLES WHERE table_schema = DATABASE()'
            );
            if ($stmt === false) {
                return false;
            }
            $estimatedBytes = (int)$stmt->fetchColumn();
            return $estimatedBytes <= $this->maxSupportedBytes;
        } catch (Throwable) {
            return false;
        }
    }

    public function dump(string $destinationFile): DumpResult
    {
        $destDir = dirname($destinationFile);
        if (!is_dir($destDir) && !@mkdir($destDir, 0755, true) && !is_dir($destDir)) {
            throw new RuntimeException(sprintf('Não foi possível criar diretório para dump MySQL: "%s".', $destDir));
        }

        $fh = @fopen($destinationFile, 'wb');
        if (!is_resource($fh)) {
            throw new RuntimeException(sprintf('Não foi possível abrir arquivo de dump MySQL para escrita: "%s".', $destinationFile));
        }

        $tablesCount = 0;

        try {
            fwrite($fh, "-- Domain System MySQL Dump\n");
            fwrite($fh, "SET FOREIGN_KEY_CHECKS=0;\n\n");

            $tablesStmt = $this->pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
            $tables = [];
            if ($tablesStmt !== false) {
                while ($row = $tablesStmt->fetch(PDO::FETCH_NUM)) {
                    if (isset($row[0]) && is_string($row[0])) {
                        $tables[] = $row[0];
                    }
                }
            }

            foreach ($tables as $table) {
                $escapedTable = '`' . str_replace('`', '``', $table) . '`';
                $createStmt = $this->pdo->query("SHOW CREATE TABLE {$escapedTable}");
                if ($createStmt === false) {
                    continue;
                }
                $createRow = $createStmt->fetch(PDO::FETCH_ASSOC);
                $createSql = (string)($createRow['Create Table'] ?? array_values($createRow ?: [])[1] ?? '');
                if ($createSql === '') {
                    continue;
                }

                fwrite($fh, "DROP TABLE IF EXISTS {$escapedTable};\n");
                fwrite($fh, $createSql . ";\n\n");

                $offset = 0;
                while (true) {
                    $batchStmt = $this->pdo->query(sprintf(
                        'SELECT * FROM %s LIMIT %d OFFSET %d',
                        $escapedTable,
                        self::BATCH_SIZE,
                        $offset
                    ));
                    if ($batchStmt === false) {
                        break;
                    }

                    $rows = $batchStmt->fetchAll(PDO::FETCH_ASSOC);
                    if ($rows === []) {
                        break;
                    }

                    $columns = array_keys($rows[0]);
                    $colList = implode(', ', array_map(
                        static fn(string $c): string => '`' . str_replace('`', '``', $c) . '`',
                        $columns
                    ));

                    foreach ($rows as $row) {
                        $vals = [];
                        foreach ($row as $val) {
                            if ($val === null) {
                                $vals[] = 'NULL';
                            } elseif (is_int($val) || is_float($val)) {
                                $vals[] = (string)$val;
                            } else {
                                $vals[] = $this->pdo->quote((string)$val);
                            }
                        }
                        fwrite($fh, sprintf(
                            "INSERT INTO %s (%s) VALUES (%s);\n",
                            $escapedTable,
                            $colList,
                            implode(', ', $vals)
                        ));
                    }

                    fwrite($fh, "\n");

                    if (count($rows) < self::BATCH_SIZE) {
                        break;
                    }
                    $offset += self::BATCH_SIZE;
                }

                $tablesCount++;
            }

            fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
        } catch (Throwable $e) {
            fclose($fh);
            @unlink($destinationFile);
            throw new RuntimeException('Erro ao gerar dump MySQL: ' . $e->getMessage(), 0, $e);
        }

        fclose($fh);
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
            throw new RollbackFailedException(sprintf('Arquivo de dump MySQL não encontrado: "%s".', $dumpFile));
        }

        $fh = @fopen($dumpFile, 'rb');
        if (!is_resource($fh)) {
            throw new RollbackFailedException(sprintf('Não foi possível abrir o arquivo de dump MySQL: "%s".', $dumpFile));
        }

        try {
            $this->pdo->exec('SET FOREIGN_KEY_CHECKS=0');

            $statement = '';
            while (($line = fgets($fh)) !== false) {
                $trimmed = trim($line);
                if ($trimmed === '' || str_starts_with($trimmed, '--')) {
                    continue;
                }

                $statement .= $line;
                if (str_ends_with(rtrim($line), ';')) {
                    $this->pdo->exec($statement);
                    $statement = '';
                }
            }

            if (trim($statement) !== '') {
                $this->pdo->exec($statement);
            }
        } catch (Throwable $e) {
            throw new RollbackFailedException('Falha ao restaurar dump MySQL: ' . $e->getMessage(), 0, $e);
        } finally {
            fclose($fh);
            try {
                $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1');
            } catch (Throwable) {
            }
        }
    }
}
