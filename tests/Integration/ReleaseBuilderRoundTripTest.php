<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Integration;

use DomainSystem\Core\Update\Exception\PackageIntegrityException;
use DomainSystem\Core\Update\Infrastructure\CoreProtectedPaths;
use DomainSystem\Core\Update\Infrastructure\Sha256PackageVerifier;
use DomainSystem\Core\Update\Infrastructure\ZipPackageExtractor;
use DomainSystem\Core\Update\Service\JsonUpdateManifestParser;
use DomainSystem\Core\Update\Value\DownloadedPackage;
use DomainSystem\Core\Update\Value\ReleaseInfo;
use DomainSystem\Tools\Release\ReleaseBuilder;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/tools/release/ReleaseBuilder.php';

final class ReleaseBuilderRoundTripTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = str_replace('\\', '/', sys_get_temp_dir()) . '/ds_rel_builder_' . uniqid('', true);
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        ZipPackageExtractor::removeDirectory($this->tempDir);
    }

    public function testReleaseBuilderGeneratesPackageAcceptedBySha256PackageVerifierAndRejectsTamperedByte(): void
    {
        $fixtureRoot = $this->tempDir . '/repo';
        $distDir = $this->tempDir . '/dist';
        $quarantineBase = $this->tempDir . '/quarantine';

        mkdir($fixtureRoot . '/vendor', 0777, true);
        mkdir($fixtureRoot . '/src/Core/Database/migrations', 0777, true);
        mkdir($fixtureRoot . '/src/Plugins/academy/migrations', 0777, true);
        mkdir($fixtureRoot . '/public', 0777, true);
        mkdir($fixtureRoot . '/config', 0777, true);
        mkdir($fixtureRoot . '/release', 0777, true);
        mkdir($fixtureRoot . '/tests', 0777, true);
        mkdir($fixtureRoot . '/storage', 0777, true);

        file_put_contents($fixtureRoot . '/bootstrap.php', "<?php\ndefine('DS_VERSION', '2.4.0');\ndefine('DS_OS_VERSION', '2.3');\n");
        file_put_contents($fixtureRoot . '/composer.json', json_encode(['name' => 'domain-system/core', 'version' => '2.4.0']));
        file_put_contents($fixtureRoot . '/vendor/autoload.php', "<?php // autoload\n");
        file_put_contents($fixtureRoot . '/public/index.php', "<?php echo 'ok';\n");
        file_put_contents($fixtureRoot . '/src/Core/Application.php', "<?php namespace DomainSystem\\Core; class Application {}\n");
        file_put_contents(
            $fixtureRoot . '/src/Core/Database/migrations/2026_11_01_000001_core_init.php',
            "<?php return ['up' => function (\\PDO \$pdo): void {}];\n"
        );
        file_put_contents(
            $fixtureRoot . '/src/Plugins/academy/migrations/2026_10_15_000002_create_academy_structure.php',
            "<?php return ['up' => function (\\PDO \$pdo): void {}];\n"
        );
        file_put_contents($fixtureRoot . '/config/update.json', json_encode(['channel' => 'stable']));
        file_put_contents($fixtureRoot . '/release/exclude.txt', ".git*\ntests/\nstorage/\n*.sqlite\n.env\n");
        file_put_contents($fixtureRoot . '/release/schema-expects.json', json_encode([
            'academy_modules' => ['folder_slug'],
            'academy_lessons' => ['mobile_profile'],
        ]));

        // Arquivos que jamais devem entrar no pacote
        file_put_contents($fixtureRoot . '/.env', "APP_KEY=secret\n");
        file_put_contents($fixtureRoot . '/database.sqlite', 'sqlite-binary');
        file_put_contents($fixtureRoot . '/tests/SecretTest.php', '<?php // test');
        file_put_contents($fixtureRoot . '/storage/state.json', '{}');

        $builder = new ReleaseBuilder($fixtureRoot);
        $buildResult = $builder->build($distDir);

        $this->assertSame('2.4.0', $buildResult['version']);
        $this->assertFileExists($buildResult['zipPath']);
        $this->assertFileExists($buildResult['sha256Path']);
        $this->assertSame(2, $buildResult['migrationCount']);

        $releaseInfo = new ReleaseInfo(
            version: '2.4.0',
            tag: 'v2.4.0',
            publishedAt: '2026-10-19T18:00:00Z',
            changelog: 'Release de teste',
            assetUrl: 'https://github.com/hadelrachid/domain-system/releases/download/v2.4.0/domain-system-2.4.0.zip',
            assetName: 'domain-system-2.4.0.zip',
            sizeBytes: (int)filesize($buildResult['zipPath']),
            sha256: $buildResult['sha256'],
        );

        $verifier = new Sha256PackageVerifier(
            new JsonUpdateManifestParser(),
            new CoreProtectedPaths(),
            new ZipPackageExtractor(),
            $quarantineBase,
        );

        $downloaded = new DownloadedPackage(
            release: $releaseInfo,
            archivePath: $buildResult['zipPath'],
            sizeBytes: $releaseInfo->sizeBytes,
        );

        $verified = $verifier->verify($downloaded);
        $this->assertSame('2.4.0', $verified->manifest->version);
        $this->assertSame(['folder_slug'], $verified->manifest->expects['academy_modules'] ?? []);
        $this->assertFileExists($verified->extractedPath . '/bootstrap.php');
        $this->assertFileDoesNotExist($verified->extractedPath . '/.env');
        $this->assertFileDoesNotExist($verified->extractedPath . '/database.sqlite');
        $this->assertFileDoesNotExist($verified->extractedPath . '/tests/SecretTest.php');

        ZipPackageExtractor::removeDirectory($verified->extractedPath);

        // Adulterar 1 byte no arquivo ZIP gerado => Sha256PackageVerifier deve rejeitar
        $bytes = (string)file_get_contents($buildResult['zipPath']);
        $pos = (int)floor(strlen($bytes) / 2);
        $bytes[$pos] = chr((ord($bytes[$pos]) ^ 0xFF));
        file_put_contents($buildResult['zipPath'], $bytes);

        $this->expectException(PackageIntegrityException::class);
        $verifier->verify($downloaded);
    }
}
