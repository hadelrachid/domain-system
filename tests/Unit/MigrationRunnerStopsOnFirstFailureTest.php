<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Core\Database\MigrationRunner;
use DomainSystem\Core\Database\PluginMigrationScanner;
use DomainSystem\SystemApps\Database\Connection;
use PDO;
use PHPUnit\Framework\TestCase;

final class MigrationRunnerStopsOnFirstFailureTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/ds_mig_stop_test_' . uniqid('', true);
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testStopsOnFirstFailureAndDoesNotRunOrRecordSubsequentMigrations(): void
    {
        $conn = new Connection('sqlite::memory:');
        $runner = new MigrationRunner($conn);

        file_put_contents(
            $this->tempDir . '/2026_10_19_000001_first_ok.php',
            <<<'PHP'
<?php
return [
    'up' => function (\PDO $pdo): void {
        $pdo->exec('CREATE TABLE mig_test_one (id INTEGER PRIMARY KEY AUTOINCREMENT, label TEXT)');
    },
];
PHP
        );

        file_put_contents(
            $this->tempDir . '/2026_10_19_000002_second_fails.php',
            <<<'PHP'
<?php
return [
    'up' => function (\PDO $pdo): void {
        throw new \RuntimeException('Erro simulado na segunda migration');
    },
];
PHP
        );

        file_put_contents(
            $this->tempDir . '/2026_10_19_000003_third_must_not_run.php',
            <<<'PHP'
<?php
return [
    'up' => function (\PDO $pdo): void {
        $pdo->exec('CREATE TABLE mig_test_three (id INTEGER PRIMARY KEY AUTOINCREMENT)');
    },
];
PHP
        );

        $result = $runner->runForPlugin('test_plugin', $this->tempDir);

        $this->assertSame(['2026_10_19_000001_first_ok'], $result['executed']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('2026_10_19_000002_second_fails', $result['errors'][0]);
        $this->assertSame('2026_10_19_000002_second_fails', $result['stopped_at']);

        // Verifica que apenas a primeira migration foi registrada em kernel_migrations
        $executedRows = $runner->getExecutedMigrations('test_plugin');
        $this->assertCount(1, $executedRows);
        $this->assertSame('2026_10_19_000001_first_ok', $executedRows[0]['migration']);

        // Verifica que a terceira tabela NÃO foi criada
        $pdo = $conn->getPdo();
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='mig_test_three'");
        $this->assertFalse($stmt !== false ? $stmt->fetch(PDO::FETCH_ASSOC) : false);
    }

    public function testStopsOnInvalidMigrationFormat(): void
    {
        $conn = new Connection('sqlite::memory:');
        $runner = new MigrationRunner($conn);

        file_put_contents(
            $this->tempDir . '/2026_10_19_000001_invalid_format.php',
            "<?php\nreturn ['invalid' => true];\n"
        );

        file_put_contents(
            $this->tempDir . '/2026_10_19_000002_after_invalid.php',
            <<<'PHP'
<?php
return [
    'up' => function (\PDO $pdo): void {
        $pdo->exec('CREATE TABLE after_invalid (id INTEGER PRIMARY KEY)');
    },
];
PHP
        );

        $result = $runner->runForPlugin('test_plugin', $this->tempDir);

        $this->assertSame([], $result['executed']);
        $this->assertCount(1, $result['errors']);
        $this->assertSame('2026_10_19_000001_invalid_format', $result['stopped_at']);
        $this->assertCount(0, $runner->getExecutedMigrations('test_plugin'));
    }

    public function testAllSucceededReturnsStoppedAtNullAndPropagatesInScanner(): void
    {
        $conn = new Connection('sqlite::memory:');
        $runner = new MigrationRunner($conn);

        $pluginMigrationsDir = $this->tempDir . '/src/Plugins/alpha/migrations';
        mkdir($pluginMigrationsDir, 0777, true);

        file_put_contents(
            $pluginMigrationsDir . '/2026_10_19_000001_alpha_ok.php',
            <<<'PHP'
<?php
return [
    'up' => function (\PDO $pdo): void {
        $pdo->exec('CREATE TABLE alpha_tbl (id INTEGER PRIMARY KEY)');
    },
];
PHP
        );

        $scanner = new PluginMigrationScanner($this->tempDir, $conn);
        $healResult = $scanner->healAll($runner);

        $this->assertSame(['alpha' => ['2026_10_19_000001_alpha_ok']], $healResult['executed']);
        $this->assertSame([], $healResult['errors']);
        $this->assertSame([], $healResult['stopped_at']);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
