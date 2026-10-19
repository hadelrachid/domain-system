<?php

declare(strict_types=1);

namespace DomainSystem\Core\Database\Update;

use DomainSystem\Core\Contracts\DatabaseConnectionInterface;
use DomainSystem\Core\Contracts\Update\SchemaMigratorInterface;
use DomainSystem\Core\Database\MigrationRunner;
use DomainSystem\Core\Update\Value\SchemaPlan;
use DomainSystem\Core\Update\Value\SchemaResult;
use PDO;

/**
 * Executor de migrações do atualizador sobre o `MigrationRunner` corrigido (Passo 0).
 *
 * Executa por escopo na ordem do plano e interrompe imediatamente na primeira falha.
 */
final class RunnerSchemaMigrator implements SchemaMigratorInterface
{
    private PDO $db;
    private string $rootPath;

    public function __construct(
        DatabaseConnectionInterface|PDO $connection,
        string $rootPath,
    ) {
        $this->db = $connection instanceof DatabaseConnectionInterface ? $connection->getPdo() : $connection;
        $this->rootPath = rtrim(str_replace('\\', '/', $rootPath), '/');
    }

    public function migrate(SchemaPlan $plan): SchemaResult
    {
        if ($plan->pending === []) {
            return new SchemaResult([]);
        }

        $runner = new MigrationRunner($this->db);

        $orderedScopes = [];
        foreach ($plan->pending as $item) {
            $scope = $item['scope'];
            if (!in_array($scope, $orderedScopes, true)) {
                $orderedScopes[] = $scope;
            }
        }

        $allExecuted = [];
        foreach ($orderedScopes as $scope) {
            $migrationsDir = $this->resolveMigrationsDirectory($scope);
            $result = $runner->runForPlugin($scope, $migrationsDir);

            foreach ($result['executed'] as $executedName) {
                $allExecuted[] = $executedName;
            }

            if ($result['stopped_at'] !== null || $result['errors'] !== []) {
                $failedName = $result['stopped_at'] ?? 'unknown';
                $errorMsg = $result['errors'][0] ?? sprintf('Falha na migration %s.', $failedName);

                return new SchemaResult(
                    executed: $allExecuted,
                    failed: $failedName,
                    error: $errorMsg,
                );
            }
        }

        return new SchemaResult(executed: $allExecuted);
    }

    private function resolveMigrationsDirectory(string $scope): string
    {
        if ($scope === '__core__') {
            return $this->rootPath . '/src/Core/Database/migrations';
        }

        return $this->rootPath . '/src/Plugins/' . trim($scope, '/\\') . '/migrations';
    }
}
