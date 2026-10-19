<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Service;

use DomainSystem\Core\Contracts\Update\MigrationRegistryInterface;
use DomainSystem\Core\Contracts\Update\SchemaPlannerInterface;
use DomainSystem\Core\Update\Enum\UpdateKind;
use DomainSystem\Core\Update\Exception\SchemaPlanException;
use DomainSystem\Core\Update\Value\SchemaPlan;
use DomainSystem\Core\Update\Value\UpdateManifest;

/**
 * Planejador estático (dry-run) de migrações declaradas no `UpdateManifest`.
 *
 * Valida existência, hash SHA-256 e estrutura estática de cada arquivo de migration
 * na quarentena sem executar nem dar `require` no arquivo PHP.
 */
final class SchemaPlanner implements SchemaPlannerInterface
{
    public function __construct(
        private readonly MigrationRegistryInterface $registry,
    ) {
    }

    public function plan(UpdateManifest $manifest, string $extractedPath): SchemaPlan
    {
        $baseDir = rtrim(str_replace('\\', '/', $extractedPath), '/');
        $defaultScope = $manifest->kind === UpdateKind::Core ? '__core__' : $manifest->slug;

        /** @var array<string, list<string>> $executedByScope */
        $executedByScope = [
            $defaultScope => $this->registry->executed($defaultScope),
        ];

        /** @var array<string, array<string, bool>> $manifestNamesByScope */
        $manifestNamesByScope = [
            $defaultScope => [],
        ];

        $pending = [];
        $destructive = false;

        foreach ($manifest->migrations as $mig) {
            $scope = $mig['scope'];
            $name = $mig['name'];
            $expectedSha = strtolower($mig['sha256']);
            $type = $mig['type'];

            if (preg_match('/^[a-zA-Z0-9_.-]+$/', $name) !== 1) {
                throw new SchemaPlanException(sprintf('Nome de migration inválido no manifest: "%s".', $name));
            }

            if (!isset($executedByScope[$scope])) {
                $executedByScope[$scope] = $this->registry->executed($scope);
            }
            $manifestNamesByScope[$scope][$name] = true;

            $relPath = $this->resolveRelativeMigrationPath($scope, $name);
            $fullPath = $baseDir . '/' . $relPath;

            if (!is_file($fullPath) || !is_readable($fullPath)) {
                throw new SchemaPlanException(sprintf(
                    'Arquivo de migration declarado no manifest não encontrado na quarentena: "%s".',
                    $relPath
                ));
            }

            $actualSha = hash_file('sha256', $fullPath);
            if ($actualSha === false || !hash_equals($expectedSha, strtolower($actualSha))) {
                throw new SchemaPlanException(sprintf(
                    'Hash SHA-256 da migration "%s" diverge do declarado no manifest.',
                    $name
                ));
            }

            $source = file_get_contents($fullPath);
            if ($source === false || !$this->hasValidMigrationStructure($source)) {
                throw new SchemaPlanException(sprintf(
                    'Estrutura estática inválida na migration "%s": deve retornar array com chave "up".',
                    $name
                ));
            }

            if (!in_array($name, $executedByScope[$scope], true)) {
                $pending[] = [
                    'scope' => $scope,
                    'name' => $name,
                    'sha256' => $expectedSha,
                    'type' => $type,
                    'path' => $relPath,
                ];

                if ($type === 'destructive') {
                    $destructive = true;
                }
            }
        }

        $blocked = [];
        foreach ($executedByScope as $scope => $executedList) {
            $knownInManifest = $manifestNamesByScope[$scope] ?? [];
            foreach ($executedList as $executedName) {
                if (!isset($knownInManifest[$executedName])) {
                    $blocked[] = $executedName;
                }
            }
        }

        return new SchemaPlan(
            scope: $defaultScope,
            pending: $pending,
            blocked: $blocked,
            destructive: $destructive,
            requiresDatabaseBackup: $destructive,
        );
    }

    private function resolveRelativeMigrationPath(string $scope, string $name): string
    {
        if ($scope === '__core__') {
            return 'src/Core/Database/migrations/' . $name . '.php';
        }

        return 'src/Plugins/' . trim($scope, '/\\') . '/migrations/' . $name . '.php';
    }

    private function hasValidMigrationStructure(string $phpSource): bool
    {
        if (!str_contains($phpSource, '<?php')) {
            return false;
        }

        if (preg_match('/\breturn\s*(\[|array\s*\()/s', $phpSource) !== 1) {
            return false;
        }

        if (preg_match('/[\'"]up[\'"]\s*=>/s', $phpSource) !== 1) {
            return false;
        }

        return true;
    }
}
