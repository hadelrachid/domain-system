<?php

declare(strict_types=1);

namespace DomainSystem\Core\Database\Update;

use DomainSystem\Core\Contracts\DatabaseConnectionInterface;
use DomainSystem\Core\Contracts\Update\MigrationRegistryInterface;
use PDO;
use Throwable;

/**
 * Implementação somente leitura de `MigrationRegistryInterface` via PDO.
 *
 * Não cria a tabela `kernel_migrations` nem produz efeitos colaterais no banco.
 * Se a tabela ainda não existir, devolve `[]`.
 */
final class PdoMigrationRegistry implements MigrationRegistryInterface
{
    private PDO $db;
    private string $table;

    public function __construct(
        DatabaseConnectionInterface|PDO $connection,
        string $table = 'kernel_migrations',
    ) {
        $this->db = $connection instanceof DatabaseConnectionInterface ? $connection->getPdo() : $connection;
        $this->table = $table;
    }

    public function executed(string $scope): array
    {
        if (trim($scope) === '') {
            return [];
        }

        try {
            $stmt = @$this->db->prepare(
                "SELECT migration FROM {$this->table} WHERE plugin = ? ORDER BY id ASC"
            );
            if ($stmt === false) {
                return [];
            }

            $ok = @$stmt->execute([$scope]);
            if (!$ok) {
                return [];
            }

            $rows = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
            if (!is_array($rows)) {
                return [];
            }

            $names = [];
            foreach ($rows as $val) {
                if (is_string($val) && trim($val) !== '') {
                    $names[] = $val;
                }
            }

            return $names;
        } catch (Throwable) {
            return [];
        }
    }
}
