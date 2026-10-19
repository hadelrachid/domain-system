<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit\Update;

use DomainSystem\Core\Update\Exception\InvalidManifestException;
use DomainSystem\Core\Update\Exception\PackageIntegrityException;
use DomainSystem\Core\Update\Infrastructure\CoreProtectedPaths;
use DomainSystem\Core\Update\Infrastructure\Sha256PackageVerifier;
use DomainSystem\Core\Update\Infrastructure\ZipPackageExtractor;
use DomainSystem\Core\Update\Service\JsonUpdateManifestParser;
use DomainSystem\Tests\Support\PackageBuilder;
use PHPUnit\Framework\TestCase;

final class PackageVerificationTest extends TestCase
{
    private string $tempDir;
    private string $quarantineBaseDir;
    private JsonUpdateManifestParser $parser;
    private CoreProtectedPaths $protectedPaths;
    private ZipPackageExtractor $extractor;
    private Sha256PackageVerifier $verifier;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ds-pkg-test-' . bin2hex(random_bytes(6));
        $this->quarantineBaseDir = $this->tempDir . DIRECTORY_SEPARATOR . 'quarantine';
        mkdir($this->quarantineBaseDir, 0755, true);

        $this->parser = new JsonUpdateManifestParser();
        $this->protectedPaths = new CoreProtectedPaths();
        $this->extractor = new ZipPackageExtractor();
        $this->verifier = new Sha256PackageVerifier(
            manifestParser: $this->parser,
            protectedPaths: $this->protectedPaths,
            extractor: $this->extractor,
            quarantineBaseDir: $this->quarantineBaseDir,
        );
    }

    protected function tearDown(): void
    {
        ZipPackageExtractor::removeDirectory($this->tempDir);
    }

    public function testValidPackagePassesVerificationAndExtractsFiles(): void
    {
        $package = PackageBuilder::create()
            ->withVersion('2.4.0')
            ->withFile('src/Core/Foo.php', "<?php\nnamespace DomainSystem\\Core;\nfinal class Foo {}\n")
            ->withFile('bootstrap.php', "<?php\ndefine('DS_VERSION', '2.4.0');\n")
            ->withFile('config/new_feature.json', '{"enabled":true}', 'default_if_missing')
            ->withRemoved(['src/Core/OldClass.php'])
            ->withMigration(
                scope: '__core__',
                name: '2026_11_01_000001_add_col',
                content: "<?php\nreturn ['up' => function () {}];\n",
                type: 'additive'
            )
            ->withExpects(['academy_modules' => ['folder_slug']])
            ->build($this->tempDir);

        $verified = $this->verifier->verify($package);

        $this->assertSame('2.4.0', $verified->manifest->version);
        $this->assertDirectoryExists($verified->extractedPath);
        $this->assertFileExists($verified->extractedPath . '/src/Core/Foo.php');
        $this->assertFileExists($verified->extractedPath . '/bootstrap.php');
        $this->assertCount(4, $verified->manifest->files);
        $this->assertSame(['src/Core/OldClass.php'], $verified->manifest->removed);
    }

    public function testWrongZipSha256IsRejectedAndQuarantineStaysEmpty(): void
    {
        $package = PackageBuilder::create()
            ->withFile('src/Core/Foo.php', '<?php // ok')
            ->build($this->tempDir, overrideReleaseSha256: str_repeat('0', 64));

        try {
            $this->verifier->verify($package);
            $this->fail('Deveria ter lançado PackageIntegrityException para hash do ZIP errado.');
        } catch (PackageIntegrityException $e) {
            $this->assertStringContainsString('diverge do esperado', $e->getMessage());
            $this->assertQuarantineIsClean();
        }
    }

    public function testWrongFileSha256InsidePackageIsRejectedAndCleanedUp(): void
    {
        $package = PackageBuilder::create()
            ->withFile(
                path: 'src/Core/Foo.php',
                content: '<?php // conteudo adulterado',
                overrideSha256: str_repeat('b', 64)
            )
            ->build($this->tempDir);

        try {
            $this->verifier->verify($package);
            $this->fail('Deveria ter lançado PackageIntegrityException para hash de arquivo divergente.');
        } catch (PackageIntegrityException $e) {
            $this->assertStringContainsString('Hash SHA-256 do arquivo', $e->getMessage());
            $this->assertQuarantineIsClean();
        }
    }

    public function testExtraUnlistedFileInZipIsRejectedAndCleanedUp(): void
    {
        $package = PackageBuilder::create()
            ->withFile('src/Core/Foo.php', '<?php // ok')
            ->withExtraZipFile('src/Core/Backdoor.php', '<?php // extra nao listado')
            ->build($this->tempDir);

        try {
            $this->verifier->verify($package);
            $this->fail('Deveria ter lançado PackageIntegrityException para arquivo extra fora do manifest.');
        } catch (PackageIntegrityException $e) {
            $this->assertStringContainsString('Arquivo extra não declarado', $e->getMessage());
            $this->assertQuarantineIsClean();
        }
    }

    public function testMissingFileListedInManifestIsRejectedAndCleanedUp(): void
    {
        $package = PackageBuilder::create()
            ->withFile('src/Core/Foo.php', '<?php // ok')
            ->withMissingDiskFile('src/Core/Missing.php')
            ->build($this->tempDir);

        try {
            $this->verifier->verify($package);
            $this->fail('Deveria ter lançado PackageIntegrityException para arquivo listado ausente.');
        } catch (PackageIntegrityException $e) {
            $this->assertStringContainsString('ausente no pacote extraído', $e->getMessage());
            $this->assertQuarantineIsClean();
        }
    }

    /**
     * @dataProvider unsafePathsProvider
     */
    public function testZipSlipAndUnsafePathsAreRejectedAndCleanedUp(string $unsafePath): void
    {
        try {
            $this->extractor->assertSafeRelativePath($unsafePath);
            $this->fail(sprintf('assertSafeRelativePath deveria ter rejeitado "%s".', $unsafePath));
        } catch (PackageIntegrityException) {
            $this->addToAssertionCount(1);
        }

        $package = PackageBuilder::create()
            ->withFile('src/Core/Foo.php', '<?php // ok')
            ->withExtraZipFile($unsafePath, 'malicious')
            ->build($this->tempDir);

        try {
            $this->verifier->verify($package);
            $this->fail(sprintf('Deveria ter rejeitado o caminho inseguro "%s".', $unsafePath));
        } catch (PackageIntegrityException) {
            $this->assertQuarantineIsClean();
        }
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function unsafePathsProvider(): array
    {
        return [
            'traversal_root' => ['../evil.php'],
            'traversal_nested' => ['src/../../evil.php'],
            'absolute_slash' => ['/etc/passwd'],
            'backslash' => ['src\\Core\\Evil.php'],
            'drive_letter' => ['C:/Windows/win.ini'],
        ];
    }

    public function testSymlinkInsideZipIsRejectedAndCleanedUp(): void
    {
        $package = PackageBuilder::create()
            ->withFile('src/Core/Foo.php', '<?php // ok')
            ->withSymlink('src/Core/Link.php', '/etc/passwd')
            ->build($this->tempDir);

        try {
            $this->verifier->verify($package);
            $this->fail('Deveria ter rejeitado entrada simbólica no ZIP.');
        } catch (PackageIntegrityException $e) {
            $this->assertStringContainsString('Link simbólico', $e->getMessage());
            $this->assertQuarantineIsClean();
        }
    }

    public function testCaseInsensitiveCollisionInZipIsRejectedAndCleanedUp(): void
    {
        $package = PackageBuilder::create()
            ->withFile('src/Core/Foo.php', '<?php // 1')
            ->withFile('src/Core/foo.php', '<?php // 2')
            ->build($this->tempDir);

        try {
            $this->verifier->verify($package);
            $this->fail('Deveria ter rejeitado colisão case-insensitive.');
        } catch (PackageIntegrityException $e) {
            $this->assertStringContainsString('case-insensitive', $e->getMessage());
            $this->assertQuarantineIsClean();
        }
    }

    public function testManifestWithProtocol2IsRejectedWithInvalidManifestException(): void
    {
        $package = PackageBuilder::create()
            ->withProtocol(2, 2)
            ->withFile('src/Core/Foo.php', '<?php // ok')
            ->build($this->tempDir);

        try {
            $this->verifier->verify($package);
            $this->fail('Deveria ter lançado InvalidManifestException para protocolo 2.');
        } catch (InvalidManifestException $e) {
            $this->assertStringContainsString('Protocolo de manifest 2 não suportado', $e->getMessage());
            $this->assertQuarantineIsClean();
        }
    }

    public function testManifestMissingRequiredFieldIsRejectedWithInvalidManifestException(): void
    {
        $incompleteManifest = [
            'protocol' => 1,
            'minUpdaterProtocol' => 1,
            'kind' => 'core',
            'slug' => 'core',
            // 'version' ausente propositalmente
            'requires' => ['php' => '8.1', 'extensions' => ['pdo'], 'osVersion' => '2.3'],
            'files' => [],
            'removed' => [],
            'migrations' => [],
            'expects' => new \stdClass(),
        ];

        $package = PackageBuilder::create()
            ->withRawManifest($incompleteManifest)
            ->build($this->tempDir);

        try {
            $this->verifier->verify($package);
            $this->fail('Deveria ter lançado InvalidManifestException para campo obrigatório ausente.');
        } catch (InvalidManifestException $e) {
            $this->assertStringContainsString('Campo obrigatório "version" ausente', $e->getMessage());
            $this->assertQuarantineIsClean();
        }
    }

    public function testFileOutsideAllowedRootsIsRejectedAndCleanedUp(): void
    {
        $package = PackageBuilder::create()
            ->withFile('storage/state/hack.php', '<?php // fora de allowedRoots')
            ->build($this->tempDir);

        try {
            $this->verifier->verify($package);
            $this->fail('Deveria ter rejeitado arquivo fora das raízes permitidas.');
        } catch (PackageIntegrityException $e) {
            $this->assertStringContainsString('fora das raízes permitidas', $e->getMessage());
            $this->assertQuarantineIsClean();
        }
    }

    public function testZipBombExceedingLimitsIsRejectedAndCleanedUp(): void
    {
        $strictExtractor = new ZipPackageExtractor(
            maxFiles: 10,
            maxTotalBytes: 2048,
            maxCompressionRatio: 20.0,
        );
        $strictVerifier = new Sha256PackageVerifier(
            manifestParser: $this->parser,
            protectedPaths: $this->protectedPaths,
            extractor: $strictExtractor,
            quarantineBaseDir: $this->quarantineBaseDir,
        );

        // 50 KB de zeros comprime para poucos bytes (razão > 100x e > 2048 bytes)
        $package = PackageBuilder::create()
            ->withFile('src/Core/Bomb.php', str_repeat("\0", 50000))
            ->build($this->tempDir);

        try {
            $strictVerifier->verify($package);
            $this->fail('Deveria ter rejeitado pacote que estoura razão de compressão / limite de bytes.');
        } catch (PackageIntegrityException $e) {
            $this->assertQuarantineIsClean();
        }
    }

    private function assertQuarantineIsClean(): void
    {
        $entries = array_values(array_diff(scandir($this->quarantineBaseDir) ?: [], ['.', '..']));
        $this->assertSame(
            [],
            $entries,
            'O diretório base de quarentena deve ficar vazio após qualquer falha na verificação.'
        );
    }
}
