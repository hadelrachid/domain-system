<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit\Update;

use DomainSystem\Core\Database\MigrationRunner;
use DomainSystem\Core\Database\Update\PdoMigrationRegistry;
use DomainSystem\Core\Database\Update\PdoSchemaProbe;
use DomainSystem\Core\Database\Update\RunnerSchemaMigrator;
use DomainSystem\Core\Update\Enum\UpdateKind;
use DomainSystem\Core\Update\Exception\SchemaPlanException;
use DomainSystem\Core\Update\Infrastructure\ZipPackageExtractor;
use DomainSystem\Core\Update\Service\SchemaPlanner;
use DomainSystem\Core\Update\Service\SchemaVerifier;
use DomainSystem\Core\Update\Value\UpdateManifest;
use PDO;
use PHPUnit\Framework\TestCase;

final class SchemaUpdateTest extends TestCase
{
    private PDO $pdo;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ds-schema-test-' . bin2hex(random_bytes(6));
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        ZipPackageExtractor::removeDirectory($this->tempDir);
    }

    private function writeMigrationFile(string $scope, string $name, string $phpCode): string
    {
        $relDir = $scope === '__core__'
            ? 'src/Core/Database/migrations'
            : 'src/Plugins/' . $scope . '/migrations';

        $fullDir = $this->tempDir . '/' . $relDir;
        if (!is_dir($fullDir)) {
            mkdir($fullDir, 0755, true);
        }

        $filePath = $fullDir . '/' . $name . '.php';
        file_put_contents($filePath, $phpCode);

        return (string)hash_file('sha256', $filePath);
    }

    private function makeManifest(array $migrations = [], array $expects = []): UpdateManifest
    {
        return new UpdateManifest(
            protocol: 1,
            minUpdaterProtocol: 1,
            kind: UpdateKind::Core,
            slug: 'core',
            version: '2.4.0',
            requires: [
                'php' => '8.1',
                'extensions' => ['pdo'],
                'osVersion' => '2.3',
            ],
            files: [],
            removed: [],
            migrations: $migrations,
            expects: $expects,
        );
    }

    public function testPdoMigrationRegistryReturnsEmptyWithoutCreatingTableWhenMissing(): void
    {
        $registry = new PdoMigrationRegistry($this->pdo);
        $probe = new PdoSchemaProbe($this->pdo);

        $this->assertSame([], $registry->executed('__core__'));
        $this->assertFalse($probe->hasTable('kernel_migrations'));
    }

    public function testSchemaPlannerPlansPendingAndDestructiveRequiresDbBackup(): void
    {
        $m1Code = "<?php\nreturn ['up' => function (\\PDO \$pdo) { \$pdo->exec('CREATE TABLE t1 (id INT)'); }];\n";
        $m2Code = "<?php\nreturn ['up' => function (\\PDO \$pdo) { \$pdo->exec('ALTER TABLE t1 ADD COLUMN c2 TEXT'); }];\n";

        $sha1 = $this->writeMigrationFile('__core__', '2026_11_01_000001_create_t1', $m1Code);
        $sha2 = $this->writeMigrationFile('__core__', '2026_11_01_000002_alter_t1', $m2Code);

        // Registra a primeira como já executada
        $runner = new MigrationRunner($this->pdo);
        $this->pdo->exec("INSERT INTO kernel_migrations (plugin, migration) VALUES ('__core__', '2026_11_01_000001_create_t1')");

        $manifest = $this->makeManifest([
            ['scope' => '__core__', 'name' => '2026_11_01_000001_create_t1', 'sha256' => $sha1, 'type' => 'additive'],
            ['scope' => '__core__', 'name' => '2026_11_01_000002_alter_t1', 'sha256' => $sha2, 'type' => 'destructive'],
        ]);

        $planner = new SchemaPlanner(new PdoMigrationRegistry($this->pdo));
        $plan = $planner->plan($manifest, $this->tempDir);

        $this->assertCount(1, $plan->pending);
        $this->assertSame('2026_11_01_000002_alter_t1', $plan->pending[0]['name']);
        $this->assertSame([], $plan->blocked);
        $this->assertTrue($plan->destructive);
        $this->assertTrue($plan->requiresDatabaseBackup);
    }

    public function testSchemaPlannerThrowsExceptionOnDivergentHash(): void
    {
        $m1Code = "<?php\nreturn ['up' => function (\\PDO \$pdo) {}];\n";
        $this->writeMigrationFile('__core__', '2026_11_01_000001_test', $m1Code);

        $manifest = $this->makeManifest([
            ['scope' => '__core__', 'name' => '2026_11_01_000001_test', 'sha256' => str_repeat('f', 64), 'type' => 'additive'],
        ]);

        $planner = new SchemaPlanner(new PdoMigrationRegistry($this->pdo));

        $this->expectException(SchemaPlanException::class);
        $this->expectExceptionMessage('Hash SHA-256 da migration');
        $planner->plan($manifest, $this->tempDir);
    }

    public function testRunnerSchemaMigratorExecutesAllPendingMigrations(): void
    {
        $m1Code = "<?php\nreturn ['up' => function (\\PDO \$pdo) { \$pdo->exec('CREATE TABLE mod_table (id INTEGER PRIMARY KEY, folder_slug TEXT)'); }];\n";
        $m2Code = "<?php\nreturn ['up' => function (\\PDO \$pdo) { \$pdo->exec('ALTER TABLE mod_table ADD COLUMN extra_col TEXT'); }];\n";

        $sha1 = $this->writeMigrationFile('__core__', '2026_11_01_000001_create_mod', $m1Code);
        $sha2 = $this->writeMigrationFile('__core__', '2026_11_01_000002_add_extra', $m2Code);

        $manifest = $this->makeManifest(
            migrations: [
                ['scope' => '__core__', 'name' => '2026_11_01_000001_create_mod', 'sha256' => $sha1, 'type' => 'additive'],
                ['scope' => '__core__', 'name' => '2026_11_01_000002_add_extra', 'sha256' => $sha2, 'type' => 'additive'],
            ],
            expects: [
                'mod_table' => ['folder_slug', 'extra_col'],
            ],
        );

        $registry = new PdoMigrationRegistry($this->pdo);
        $planner = new SchemaPlanner($registry);
        $plan = $planner->plan($manifest, $this->tempDir);

        $migrator = new RunnerSchemaMigrator($this->pdo, $this->tempDir);
        $result = $migrator->migrate($plan);

        $this->assertTrue($result->isOk());
        $this->assertSame(['2026_11_01_000001_create_mod', '2026_11_01_000002_add_extra'], $result->executed);

        $verifier = new SchemaVerifier(new PdoSchemaProbe($this->pdo), $registry);
        $verification = $verifier->verify($manifest);
        $this->assertTrue($verification->ok);
    }

    public function testRunnerSchemaMigratorStopsAtSecondOfThreeAndDoesNotRecordFailure(): void
    {
        $m1Code = "<?php\nreturn ['up' => function (\\PDO \$pdo) { \$pdo->exec('CREATE TABLE step1 (id INT)'); }];\n";
        $m2Code = "<?php\nreturn ['up' => function (\\PDO \$pdo) { throw new \\RuntimeException('Boom na migration 2'); }];\n";
        $m3Code = "<?php\nreturn ['up' => function (\\PDO \$pdo) { \$pdo->exec('CREATE TABLE step3 (id INT)'); }];\n";

        $sha1 = $this->writeMigrationFile('__core__', '2026_11_01_000001_one', $m1Code);
        $sha2 = $this->writeMigrationFile('__core__', '2026_11_01_000002_two', $m2Code);
        $sha3 = $this->writeMigrationFile('__core__', '2026_11_01_000003_three', $m3Code);

        $manifest = $this->makeManifest([
            ['scope' => '__core__', 'name' => '2026_11_01_000001_one', 'sha256' => $sha1, 'type' => 'additive'],
            ['scope' => '__core__', 'name' => '2026_11_01_000002_two', 'sha256' => $sha2, 'type' => 'additive'],
            ['scope' => '__core__', 'name' => '2026_11_01_000003_three', 'sha256' => $sha3, 'type' => 'additive'],
        ]);

        $registry = new PdoMigrationRegistry($this->pdo);
        $plan = (new SchemaPlanner($registry))->plan($manifest, $this->tempDir);

        $migrator = new RunnerSchemaMigrator($this->pdo, $this->tempDir);
        $result = $migrator->migrate($plan);

        $this->assertFalse($result->isOk());
        $this->assertSame(['2026_11_01_000001_one'], $result->executed);
        $this->assertSame('2026_11_01_000002_two', $result->failed);
        $this->assertStringContainsString('Boom na migration 2', (string)$result->error);

        $this->assertSame(['2026_11_01_000001_one'], $registry->executed('__core__'));
        $probe = new PdoSchemaProbe($this->pdo);
        $this->assertTrue($probe->hasTable('step1'));
        $this->assertFalse($probe->hasTable('step3'));
    }

    public function testSchemaVerifierDetectsMissingColumnEvenWhenMigrationIsRecorded(): void
    {
        $runner = new MigrationRunner($this->pdo);
        $this->pdo->exec('CREATE TABLE academy_modules (id INTEGER PRIMARY KEY, title TEXT)');
        $this->pdo->exec("INSERT INTO kernel_migrations (plugin, migration) VALUES ('__core__', '2026_11_01_000001_fake')");

        $manifest = $this->makeManifest(
            migrations: [
                ['scope' => '__core__', 'name' => '2026_11_01_000001_fake', 'sha256' => str_repeat('a', 64), 'type' => 'additive'],
            ],
            expects: [
                'academy_modules' => ['title', 'folder_slug'],
            ],
        );

        $verifier = new SchemaVerifier(
            new PdoSchemaProbe($this->pdo),
            new PdoMigrationRegistry($this->pdo),
        );

        $verification = $verifier->verify($manifest);
        $this->assertFalse($verification->ok);
        $this->assertSame([], $verification->missingTables);
        $this->assertSame(['academy_modules.folder_slug'], $verification->missingColumns);
        $this->assertSame([], $verification->unrecorded);
    }
}
