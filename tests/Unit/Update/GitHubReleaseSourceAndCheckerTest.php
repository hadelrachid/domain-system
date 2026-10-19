<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit\Update;

use DomainSystem\Core\Update\Enum\UpdateStatus;
use DomainSystem\Core\Update\Exception\SourceUnavailableException;
use DomainSystem\Core\Update\Infrastructure\FilesystemInstalledState;
use DomainSystem\Core\Update\Infrastructure\GitHubReleaseSource;
use DomainSystem\Core\Update\Infrastructure\JsonUpdateStateStore;
use DomainSystem\Core\Update\Infrastructure\UpdateConfig;
use DomainSystem\Core\Update\Service\SemverComparator;
use DomainSystem\Core\Update\Service\UpdateChecker;
use DomainSystem\Core\Update\Service\UpdateTargetResolver;
use DomainSystem\Core\Update\Value\HttpResponse;
use DomainSystem\Tests\Support\ArrayHttpClient;
use PHPUnit\Framework\TestCase;

final class GitHubReleaseSourceAndCheckerTest extends TestCase
{
    private const API_URL = 'https://api.github.com/repos/hadelrachid/domain-system/releases/latest';
    private const SHA_URL = 'https://github.com/hadelrachid/domain-system/releases/download/v2.4.0/domain-system-2.4.0.zip.sha256';
    private const ZIP_URL = 'https://github.com/hadelrachid/domain-system/releases/download/v2.4.0/domain-system-2.4.0.zip';
    private const VALID_HASH = 'a948904f2f0f479b8f8197694b30184b0d2ed1c1cd2a1ec0fb85d299a192a447';

    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = str_replace('\\', '/', sys_get_temp_dir()) . '/ds_github_check_' . uniqid('', true);
        mkdir($this->tempDir . '/config', 0777, true);
        file_put_contents(
            $this->tempDir . '/config/update.json',
            json_encode([
                'core_repository' => 'hadelrachid/domain-system',
                'channel'         => 'stable',
                'check_ttl_hours' => 12,
                'auto_rollback'   => true,
            ], JSON_PRETTY_PRINT),
        );
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testUpdateConfigAndTargetResolverReadCoreTarget(): void
    {
        $config = new UpdateConfig($this->tempDir);
        $this->assertSame('hadelrachid/domain-system', $config->coreRepository());
        $this->assertSame('stable', $config->channel());
        $this->assertSame(12, $config->checkTtlHours());
        $this->assertTrue($config->autoRollback());

        $installed = new FilesystemInstalledState($this->tempDir, '2.3.0');
        $resolver = new UpdateTargetResolver($installed, $config->coreRepository(), $config->channel());
        $target = $resolver->core();

        $this->assertSame('core', $target->slug);
        $this->assertSame('2.3.0', $target->installedVersion);
    }

    public function testCheckerReturnsAvailableWhenNewerVersionExistsAndRespectsTtlAnd304(): void
    {
        $fixtureJson = (string)file_get_contents(dirname(__DIR__, 2) . '/Fixtures/github/releases_latest.json');
        $httpClient = new ArrayHttpClient([
            self::API_URL => new HttpResponse(200, ['etag' => 'W/"etag-v240"'], $fixtureJson, 'W/"etag-v240"'),
            self::SHA_URL => new HttpResponse(200, [], self::VALID_HASH . "  domain-system-2.4.0.zip\n"),
        ]);

        $now = 1_700_000_000;
        $comparator = new SemverComparator();
        $source = new GitHubReleaseSource($httpClient, $comparator);
        $stateStore = new JsonUpdateStateStore($this->tempDir);
        $checker = new UpdateChecker($source, $comparator, $stateStore, 12, static function () use (&$now): int {
            return $now;
        });

        $target = (new UpdateTargetResolver(new FilesystemInstalledState($this->tempDir, '2.3.0')))->core();

        // 1. Primeira verificação bate na API e devolve Available
        $res1 = $checker->check($target, false);
        $this->assertSame(UpdateStatus::Available, $res1->status);
        $this->assertNotNull($res1->release);
        $this->assertSame('2.4.0', $res1->release->version);
        $this->assertSame(self::VALID_HASH, $res1->release->sha256);
        $this->assertCount(2, $httpClient->recordedRequests());

        // 2. Segunda verificação 1 hora depois (< 12h TTL) sem $force usa cache sem fazer novas chamadas HTTP
        $now += 3600;
        $res2 = $checker->check($target, false);
        $this->assertSame(UpdateStatus::Available, $res2->status);
        $this->assertCount(2, $httpClient->recordedRequests(), 'Não deve chamar a API dentro do TTL');

        // 3. Após expirar o TTL (13h depois), envia If-None-Match e recebe 304 -> mantém cache Available
        $now += 13 * 3600;
        $httpClient->when(self::API_URL, static function (string $url, array $headers): HttpResponse {
            self::assertSame('W/"etag-v240"', $headers['If-None-Match'] ?? '');
            return new HttpResponse(304, [], '', 'W/"etag-v240"');
        });
        $res3 = $checker->check($target, false);
        $this->assertSame(UpdateStatus::Available, $res3->status);
        $this->assertCount(3, $httpClient->recordedRequests());
    }

