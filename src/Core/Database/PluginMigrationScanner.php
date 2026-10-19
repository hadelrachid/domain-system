<?php

declare(strict_types=1);

namespace DomainSystem\Core\Database;

use DomainSystem\Core\Contracts\DatabaseConnectionInterface;
use DomainSystem\Core\Contracts\SchemaDoctorInterface;

/**
 * Implementação de SchemaDoctorInterface via auto-descoberta de `migrations/*.php` (Pilar 3 v2.4).
 *
 * Comportamento do Auto-Heal:
 *   Ao executar `healAll()`, as migrations pendentes de cada plugin são aplicadas em ordem.
 *   Se uma migration falhar, o processo daquele plugin é interrompido imediatamente na primeira
 *   falha (`stopped_at`), sem executar nem registrar as migrations seguintes.
 */
class PluginMigrationScanner implements SchemaDoctorInterface
{
    private string $pluginsBaseDir;
    private ?DatabaseConnectionInterface $dbConnection;

    public function __construct(?string $rootPath = null, ?DatabaseConnectionInterface $dbConnection = null)
    {
        $root = $rootPath ?? (defined('DOMAIN_SYSTEM_ROOT') ? DOMAIN_SYSTEM_ROOT : dirname(__DIR__, 3));
        $this->pluginsBaseDir = rtrim($root, '/\\') . '/src/Plugins';
        $this->dbConnection = $dbConnection;
    }

    /**
     * Descobre todos os diretórios `migrations/` de plugins que possuem arquivos `.php`.
     *
     * @return array<string, array{path: string, files: array<int, string>}>
     */
    public function discover(): array
    {
        if (!is_dir($this->pluginsBaseDir)) {
            return [];
        }

        $entries = scandir($this->pluginsBaseDir);
        if ($entries === false) {
            return [];
        }

        $discovered = [];
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $pluginDir = $this->pluginsBaseDir . DIRECTORY_SEPARATOR . $entry;
            if (!is_dir($pluginDir)) {
                continue;
            }

            $migrationsDir = $pluginDir . DIRECTORY_SEPARATOR . 'migrations';
            if (!is_dir($migrationsDir)) {
                continue;
            }

            $files = glob($migrationsDir . '/*.php') ?: [];
            if (empty($files)) {
                continue;
            }

            sort($files);
            $migrationNames = array_map(static fn(string $f): string => basename($f, '.php'), $files);

            $discovered[$entry] = [
                'path'  => $migrationsDir,
                'files' => $migrationNames,
            ];
        }

        ksort($discovered);
        return $discovered;
    }

    /**
     * Inspeciona o status das migrações de todos os plugins via contrato.
     *
     * @return array<string, array{
     *     plugin: string,
     *     total: int,
     *     executed: array<int, string>,
     *     pending: array<int, string>,
     *     status: string
     * }>
     */
    public function inspectStatus(?MigrationRunner $runner = null): array
    {
        $effectiveRunner = $runner ?? $this->createDefaultRunner();
        if ($effectiveRunner === null) {
            return [];
        }

        $discovered = $this->discover();
        $executedRows = $effectiveRunner->getExecutedMigrations();

        $executedMap = [];
        foreach ($executedRows as $row) {
            $p = (string)($row['plugin'] ?? '');
            $m = (string)($row['migration'] ?? '');
            if ($p !== '' && $m !== '') {
                $executedMap[$p][$m] = true;
            }
        }

        $report = [];
        foreach ($discovered as $pluginName => $info) {
            $executedList = [];
            $pendingList = [];

            foreach ($info['files'] as $migName) {
                if (!empty($executedMap[$pluginName][$migName])) {
                    $executedList[] = $migName;
                } else {
                    $pendingList[] = $migName;
                }
            }

            $report[$pluginName] = [
                'plugin'   => $pluginName,
                'total'    => count($info['files']),
                'executed' => $executedList,
                'pending'  => $pendingList,
                'status'   => empty($pendingList) ? 'ok' : 'pending',
            ];
        }

        return $report;
    }

    /**
     * Executa todas as migrações pendentes de todos os plugins descobertos (Auto-Heal),
     * interrompendo na primeira falha de cada plugin (`stopped_at`).
     *
     * @return array{executed: array<string, array<int, string>>, errors: array<string, array<int, string>>, stopped_at: array<string, string>}
     */
    public function healAll(?MigrationRunner $runner = null): array
    {
        $effectiveRunner = $runner ?? $this->createDefaultRunner();
        if ($effectiveRunner === null) {
            return ['executed' => [], 'errors' => [], 'stopped_at' => []];
        }

        $discovered = $this->discover();
        $allExecuted = [];
        $allErrors = [];
        $allStoppedAt = [];

        foreach ($discovered as $pluginName => $info) {
            $res = $effectiveRunner->runForPlugin($pluginName, $info['path']);
            if (!empty($res['executed'])) {
                $allExecuted[$pluginName] = $res['executed'];
            }
            if (!empty($res['errors'])) {
                $allErrors[$pluginName] = $res['errors'];
            }
            if (!empty($res['stopped_at'])) {
                $allStoppedAt[$pluginName] = (string)$res['stopped_at'];
            }
        }

        return [
            'executed'   => $allExecuted,
            'errors'     => $allErrors,
            'stopped_at' => $allStoppedAt,
        ];
    }

    private function createDefaultRunner(): ?MigrationRunner
    {
        if ($this->dbConnection === null) {
            return null;
        }

        return new MigrationRunner($this->dbConnection->getPdo());
    }
}
