<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit\Update;

use DomainSystem\Core\Update\Enum\CheckLevel;
use DomainSystem\Core\Update\Enum\UpdateKind;
use DomainSystem\Core\Update\Enum\UpdateStatus;
use DomainSystem\Core\Update\Enum\UpdateStep;
use DomainSystem\Core\Update\Exception\ApplyException;
use DomainSystem\Core\Update\Exception\InvalidManifestException;
use DomainSystem\Core\Update\Exception\PackageIntegrityException;
use DomainSystem\Core\Update\Exception\PreflightFailedException;
use DomainSystem\Core\Update\Exception\RollbackFailedException;
use DomainSystem\Core\Update\Exception\SchemaMigrationException;
use DomainSystem\Core\Update\Exception\SchemaPlanException;
use DomainSystem\Core\Update\Exception\SourceUnavailableException;
use DomainSystem\Core\Update\Exception\TransportException;
use DomainSystem\Core\Update\Exception\UpdateException;
use DomainSystem\Core\Update\Value\ApplyResult;
use DomainSystem\Core\Update\Value\BackupRecord;
use DomainSystem\Core\Update\Value\BackupScope;
use DomainSystem\Core\Update\Value\DownloadedPackage;
use DomainSystem\Core\Update\Value\DumpResult;
use DomainSystem\Core\Update\Value\HealthReport;
use DomainSystem\Core\Update\Value\HttpResponse;
use DomainSystem\Core\Update\Value\InstalledPlugin;
use DomainSystem\Core\Update\Value\PreflightCheck;
use DomainSystem\Core\Update\Value\PreflightReport;
use DomainSystem\Core\Update\Value\ReleaseInfo;
use DomainSystem\Core\Update\Value\SchemaPlan;
use DomainSystem\Core\Update\Value\SchemaResult;
use DomainSystem\Core\Update\Value\SchemaVerification;
use DomainSystem\Core\Update\Value\UpdateCheckResult;
use DomainSystem\Core\Update\Value\UpdateManifest;
use DomainSystem\Core\Update\Value\UpdateState;
use DomainSystem\Core\Update\Value\UpdateTarget;
use DomainSystem\Core\Update\Value\VerifiedPackage;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class ValueObjectsTest extends TestCase
{
    private const VALID_HASH = 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855';

    public function testAllValueObjectsAreFinalReadonlyAndHaveNoSetters(): void
    {
        $classes = [
            UpdateTarget::class,
            ReleaseInfo::class,
            DownloadedPackage::class,
            VerifiedPackage::class,
            UpdateManifest::class,
            UpdateCheckResult::class,
            PreflightCheck::class,
            PreflightReport::class,
            SchemaPlan::class,
            SchemaResult::class,
            SchemaVerification::class,
            BackupScope::class,
            BackupRecord::class,
            DumpResult::class,
            ApplyResult::class,
            HealthReport::class,
            UpdateState::class,
            HttpResponse::class,
            InstalledPlugin::class,
        ];

        foreach ($classes as $class) {
            $ref = new ReflectionClass($class);
            $this->assertTrue($ref->isFinal(), "{$class} deve ser final");
            $this->assertTrue($ref->isReadOnly(), "{$class} deve ser readonly");

            foreach ($ref->getMethods() as $method) {
                $this->assertFalse(
                    str_starts_with($method->getName(), 'set'),
                    "{$class} não deve expor setters ({$method->getName()})"
                );
            }
        }
    }

    public function testAllExceptionsInheritFromUpdateException(): void
    {
        $exceptions = [
            TransportException::class,
            SourceUnavailableException::class,
            InvalidManifestException::class,
            PackageIntegrityException::class,
            PreflightFailedException::class,
            SchemaPlanException::class,
            SchemaMigrationException::class,
            ApplyException::class,
            RollbackFailedException::class,
        ];

        foreach ($exceptions as $exceptionClass) {
            $instance = new $exceptionClass('Falha de teste');
            $this->assertInstanceOf(UpdateException::class, $instance);
            $this->assertSame('Falha de teste', $instance->getMessage());
        }
    }

    public function testUpdateTargetValidation(): void
    {
        $target = new UpdateTarget(
            kind: UpdateKind::Core,
            slug: 'core',
            repository: 'hadelrachid/domain-system',
            installedVersion: '2.3.0',
        );
        $this->assertSame('stable', $target->channel);
        $this->assertSame($target->toArray(), UpdateTarget::fromArray($target->toArray())->toArray());

        $this->expectException(InvalidArgumentException::class);
        new UpdateTarget(UpdateKind::Core, 'core', 'repo-invalido', '2.3.0');
    }

    public function testReleaseInfoValidatesSha256(): void
    {
        $release = new ReleaseInfo(
            version: '2.4.0',
            tag: 'v2.4.0',
            publishedAt: '2026-10-19T12:00:00Z',
            changelog: 'Novidades v2.4.0',
            assetUrl: 'https://github.com/hadelrachid/domain-system/releases/download/v2.4.0/domain-system-2.4.0.zip',
            assetName: 'domain-system-2.4.0.zip',
            sizeBytes: 1024,
            sha256: self::VALID_HASH,
            etag: 'W/"123"',
        );

        $this->assertSame('2.4.0', $release->version);
        $this->assertSame($release->toArray(), ReleaseInfo::fromArray($release->toArray())->toArray());

        $this->expectException(InvalidArgumentException::class);
        new ReleaseInfo(
            version: '2.4.0',
            tag: 'v2.4.0',
            publishedAt: '2026-10-19T12:00:00Z',
            changelog: '',
            assetUrl: 'https://github.com/x.zip',
            assetName: 'x.zip',
            sizeBytes: 10,
            sha256: 'hash-curto',
        );
    }

    public function testUpdateManifestValidatesFilesAndMigrationsWithAmendments(): void
    {
        $manifest = new UpdateManifest(
            protocol: 1,
            minUpdaterProtocol: 1,
            kind: UpdateKind::Core,
            slug: 'core',
            version: '2.4.0',
            requires: [
                'php'        => '8.1',
                'extensions' => ['pdo', 'zip', 'curl'],
                'osVersion'  => '2.3',
            ],
            files: [
                [
                    'path'   => 'src/Core/Application.php',
                    'sha256' => self::VALID_HASH,
                    'size'   => 512,
                    'mode'   => 'replace',
                ],
                [
                    'path'   => 'config/update.json',
                    'sha256' => self::VALID_HASH,
                    'size'   => 128,
                    'mode'   => 'default_if_missing',
                ],
            ],
            removed: ['src/Core/Obsolete.php'],
            migrations: [
                [
                    'scope'  => '__core__',
                    'name'   => '2026_11_01_000001_init',
                    'sha256' => self::VALID_HASH,
                    'type'   => 'additive',
                ],
            ],
            expects: [
                'academy_modules' => ['folder_slug'],
            ],
        );

        $this->assertCount(2, $manifest->files);
        $this->assertSame('default_if_missing', $manifest->files[1]['mode']);
        $this->assertSame('__core__', $manifest->migrations[0]['scope']);

        $this->expectException(InvalidArgumentException::class);
        new UpdateManifest(
            protocol: 1,
            minUpdaterProtocol: 1,
            kind: UpdateKind::Core,
            slug: 'core',
            version: '2.4.0',
            requires: ['php' => '8.1', 'extensions' => ['pdo'], 'osVersion' => '2.3'],
            files: [['path' => 'src/Foo.php', 'sha256' => self::VALID_HASH, 'size' => 10, 'mode' => 'modo_invalido']],
            removed: [],
            migrations: [],
            expects: [],
        );
    }

    public function testPreflightReportCanProceedIsFalseOnlyWhenBlockExists(): void
    {
        $okAndWarn = new PreflightReport([
            new PreflightCheck('php', CheckLevel::Ok, 'PHP 8.2 OK'),
            new PreflightCheck('plugin_compat', CheckLevel::Warn, 'Plugin custom com os_version antigo'),
        ]);
        $this->assertTrue($okAndWarn->canProceed());
        $this->assertCount(1, $okAndWarn->warnings());
        $this->assertCount(0, $okAndWarn->blocks());

        $withBlock = new PreflightReport([
            new PreflightCheck('php', CheckLevel::Ok, 'PHP 8.2 OK'),
            new PreflightCheck('disk', CheckLevel::Block, 'Espaço insuficiente'),
        ]);
        $this->assertFalse($withBlock->canProceed());
        $this->assertCount(1, $withBlock->blocks());
    }

    public function testUpdateStateImmutableTransitions(): void
    {
        $target = new UpdateTarget(UpdateKind::Core, 'core', 'hadelrachid/domain-system', '2.3.0');
        $state0 = UpdateState::idle($target, '2026-10-19T12:00:00Z');

        $this->assertSame(UpdateStep::Idle, $state0->step);
        $this->assertNull($state0->error);

        $state1 = $state0->withStep(UpdateStep::Downloaded, '2026-10-19T12:01:00Z');
        $this->assertNotSame($state0, $state1);
        $this->assertSame(UpdateStep::Idle, $state0->step);
        $this->assertSame(UpdateStep::Downloaded, $state1->step);

        $state2 = $state1->withContext(['backup_id' => 'bkp-01'], '2026-10-19T12:02:00Z');
        $this->assertSame([], $state1->context);
        $this->assertSame(['backup_id' => 'bkp-01'], $state2->context);

        $state3 = $state2->withError('Falha ao migrar', UpdateStep::Failed, '2026-10-19T12:03:00Z');
        $this->assertNull($state2->error);
        $this->assertSame('Falha ao migrar', $state3->error);
        $this->assertSame(UpdateStep::Failed, $state3->step);

        $rehydrated = UpdateState::fromArray($state3->toArray(), $target);
        $this->assertSame($state3->toArray(), $rehydrated->toArray());
    }

    public function testOtherValueObjectsConstructAndValidateProperly(): void
    {
        $target = new UpdateTarget(UpdateKind::Core, 'core', 'hadelrachid/domain-system', '2.3.0');
        $release = new ReleaseInfo(
            version: '2.4.0',
            tag: 'v2.4.0',
            publishedAt: '2026-10-19T12:00:00Z',
            changelog: 'OK',
            assetUrl: 'https://github.com/x.zip',
            assetName: 'x.zip',
            sizeBytes: 100,
            sha256: self::VALID_HASH,
        );

        $downloaded = new DownloadedPackage($release, '/tmp/x.zip', 100);
        $this->assertSame('/tmp/x.zip', $downloaded->archivePath);

        $manifest = new UpdateManifest(
            1,
            1,
            UpdateKind::Core,
            'core',
            '2.4.0',
            ['php' => '8.1', 'extensions' => ['pdo'], 'osVersion' => '2.3'],
            [],
            [],
            [],
            [],
        );
        $verified = new VerifiedPackage($release, $manifest, '/tmp/extracted');
        $this->assertSame('/tmp/extracted', $verified->extractedPath);

        $checkResult = new UpdateCheckResult(
            UpdateStatus::Available,
            $target,
            $release,
            ['Nova versão disponível'],
            '2026-10-19T12:00:00Z',
        );
        $this->assertSame(UpdateStatus::Available, $checkResult->status);

        $plan = new SchemaPlan(
            scope: 'core',
            pending: [['scope' => '__core__', 'name' => '2026_10_19_000001_a', 'sha256' => self::VALID_HASH, 'type' => 'additive']],
            blocked: [],
            destructive: false,
            requiresDatabaseBackup: false,
        );
        $this->assertCount(1, $plan->pending);

        $schemaRes = new SchemaResult(['2026_10_19_000001_a']);
        $this->assertTrue($schemaRes->isOk());

        $verification = new SchemaVerification(true);
        $this->assertTrue($verification->ok);

        $scope = new BackupScope(UpdateKind::Core, 'core');
        $record = new BackupRecord('bkp-1', $scope, 'Pre-update', '2026-10-19T12:00:00Z', '/tmp/code.zip', '/tmp/db.sql');
        $this->assertSame($record->toArray(), BackupRecord::fromArray($record->toArray())->toArray());

        $dump = new DumpResult('/tmp/db.sql', 2048, 5);
        $this->assertSame(5, $dump->tablesCount);

        $apply = new ApplyResult($record, ['src/Core/Foo.php'], ['src/Core/Old.php'], ['.env']);
        $this->assertSame(['.env'], $apply->skippedProtected);

        $health = new HealthReport(true, [['id' => 'version', 'ok' => true, 'message' => 'v2.4.0 ativa']]);
        $this->assertTrue(HealthReport::fromArray($health->toArray())->ok);

        $resp304 = new HttpResponse(304, ['etag' => 'W/"1"'], '', 'W/"1"');
        $this->assertTrue($resp304->isNotModified());

        $plugin = new InstalledPlugin('academy', '1.0.0', '2.3', false, true);
        $this->assertTrue($plugin->isActive);
    }
}
