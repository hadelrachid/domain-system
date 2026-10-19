<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit\Update;

use DomainSystem\Core\Update\Enum\UpdateKind;
use DomainSystem\Core\Update\Infrastructure\ZipDirectoryBackup;
use DomainSystem\Core\Update\Infrastructure\ZipPackageExtractor;
use DomainSystem\Core\Update\Value\BackupScope;
use DomainSystem\SystemApps\Database\Backup\DatabaseBackupFactory;
use DomainSystem\SystemApps\Database\Backup\MysqlDatabaseBackup;
use DomainSystem\SystemApps\Database\Backup\SqliteDatabaseBackup;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class BackupTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ds-backup-test-' . bin2hex(random_bytes(6));
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        ZipPackageExtractor::removeDirectory($this->tempDir);
    }

    public function testZipDirectoryBackupCreatesRestoresRespectsExclusionsAndPrunes(): void
    {
        $root = $this->tempDir . '/project';
        mkdir($root . '/src/Core', 0755, true);
        mkdir($root . '/public/uploads', 0755, true);
        mkdir($root . '/config', 0755, true);
        mkdir($root . '/storage/logs', 0755, true);
        mkdir($root . '/temp', 0755, true);

        file_put_contents($root . '/bootstrap.php', "<?php // v2.3.0\n");
        file_put_contents($root . '/src/Core/Kernel.php', "<?php // kernel original\n");
        file_put_contents($root . '/config/plugins.json', '{"seo":true}');
        file_put_contents($root . '/.env', 'DB_PASS=secret');
        file_put_contents($root . '/database.sqlite', 'sqlite-bytes');
        file_put_contents($root . '/public/index.php', "<?php // index\n");
        file_put_contents($root . '/public/uploads/foto.jpg', 'img-bytes');
        file_put_contents($root . '/storage/logs/app.log', 'log-bytes');

        $backup = new ZipDirectoryBackup($root);
        $scope = new BackupScope(UpdateKind::Core, 'core');

        $record1 = $backup->create($scope, 'pre-update-2.4.0');
        $this->assertFileExists($record1->codePath);
        $this->assertFileExists($root . '/storage/backups/.htaccess');

        // Verifica o que entrou e o que foi excluído no ZIP
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($record1->codePath));
        $this->assertNotFalse($zip->locateName('bootstrap.php'));
        $this->assertNotFalse($zip->locateName('src/Core/Kernel.php'));
        $this->assertNotFalse($zip->locateName('config/plugins.json'));
        $this->assertNotFalse($zip->locateName('public/index.php'));
        $this->assertFalse($zip->locateName('.env'));
        $this->assertFalse($zip->locateName('database.sqlite'));
        $this->assertFalse($zip->locateName('public/uploads/foto.jpg'));
        $this->assertFalse($zip->locateName('storage/logs/app.log'));
        $zip->close();

        // Modifica arquivos do projeto e depois restaura o backup
        file_put_contents($root . '/src/Core/Kernel.php', "<?php // corrompido\n");
        file_put_contents($root . '/bootstrap.php', "<?php // v2.4.0 quebrado\n");

        $backup->restore($record1);

        $this->assertSame("<?php // kernel original\n", file_get_contents($root . '/src/Core/Kernel.php'));
        $this->assertSame("<?php // v2.3.0\n", file_get_contents($root . '/bootstrap.php'));
        $this->assertSame('DB_PASS=secret', file_get_contents($root . '/.env'));

        // Testa list() e prune($scope, 2)
        $record2 = $backup->create($scope, 'backup-2');
        $record3 = $backup->create($scope, 'backup-3');

        $this->assertCount(3, $backup->list($scope));
        $removed = $backup->prune($scope, 2);
        $this->assertSame(1, $removed);
        $this->assertCount(2, $backup->list($scope));
    }

    public function testSqliteDatabaseBackupRoundTrip(): void
    {
        $dbFile = $this->tempDir . '/test.sqlite';
        $dumpFile = $this->tempDir . '/dumps/db.sqlite.bak';

        $pdo = new PDO('sqlite:' . $dbFile);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE items (id INTEGER PRIMARY KEY, name TEXT)');
        $pdo->exec("INSERT INTO items (name) VALUES ('original_1'), ('original_2')");

        $adapter = DatabaseBackupFactory::create($pdo, $dbFile);
        $this->assertInstanceOf(SqliteDatabaseBackup::class, $adapter);
        $this->assertTrue($adapter->isSupported());

        $dumpResult = $adapter->dump($dumpFile);
        $this->assertFileExists($dumpResult->dumpPath);
        $this->assertGreaterThan(0, $dumpResult->sizeBytes);
        $this->assertSame(1, $dumpResult->tablesCount);

        // Altera o banco após o dump
        $pdo->exec("DELETE FROM items WHERE name = 'original_2'");
        $pdo->exec("INSERT INTO items (name) VALUES ('corrupted_after_update')");
        unset($pdo);

        // Restaura a partir do dump
        $restorePdo = new PDO('sqlite:' . $dbFile);
        $restoreAdapter = new SqliteDatabaseBackup($restorePdo, $dbFile);
        $restoreAdapter->restore($dumpFile);
        unset($restorePdo);

        $verifyPdo = new PDO('sqlite:' . $dbFile);
        $names = $verifyPdo->query('SELECT name FROM items ORDER BY id ASC')->fetchAll(PDO::FETCH_COLUMN, 0);
        $this->assertSame(['original_1', 'original_2'], $names);
        unset($verifyPdo);
    }

    #[Group('mysql')]
    public function testMysqlDatabaseBackupRoundTripWhenEnvIsProvided(): void
    {
        $dsn = getenv('DS_TEST_MYSQL_DSN');
        if (!is_string($dsn) || trim($dsn) === '') {
            $this->markTestSkipped('Defina DS_TEST_MYSQL_DSN para executar o teste de integração MySQL.');
        }

        $user = (string)(getenv('DS_TEST_MYSQL_USER') ?: 'root');
        $pass = (string)(getenv('DS_TEST_MYSQL_PASS') ?: '');

        $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec('DROP TABLE IF EXISTS ds_backup_test_tbl');
        $pdo->exec('CREATE TABLE ds_backup_test_tbl (id INT AUTO_INCREMENT PRIMARY KEY, label VARCHAR(100) NOT NULL)');
        $pdo->exec("INSERT INTO ds_backup_test_tbl (label) VALUES ('alpha'), ('beta')");

        $backup = new MysqlDatabaseBackup($pdo);
        $this->assertTrue($backup->isSupported());

        $dumpPath = $this->tempDir . '/mysql_dump.sql';
        $result = $backup->dump($dumpPath);
        $this->assertFileExists($result->dumpPath);
        $this->assertGreaterThan(0, $result->sizeBytes);

        $pdo->exec("DELETE FROM ds_backup_test_tbl WHERE label = 'beta'");
        $backup->restore($dumpPath);

        $labels = $pdo->query('SELECT label FROM ds_backup_test_tbl ORDER BY id ASC')->fetchAll(PDO::FETCH_COLUMN, 0);
        $this->assertSame(['alpha', 'beta'], $labels);

        $pdo->exec('DROP TABLE IF EXISTS ds_backup_test_tbl');
    }
}
