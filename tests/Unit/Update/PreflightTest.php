<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit\Update;

use DomainSystem\Core\Contracts\Update\InstalledStateProviderInterface;
use DomainSystem\Core\Contracts\Update\PreflightCheckInterface;
use DomainSystem\Core\Contracts\Update\SchemaPlannerInterface;
use DomainSystem\Core\Update\Enum\CheckLevel;
use DomainSystem\Core\Update\Enum\UpdateKind;
use DomainSystem\Core\Update\Exception\SchemaPlanException;
use DomainSystem\Core\Update\Infrastructure\CoreProtectedPaths;
use DomainSystem\Core\Update\Infrastructure\FilesystemInstalledState;
use DomainSystem\Core\Update\Infrastructure\Preflight\DiskSpaceCheck;
use DomainSystem\Core\Update\Infrastructure\Preflight\DowngradeCheck;
use DomainSystem\Core\Update\Infrastructure\Preflight\ExtensionsCheck;
use DomainSystem\Core\Update\Infrastructure\Preflight\PhpVersionCheck;
use DomainSystem\Core\Update\Infrastructure\Preflight\PluginCompatibilityCheck;
use DomainSystem\Core\Update\Infrastructure\Preflight\ProtocolCheck;
use DomainSystem\Core\Update\Infrastructure\Preflight\SchemaPlanCheck;
use DomainSystem\Core\Update\Infrastructure\Preflight\WritablePathsCheck;
use DomainSystem\Core\Update\Service\JsonUpdateManifestParser;
use DomainSystem\Core\Update\Service\Preflight;
use DomainSystem\Core\Update\Service\SemverComparator;
use DomainSystem\Core\Update\Value\InstalledPlugin;
use DomainSystem\Core\Update\Value\ReleaseInfo;
use DomainSystem\Core\Update\Value\SchemaPlan;
use DomainSystem\Core\Update\Value\UpdateManifest;
use DomainSystem\Core\Update\Value\UpdateTarget;
use DomainSystem\Core\Update\Value\VerifiedPackage;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PreflightTest extends TestCase
{
    private function makeVerifiedPackage(
        string $version = '2.4.0',
        int $minUpdaterProtocol = 1,
        string $phpRequires = '8.1',
        array $extensions = ['pdo', 'json'],
        array $files = [],
        int $sizeBytes = 1000,
    ): VerifiedPackage {
        $manifest = new UpdateManifest(
            protocol: 1,
            minUpdaterProtocol: $minUpdaterProtocol,
            kind: UpdateKind::Core,
            slug: 'core',
            version: $version,
            requires: [
                'php' => $phpRequires,
                'extensions' => $extensions,
                'osVersion' => '2.3',
            ],
            files: $files,
            removed: [],
            migrations: [],
            expects: [],
        );

        $release = new ReleaseInfo(
            version: $version,
            tag: 'v' . $version,
            publishedAt: '2026-10-19T12:00:00Z',
            changelog: 'Notes',
            assetUrl: 'https://github.com/hadelrachid/domain-system/releases/download/v' . $version . '/pkg.zip',
            assetName: 'pkg.zip',
            sizeBytes: $sizeBytes,
            sha256: str_repeat('a', 64),
        );

        return new VerifiedPackage(
            release: $release,
            manifest: $manifest,
            extractedPath: sys_get_temp_dir(),
        );
    }

    private function makeTarget(string $installedVersion = '2.3.0'): UpdateTarget
    {
        return new UpdateTarget(
            kind: UpdateKind::Core,
            slug: 'core',
            repository: 'hadelrachid/domain-system',
            installedVersion: $installedVersion,
            channel: 'stable',
        );
    }

    public function testPhpVersionCheckPassAndFail(): void
    {
        $pkg = $this->makeVerifiedPackage(phpRequires: '8.2.0');
        $target = $this->makeTarget();

        $passCheck = new PhpVersionCheck('8.2.12');
        $resPass = $passCheck->run($pkg, $target);
        $this->assertSame(CheckLevel::Ok, $resPass[0]->level);

        $failCheck = new PhpVersionCheck('8.1.9');
        $resFail = $failCheck->run($pkg, $target);
        $this->assertSame(CheckLevel::Block, $resFail[0]->level);
    }

    public function testExtensionsCheckPassAndFail(): void
    {
        $pkg = $this->makeVerifiedPackage(extensions: ['pdo', 'mbstring', 'sodium']);
        $target = $this->makeTarget();

        $allLoaded = new ExtensionsCheck(static fn(string $ext): bool => true);
        $this->assertSame(CheckLevel::Ok, $allLoaded->run($pkg, $target)[0]->level);

        $missingSodium = new ExtensionsCheck(static fn(string $ext): bool => $ext !== 'sodium');
        $res = $missingSodium->run($pkg, $target);
        $this->assertSame(CheckLevel::Block, $res[0]->level);
        $this->assertStringContainsString('sodium', $res[0]->message);
    }

    public function testDiskSpaceCheckPassAndFail(): void
    {
        $pkg = $this->makeVerifiedPackage(sizeBytes: 10000);
        $target = $this->makeTarget();

        $enough = new DiskSpaceCheck(sys_get_temp_dir(), freeBytesOverride: 30000);
        $this->assertSame(CheckLevel::Ok, $enough->run($pkg, $target)[0]->level);

        $insufficient = new DiskSpaceCheck(sys_get_temp_dir(), freeBytesOverride: 29999);
        $this->assertSame(CheckLevel::Block, $insufficient->run($pkg, $target)[0]->level);
    }

    public function testWritablePathsCheckPassAndFail(): void
    {
        $pkg = $this->makeVerifiedPackage();
        $target = $this->makeTarget();
        $protectedPaths = new CoreProtectedPaths();

        $writable = new WritablePathsCheck(sys_get_temp_dir(), $protectedPaths, static fn(): bool => true);
        $this->assertSame(CheckLevel::Ok, $writable->run($pkg, $target)[0]->level);

        $readOnlyStorage = new WritablePathsCheck(
            '/fake/root',
            $protectedPaths,
            static fn(string $p): bool => !str_contains($p, 'storage')
        );
        $res = $readOnlyStorage->run($pkg, $target);
        $this->assertSame(CheckLevel::Block, $res[0]->level);
    }

    public function testProtocolCheckPassAndFail(): void
    {
        $parser = new JsonUpdateManifestParser();
        $check = new ProtocolCheck($parser);
        $target = $this->makeTarget();

        $okPkg = $this->makeVerifiedPackage(minUpdaterProtocol: 1);
        $this->assertSame(CheckLevel::Ok, $check->run($okPkg, $target)[0]->level);

        $futurePkg = $this->makeVerifiedPackage(minUpdaterProtocol: 2);
        $this->assertSame(CheckLevel::Block, $check->run($futurePkg, $target)[0]->level);
    }

    public function testDowngradeCheckBlocksSameOrLowerVersionAndAllowsNewer(): void
    {
        $check = new DowngradeCheck(new SemverComparator());
        $target = $this->makeTarget('2.3.0');

        $newer = $this->makeVerifiedPackage(version: '2.4.0');
        $this->assertSame(CheckLevel::Ok, $check->run($newer, $target)[0]->level);

        $same = $this->makeVerifiedPackage(version: '2.3.0');
        $this->assertSame(CheckLevel::Block, $check->run($same, $target)[0]->level);

        $older = $this->makeVerifiedPackage(version: '2.2.9');
        $this->assertSame(CheckLevel::Block, $check->run($older, $target)[0]->level);
    }

    public function testPluginCompatibilityCheckEmitsWarningForUnbundledPluginWithDifferentMajor(): void
    {
        $state = new class implements InstalledStateProviderInterface {
            public function coreVersion(): string
            {
                return '2.3.0';
            }

            public function plugins(): array
            {
                return [
                    'bundled_plugin' => new InstalledPlugin('bundled_plugin', '2.3.0', '2.3', true, true),
                    'legacy_third_party' => new InstalledPlugin('legacy_third_party', '1.0.0', '1.0', false, true),
                ];
            }
        };

        $check = new PluginCompatibilityCheck($state);
        $pkg = $this->makeVerifiedPackage(
            version: '2.4.0',
            files: [
                ['path' => 'src/Plugins/bundled_plugin/Plugin.php', 'sha256' => str_repeat('a', 64), 'size' => 10, 'mode' => 'replace'],
            ],
        );

        $results = $check->run($pkg, $this->makeTarget());
        $this->assertCount(1, $results);
        $this->assertSame(CheckLevel::Warn, $results[0]->level);
        $this->assertStringContainsString('legacy_third_party', $results[0]->message);
    }

    public function testSchemaPlanCheckHandlesOkDestructiveBlockedAndException(): void
    {
        $pkg = $this->makeVerifiedPackage();
        $target = $this->makeTarget();

        $okPlanner = new class implements SchemaPlannerInterface {
            public function plan(UpdateManifest $manifest, string $extractedPath): SchemaPlan
            {
                return new SchemaPlan('__core__', [], [], false, false);
            }
        };
        $this->assertSame(CheckLevel::Ok, (new SchemaPlanCheck($okPlanner))->run($pkg, $target)[0]->level);

        $destructivePlanner = new class implements SchemaPlannerInterface {
            public function plan(UpdateManifest $manifest, string $extractedPath): SchemaPlan
            {
                return new SchemaPlan(
                    '__core__',
                    [['scope' => '__core__', 'name' => 'm1', 'sha256' => str_repeat('a', 64), 'type' => 'destructive']],
                    [],
                    true,
                    true,
                );
            }
        };
        $destRes = (new SchemaPlanCheck($destructivePlanner))->run($pkg, $target);
        $this->assertCount(2, $destRes);
        $this->assertSame(CheckLevel::Warn, $destRes[1]->level);

        $blockedPlanner = new class implements SchemaPlannerInterface {
            public function plan(UpdateManifest $manifest, string $extractedPath): SchemaPlan
            {
                return new SchemaPlan('__core__', [], ['2026_12_01_future_mig'], false, false);
            }
        };
        $this->assertSame(CheckLevel::Block, (new SchemaPlanCheck($blockedPlanner))->run($pkg, $target)[0]->level);

        $throwingPlanner = new class implements SchemaPlannerInterface {
            public function plan(UpdateManifest $manifest, string $extractedPath): SchemaPlan
            {
                throw new SchemaPlanException('Hash divergente na migration.');
            }
        };
        $this->assertSame(CheckLevel::Block, (new SchemaPlanCheck($throwingPlanner))->run($pkg, $target)[0]->level);
    }

    public function testPreflightCompositeAggregatesAndConvertsThrownExceptionIntoBlock(): void
    {
        $pkg = $this->makeVerifiedPackage();
        $target = $this->makeTarget();

        $throwingCheck = new class implements PreflightCheckInterface {
            public function id(): string
            {
                return 'exploding_check';
            }

            public function run(VerifiedPackage $package, UpdateTarget $target): array
            {
                throw new RuntimeException('Erro inesperado no check.');
            }
        };

        $composite = new Preflight([
            new PhpVersionCheck('8.2.12'),
            $throwingCheck,
        ]);

        $report = $composite->run($pkg, $target);
        $this->assertFalse($report->canProceed());
        $this->assertCount(1, $report->blocks());
        $this->assertSame('exploding_check', $report->blocks()[0]->id);
    }

    public function testFilesystemInstalledStateReadsRealProjectPlugins(): void
    {
        $root = dirname(__DIR__, 3);
        $state = new FilesystemInstalledState($root);

        $this->assertSame('2.4.0', $state->coreVersion());
        $plugins = $state->plugins();
        $this->assertArrayHasKey('settings', $plugins);
        $this->assertSame('2.4.0', $plugins['settings']->version);
        $this->assertSame('2.4', $plugins['settings']->osVersion);
    }
}