    public function testCheckerReturnsUpToDateWhenInstalledVersionEqualsOrExceedsRelease(): void
    {
        $fixtureJson = (string)file_get_contents(dirname(__DIR__, 2) . '/Fixtures/github/releases_latest.json');
        $httpClient = new ArrayHttpClient([
            self::API_URL => new HttpResponse(200, [], $fixtureJson),
            self::SHA_URL => new HttpResponse(200, [], self::VALID_HASH),
        ]);

        $comparator = new SemverComparator();
        $source = new GitHubReleaseSource($httpClient, $comparator);
        $checker = new UpdateChecker($source, $comparator, new JsonUpdateStateStore($this->tempDir));

        $target = (new UpdateTargetResolver(new FilesystemInstalledState($this->tempDir, '2.4.0')))->core();
        $res = $checker->check($target, true);

        $this->assertSame(UpdateStatus::UpToDate, $res->status);

        // E também quando a Release no GitHub (ex.: v2.3.0 legada com domain-system-core.zip) é igual à instalada (2.3.0)
        $noAssetJson = (string)file_get_contents(dirname(__DIR__, 2) . '/Fixtures/github/releases_latest_no_asset.json');
        $httpLegacySameVer = new ArrayHttpClient([
            self::API_URL => new HttpResponse(200, [], $noAssetJson),
        ]);
        $checkerLegacy = new UpdateChecker(
            new GitHubReleaseSource($httpLegacySameVer, $comparator),
            $comparator,
            new JsonUpdateStateStore($this->tempDir),
        );
        $resLegacy = $checkerLegacy->check($target, true);
        $this->assertSame(UpdateStatus::UpToDate, $resLegacy->status);
    }

    public function testCheckerNeverThrowsOnMalformedJsonMissingAssetOrInvalidSha256(): void
    {
        $comparator = new SemverComparator();
        $target = (new UpdateTargetResolver(new FilesystemInstalledState($this->tempDir, '2.3.0')))->core();

        // Caso 1: JSON malformado -> Unknown
        $httpBadJson = new ArrayHttpClient([
            self::API_URL => new HttpResponse(200, [], '{json-quebrado'),
        ]);
        $checker1 = new UpdateChecker(
            new GitHubReleaseSource($httpBadJson, $comparator),
            $comparator,
            new JsonUpdateStateStore($this->tempDir),
        );
        $res1 = $checker1->check($target, true);
        $this->assertSame(UpdateStatus::Unknown, $res1->status);
        $this->assertNotEmpty($res1->reasons);

        // Caso 2: Asset domain-system-2.4.0.zip ausente -> SourceUnavailableException na fonte e Unknown no checker
        $noAssetJson = (string)file_get_contents(dirname(__DIR__, 2) . '/Fixtures/github/releases_latest_no_asset.json');
        $httpNoAsset = new ArrayHttpClient([
            self::API_URL => new HttpResponse(200, [], $noAssetJson),
        ]);
        $sourceNoAsset = new GitHubReleaseSource($httpNoAsset, $comparator);
        try {
            $sourceNoAsset->latest($target);
            $this->fail('Deveria lançar SourceUnavailableException quando falta o asset.');
        } catch (SourceUnavailableException $e) {
            $this->assertStringContainsString('domain-system-2.4.0.zip', $e->getMessage());
        }

        // Caso 3: .sha256 inválido -> SourceUnavailableException na fonte e Unknown no checker
        $fixtureJson = (string)file_get_contents(dirname(__DIR__, 2) . '/Fixtures/github/releases_latest.json');
        $httpBadSha = new ArrayHttpClient([
            self::API_URL => new HttpResponse(200, [], $fixtureJson),
            self::SHA_URL => new HttpResponse(200, [], 'nao-e-um-sha256-valido'),
        ]);
        $checker3 = new UpdateChecker(
            new GitHubReleaseSource($httpBadSha, $comparator),
            $comparator,
            new JsonUpdateStateStore($this->tempDir),
        );
        $res3 = $checker3->check($target, true);
        $this->assertSame(UpdateStatus::Unknown, $res3->status);
    }

    public function testFetchPackageDownloadsArchiveToDestinationDir(): void
    {
        $fixtureJson = (string)file_get_contents(dirname(__DIR__, 2) . '/Fixtures/github/releases_latest.json');
        $httpClient = new ArrayHttpClient([
            self::API_URL => new HttpResponse(200, [], $fixtureJson),
            self::SHA_URL => new HttpResponse(200, [], self::VALID_HASH),
            self::ZIP_URL => new HttpResponse(200, [], 'PK-DUMMY-ZIP-CONTENT'),
        ]);

        $source = new GitHubReleaseSource($httpClient, new SemverComparator());
        $target = (new UpdateTargetResolver(new FilesystemInstalledState($this->tempDir, '2.3.0')))->core();
        $release = $source->latest($target);
        $this->assertNotNull($release);

        $downloaded = $source->fetchPackage($release, $this->tempDir . '/temp/updates/2.4.0');
        $this->assertFileExists($downloaded->archivePath);
        $this->assertSame(20, $downloaded->sizeBytes);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $item) {
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
