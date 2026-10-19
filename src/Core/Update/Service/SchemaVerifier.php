<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Service;

use DomainSystem\Core\Contracts\Update\MigrationRegistryInterface;
use DomainSystem\Core\Contracts\Update\SchemaProbeInterface;
use DomainSystem\Core\Contracts\Update\SchemaVerifierInterface;
use DomainSystem\Core\Update\Value\SchemaVerification;
use DomainSystem\Core\Update\Value\UpdateManifest;

/**
 * Verificador pós-migração da estrutura efetiva do banco de dados (`expects` + `migrations`).
 */
final class SchemaVerifier implements SchemaVerifierInterface
{
    public function __construct(
        private readonly SchemaProbeInterface $probe,
        private readonly MigrationRegistryInterface $registry,
    ) {
    }

    public function verify(UpdateManifest $manifest): SchemaVerification
    {
        $missingTables = [];
        $missingColumns = [];
        $unrecorded = [];

        foreach ($manifest->expects as $table => $columns) {
            if (!$this->probe->hasTable($table)) {
                $missingTables[] = $table;
                foreach ($columns as $col) {
                    $missingColumns[] = $table . '.' . $col;
                }
                continue;
            }

            foreach ($columns as $col) {
                if (!$this->probe->hasColumn($table, $col)) {
                    $missingColumns[] = $table . '.' . $col;
                }
            }
        }

        /** @var array<string, list<string>> $executedByScope */
        $executedByScope = [];
        foreach ($manifest->migrations as $mig) {
            $scope = $mig['scope'];
            $name = $mig['name'];

            if (!isset($executedByScope[$scope])) {
                $executedByScope[$scope] = $this->registry->executed($scope);
            }

            if (!in_array($name, $executedByScope[$scope], true)) {
                $unrecorded[] = $name;
            }
        }

        $ok = ($missingTables === [] && $missingColumns === [] && $unrecorded === []);

        return new SchemaVerification(
            ok: $ok,
            missingTables: $missingTables,
            missingColumns: $missingColumns,
            unrecorded: $unrecorded,
        );
    }
}
