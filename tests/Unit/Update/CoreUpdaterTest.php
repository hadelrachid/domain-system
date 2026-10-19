<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit\Update;

use DomainSystem\Core\Contracts\EventDispatcherInterface;
use DomainSystem\Core\Contracts\NotificationManagerInterface;
use DomainSystem\Core\Contracts\Update\BackupInterface;
use DomainSystem\Core\Contracts\Update\DatabaseBackupInterface;
use DomainSystem\Core\Contracts\Update\HealthCheckInterface;
use DomainSystem\Core\Contracts\Update\MaintenanceModeInterface;
use DomainSystem\Core\Contracts\Update\PreflightInterface;
use DomainSystem\Core\Contracts\Update\ReleaseSourceInterface;
use DomainSystem\Core\Contracts\Update\SchemaMigratorInterface;
use DomainSystem\Core\Contracts\Update\SchemaPlannerInterface;
use DomainSystem\Core\Contracts\Update\SchemaVerifierInterface;
use DomainSystem\Core\Contracts\Update\UpdateApplierInterface;
use DomainSystem\Core\Contracts\Update\UpdateCheckerInterface;
use DomainSystem\Core\Contracts\Update\UpdateLockInterface;
use DomainSystem\Core\Contracts\Update\UpdateManifestParserInterface;
use DomainSystem\Core\Contracts\Update\UpdatePackageVerifierInterface;
use DomainSystem\Core\Contracts\Update\UpdateStateStoreInterface;
use DomainSystem\Core\Contracts\Update\UpdateTargetResolverInterface;
use DomainSystem\Core\Update\Enum\CheckLevel;
use DomainSystem\Core\Update\Enum\UpdateKind;
use DomainSystem\Core\Update\Enum\UpdateStatus;
use DomainSystem\Core\Update\Enum\UpdateStep;
use DomainSystem\Core\Update\Exception\RollbackFailedException;
use DomainSystem\Core\Update\Exception\UpdateException;
use DomainSystem\Core\Update\Infrastructure\FileUpdateLock;
use DomainSystem\Core\Update\Infrastructure\JsonUpdateStateStore;
use DomainSystem\Core\Update\Infrastructure\ZipPackageExtractor;
use DomainSystem\Core\Update\Service\CoreUpdater;
use DomainSystem\Core\Update\Service\JsonUpdateManifestParser;
use DomainSystem\Core\Update\Value\ApplyResult;
use DomainSystem\Core\Update\Value\BackupRecord;
use DomainSystem\Core\Update\Value\BackupScope;
use DomainSystem\Core\Update\Value\DownloadedPackage;
use DomainSystem\Core\Update\Value\DumpResult;
use DomainSystem\Core\Update\Value\HealthReport;
use DomainSystem\Core\Update\Value\PreflightCheck;
use DomainSystem\Core\Update\Value\PreflightReport;
use DomainSystem\Core\Update\Value\ReleaseInfo;
use DomainSystem\Core\Update\Value\SchemaPlan;
use DomainSystem\Core\Update\Value\SchemaResult;
use DomainSystem\Core\Update\Value\SchemaVerification;
use DomainSystem\Core\Update\Value\UpdateCheckResult;
use DomainSystem\Core\Update\Value\UpdateManifest;
use DomainSystem\Core\Update\Value\UpdateTarget;
use DomainSystem\Core\Update\Value\VerifiedPackage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CoreUpdaterTest extends TestCase
{
    private string $tempDir;
    private UpdateTarget $target;
    private ReleaseInfo $release;
    private UpdateManifest $manifest;

    /** @var list<string> */
    public array $callLog = [];

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ds-core-updater-test-' . bin2hex(random_bytes(6));
        mkdir($this->tempDir, 0755, true);
        $this->callLog = [];

        $this->target = new UpdateTarget(
            kind: UpdateKind::Core,
            slug: 'core',
            repository: 'hadelrachid/domain-system',
            installedVersion: '2.3.0',
            channel: 'stable',
        );

        $this->release = new ReleaseInfo(
            version: '2.4.0',
            tag: 'v2.4.0',
            publishedAt: '2026-10-19T12:00:00Z',
            changelog: 'Release 2.4.0',
            assetUrl: 'https://github.com/hadelrachid/domain-system/releases/download/v2.4.0/domain-system-2.4.0.zip',
            assetName: 'domain-system-2.4.0.zip',
            sizeBytes: 1024,
            sha256: str_repeat('a', 64),
            etag: '"etag-2.4.0"',
        );

        $this->manifest = new UpdateManifest(
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
            files: [
                ['path' => 'src/Core/Foo.php', 'sha256' => str_repeat('b', 64), 'size' => 20, 'mode' => 'replace'],
            ],
            removed: [],
            migrations: [
                ['scope' => '__core__', 'name' => '2026_11_01_000001_add', 'sha256' => str_repeat('c', 64), 'type' => 'additive'],
            ],
            expects: ['users' => ['id']],
        );

        file_put_contents($this->tempDir . '/manifest.json', json_encode([
            'protocol' => 1,
            'minUpdaterProtocol' => 1,
            'kind' => 'core',
            'slug' => 'core',
            'version' => '2.4.0',
            'requires' => ['php' => '8.1', 'extensions' => ['pdo'], 'osVersion' => '2.3'],
            'files' => [['path' => 'src/Core/Foo.php', 'sha256' => str_repeat('b', 64), 'size' => 20, 'mode' => 'replace']],
            'removed' => [],
            'migrations' => [['scope' => '__core__', 'name' => '2026_11_01_000001_add', 'sha256' => str_repeat('c', 64), 'type' => 'additive']],
            'expects' => ['users' => ['id']],
        ]));
    }

    protected function tearDown(): void
    {
        ZipPackageExtractor::removeDirectory($this->tempDir);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array{updater: CoreUpdater, stateStore: UpdateStateStoreInterface, lock: UpdateLockInterface, maintenance: MaintenanceModeInterface, events: list<string>}
     */
    private function buildHarness(array $overrides = []): array
    {
        $failAtStep = $overrides['failAtStep'] ?? null;
        $autoRollback = $overrides['autoRollback'] ?? true;
        $rollbackFails = $overrides['rollbackFails'] ?? false;
        $dbSupported = $overrides['dbSupported'] ?? true;
        $destructivePlan = $overrides['destructivePlan'] ?? false;
        $preflightBlocks = $overrides['preflightBlocks'] ?? false;
        $initialMaintenance = $overrides['initialMaintenance'] ?? false;

        $stateStore = $overrides['stateStore'] ?? new JsonUpdateStateStore($this->tempDir);
        $lock = $overrides['lock'] ?? new FileUpdateLock($this->tempDir);
        $dispatchedEvents = [];

        $targetResolver = new class ($this->target) implements UpdateTargetResolverInterface {
            public function __construct(private UpdateTarget $t) {}
            public function core(): UpdateTarget { return $this->t; }
            public function plugins(): array { return []; }
        };

        $checker = new class ($this->target, $this->release) implements UpdateCheckerInterface {
            public function __construct(private UpdateTarget $t, private ReleaseInfo $r) {}
            public function check(UpdateTarget $target, bool $force = false): UpdateCheckResult
            {
                return new UpdateCheckResult(UpdateStatus::Available, $this->t, $this->r, [], '2026-10-19T12:00:00Z');
            }
        };

        $releaseSource = new class ($this, $this->release, $this->tempDir, $failAtStep) implements ReleaseSourceInterface {
            public function __construct(
                private CoreUpdaterTest $test,
                private ReleaseInfo $r,
                private string $dir,
                private ?string $failAt,
            ) {}
            public function latest(UpdateTarget $target, ?string $knownEtag = null): ?ReleaseInfo { return $this->r; }
            public function fetchPackage(ReleaseInfo $release, string $destinationDir): DownloadedPackage
            {
                $this->test->callLog[] = 'download';
                if ($this->failAt === 'download') {
                    throw new RuntimeException('Falha simulada no download.');
                }
                return new DownloadedPackage($release, $this->dir . '/pkg.zip', 1024);
            }
        };

        $verifier = new class ($this, $this->release, $this->manifest, $this->tempDir, $failAtStep) implements UpdatePackageVerifierInterface {
            public function __construct(
                private CoreUpdaterTest $test,
                private ReleaseInfo $r,
                private UpdateManifest $m,
                private string $dir,
                private ?string $failAt,
            ) {}
            public function verify(DownloadedPackage $package): VerifiedPackage
            {
                $this->test->callLog[] = 'verify';
                if ($this->failAt === 'verify') {
                    throw new RuntimeException('Falha simulada na verificação.');
                }
                return new VerifiedPackage($this->r, $this->m, $this->dir);
            }
        };

        $preflight = new class ($this, $failAtStep, $preflightBlocks) implements PreflightInterface {
            public function __construct(
                private CoreUpdaterTest $test,
                private ?string $failAt,
                private bool $blocks,
            ) {}
            public function run(VerifiedPackage $package, UpdateTarget $target): PreflightReport
            {
                $this->test->callLog[] = 'preflight';
                if ($this->failAt === 'preflight' || $this->blocks) {
                    return new PreflightReport([
                        new PreflightCheck('downgrade', CheckLevel::Block, 'Bloqueado no preflight.'),
                    ]);
                }
                return new PreflightReport([
                    new PreflightCheck('ok', CheckLevel::Ok, 'Tudo certo.'),
                ]);
            }
        };

        $schemaPlanner = new class ($this, $destructivePlan) implements SchemaPlannerInterface {
            public function __construct(private CoreUpdaterTest $test, private bool $destructive) {}
            public function plan(UpdateManifest $manifest, string $extractedPath): SchemaPlan
            {
                $this->test->callLog[] = 'schema_plan';
                return new SchemaPlan(
                    scope: '__core__',
                    pending: [['scope' => '__core__', 'name' => '2026_11_01_000001_add', 'sha256' => str_repeat('c', 64), 'type' => $this->destructive ? 'destructive' : 'additive']],
                    blocked: [],
                    destructive: $this->destructive,
                    requiresDatabaseBackup: $this->destructive,
                );
            }
        };

        $backup = new class ($this, $this->tempDir, $failAtStep) implements BackupInterface {
            public function __construct(private CoreUpdaterTest $test, private string $dir, private ?string $failAt) {}
            public function create(BackupScope $scope, string $label): BackupRecord
            {
                $this->test->callLog[] = 'backup_code';
                if ($this->failAt === 'backup') {
                    throw new RuntimeException('Falha simulada no backup.');
                }
                return new BackupRecord('bkp-1', $scope, $label, '2026-10-19T12:00:00Z', $this->dir . '/code.zip');
            }
            public function restore(BackupRecord $record): void
            {
                $this->test->callLog[] = 'restore_code';
            }
            public function list(?BackupScope $scope = null): array { return []; }
            public function prune(BackupScope $scope, int $keep): int { return 0; }
        };

        $dbBackup = new class ($this, $this->tempDir, $dbSupported, $rollbackFails) implements DatabaseBackupInterface {
            public function __construct(
                private CoreUpdaterTest $test,
                private string $dir,
                private bool $supported,
                private bool $rbFails,
            ) {}
            public function isSupported(): bool { return $this->supported; }
            public function dump(string $destinationFile): DumpResult
            {
                $this->test->callLog[] = 'backup_db';
                return new DumpResult($this->dir . '/db.dump', 512, 3);
            }
            public function restore(string $dumpFile): void
            {
                $this->test->callLog[] = 'restore_db';
                if ($this->rbFails) {
                    throw new RollbackFailedException('Falha ao restaurar dump do banco.');
                }
            }
        };

        $maintenance = new class ($this, $initialMaintenance, $failAtStep) implements MaintenanceModeInterface {
            private bool $enabled;
            public function __construct(private CoreUpdaterTest $test, bool $initial, private ?string $failAt)
            {
                $this->enabled = $initial;
            }
            public function enable(string $message = 'maintenance', ?string $adminBypassToken = null): void
            {
                $this->test->callLog[] = 'maintenance_on';
                if ($this->failAt === 'maintenance') {
                    throw new RuntimeException('Falha simulada ao ativar manutenção.');
                }
                $this->enabled = true;
            }
            public function disable(): void
            {
                $this->test->callLog[] = 'maintenance_off';
                $this->enabled = false;
            }
            public function isEnabled(): bool { return $this->enabled; }
        };

        $applier = new class ($this, $failAtStep, $rollbackFails) implements UpdateApplierInterface {
            public function __construct(private CoreUpdaterTest $test, private ?string $failAt, private bool $rbFails) {}
            public function apply(VerifiedPackage $package, UpdateTarget $target, BackupRecord $backup): ApplyResult
            {
                $this->test->callLog[] = 'apply';
                if ($this->failAt === 'apply') {
                    throw new RuntimeException('Falha simulada na aplicação de arquivos.');
                }
                return new ApplyResult($backup, ['src/Core/Foo.php'], [], []);
            }
            public function revert(ApplyResult $result): void
            {
                $this->test->callLog[] = 'revert_code';
                if ($this->rbFails) {
                    throw new RollbackFailedException('Falha crítica ao reverter arquivos.');
                }
            }
        };

        $migrator = new class ($this, $failAtStep) implements SchemaMigratorInterface {
            public function __construct(private CoreUpdaterTest $test, private ?string $failAt) {}
            public function migrate(SchemaPlan $plan): SchemaResult
            {
                $this->test->callLog[] = 'migrate';
                if ($this->failAt === 'migrate') {
                    return new SchemaResult([], '2026_11_01_000001_add', 'Erro SQL na migration.');
                }
                return new SchemaResult(['2026_11_01_000001_add']);
            }
        };

        $schemaVerifier = new class ($this, $failAtStep) implements SchemaVerifierInterface {
            public function __construct(private CoreUpdaterTest $test, private ?string $failAt) {}
            public function verify(UpdateManifest $manifest): SchemaVerification
            {
                $this->test->callLog[] = 'verify_schema';
                if ($this->failAt === 'verify_schema') {
                    return new SchemaVerification(false, [], ['users.id'], []);
                }
                return new SchemaVerification(true);
            }
        };

        $healthCheck = new class ($this, $failAtStep) implements HealthCheckInterface {
            public function __construct(private CoreUpdaterTest $test, private ?string $failAt) {}
            public function run(UpdateTarget $target): HealthReport
            {
                $this->test->callLog[] = 'health_check';
                if ($this->failAt === 'health') {
                    return new HealthReport(false, [new PreflightCheck('core', CheckLevel::Block, 'Falha de saúde.')]);
                }
                return new HealthReport(true, [new PreflightCheck('core', CheckLevel::Ok, 'Saudável.')]);
            }
        };

        $events = new class ($dispatchedEvents) implements EventDispatcherInterface {
            /** @var list<string> */
            public array $names = [];
            public function __construct(array &$ref) { $this->names = &$ref; }
            public function addListener(string $eventName, callable $listener, int $priority = 10): void {}
            public function removeListener(string $eventName, callable $listener): void {}
            public function dispatch(string $eventName, ...$args): void { $this->names[] = $eventName; }
            public function applyFilters(string $filterName, mixed $value, ...$args): mixed { return $value; }
        };

        $notifications = new class implements NotificationManagerInterface {
            public function push(string $message, string $type = 'info', ?string $source = null, array $context = []): void {}
            public function getUnread(): array { return []; }
            public function markAsRead(): void {}
            public function getAll(): array { return []; }
            public function clear(): void {}
        };

        $updater = new CoreUpdater(
            targetResolver: $targetResolver,
            checker: $checker,
            releaseSource: $releaseSource,
            manifestParser: new JsonUpdateManifestParser(),
            packageVerifier: $verifier,
            preflight: $preflight,
            schemaPlanner: $schemaPlanner,
            backup: $backup,
            databaseBackup: $dbBackup,
            maintenanceMode: $maintenance,
            applier: $applier,
            schemaMigrator: $migrator,
            schemaVerifier: $schemaVerifier,
            healthCheck: $healthCheck,
            stateStore: $stateStore,
            lock: $lock,
            tempUpdatesDir: $this->tempDir . '/updates',
            autoRollback: $autoRollback,
            events: $events,
            notifications: $notifications,
        );

        return [
            'updater'     => $updater,
            'stateStore'  => $stateStore,
            'lock'        => $lock,
            'maintenance' => $maintenance,
            'events'      => &$dispatchedEvents,
        ];
    }

    public function testHappyPathExecutesExactStepSequenceAndTwoPhaseHealthCheck(): void
    {
        $h = $this->buildHarness();
        $updater = $h['updater'];
        $stateStore = $h['stateStore'];

        $state = $updater->begin($this->target);
        $this->assertSame(UpdateStep::Checked, $state->step);
        $id = $state->updateId;

        $this->assertSame(UpdateStep::Downloaded, $updater->advance($id)->step);
        $this->assertSame(UpdateStep::Verified, $updater->advance($id)->step);
        $this->assertSame(UpdateStep::Preflighted, $updater->advance($id)->step);
        $this->assertSame(UpdateStep::BackedUp, $updater->advance($id)->step);
        $this->assertSame(UpdateStep::MaintenanceOn, $updater->advance($id)->step);
        $this->assertTrue($h['maintenance']->isEnabled());

        $this->assertSame(UpdateStep::Applied, $updater->advance($id)->step);
        $this->assertSame(UpdateStep::Migrated, $updater->advance($id)->step);
        $this->assertSame(UpdateStep::SchemaVerified, $updater->advance($id)->step);

        // Sem health_report no estado, advance() devolve SchemaVerified com awaiting = health
        $awaitingState = $updater->advance($id);
        $this->assertSame(UpdateStep::SchemaVerified, $awaitingState->step);
        $this->assertSame('health', $awaitingState->context['awaiting'] ?? null);

        // Simula requisição GET /admin/settings/updates/core/health gravando o relatório
        $withHealth = $awaitingState->withContext([
            'health_report' => (new HealthReport(true, [new PreflightCheck('core', CheckLevel::Ok, 'OK')]))->toArray(),
        ]);
        $stateStore->save($withHealth);

        $this->assertSame(UpdateStep::HealthChecked, $updater->advance($id)->step);
        $doneState = $updater->advance($id);
        $this->assertSame(UpdateStep::Done, $doneState->step);
        $this->assertFalse($h['maintenance']->isEnabled());

        $this->assertSame([
            'download',
            'verify',
            'preflight',
            'schema_plan',
            'backup_code',
            'backup_db',
            'maintenance_on',
            'apply',
            'migrate',
            'verify_schema',
            'maintenance_off',
        ], $this->callLog);

        $this->assertContains('update.started', $h['events']);
        $this->assertContains('update.step', $h['events']);
        $this->assertContains('update.completed', $h['events']);
    }

    /**
     * @param list<string> $expectedRollbackCalls
     */
    #[DataProvider('failureStepsProvider')]
    public function testFailureInjectedAtEachStepProducesExpectedFinalStateAndRollbackCalls(
        string $failAtStep,
        UpdateStep $expectedFinalStep,
        array $expectedRollbackCalls,
    ): void {
        $h = $this->buildHarness(['failAtStep' => $failAtStep]);
        $updater = $h['updater'];

        $state = $updater->begin($this->target, ['inlineHealthCheck' => true]);
        $id = $state->updateId;

        for ($i = 0; $i < 12; $i++) {
            $state = $updater->advance($id);
            if (in_array($state->step, [UpdateStep::Done, UpdateStep::Failed, UpdateStep::RolledBack], true)) {
                break;
            }
        }

        $this->assertSame($expectedFinalStep, $state->step);
        foreach ($expectedRollbackCalls as $rbCall) {
            $this->assertContains($rbCall, $this->callLog);
        }
        if ($expectedRollbackCalls === []) {
            $this->assertNotContains('revert_code', $this->callLog);
            $this->assertNotContains('restore_db', $this->callLog);
        }
    }

    /**
     * @return array<string, array{0: string, 1: UpdateStep, 2: list<string>}>
     */
    public static function failureStepsProvider(): array
    {
        return [
            'fail_at_download'      => ['download', UpdateStep::Failed, []],
            'fail_at_verify'        => ['verify', UpdateStep::Failed, []],
            'fail_at_preflight'     => ['preflight', UpdateStep::Failed, []],
            'fail_at_backup'        => ['backup', UpdateStep::Failed, []],
            'fail_at_maintenance'   => ['maintenance', UpdateStep::RolledBack, ['revert_code']],
            'fail_at_apply'         => ['apply', UpdateStep::RolledBack, ['revert_code', 'maintenance_off']],
            'fail_at_migrate'       => ['migrate', UpdateStep::RolledBack, ['revert_code', 'restore_db', 'maintenance_off']],
            'fail_at_verify_schema' => ['verify_schema', UpdateStep::RolledBack, ['revert_code', 'restore_db', 'maintenance_off']],
            'fail_at_health'        => ['health', UpdateStep::RolledBack, ['revert_code', 'restore_db', 'maintenance_off']],
        ];
    }

    public function testAutoRollbackFalseEndsInFailedWithoutReverting(): void
    {
        $h = $this->buildHarness([
            'failAtStep'   => 'apply',
            'autoRollback' => false,
        ]);
        $updater = $h['updater'];

        $state = $updater->begin($this->target);
        $id = $state->updateId;

        while (!in_array($state->step, [UpdateStep::Done, UpdateStep::Failed, UpdateStep::RolledBack], true)) {
            $state = $updater->advance($id);
        }

        $this->assertSame(UpdateStep::Failed, $state->step);
        $this->assertNotContains('revert_code', $this->callLog);
        $this->assertFalse($h['maintenance']->isEnabled());
    }

    public function testRollbackFailedExceptionKeepsMaintenanceOnAndTransitionsToFailed(): void
    {
        $h = $this->buildHarness([
            'failAtStep'    => 'migrate',
            'rollbackFails' => true,
        ]);
        $updater = $h['updater'];

        $state = $updater->begin($this->target);
        $id = $state->updateId;

        while (!in_array($state->step, [UpdateStep::Done, UpdateStep::Failed, UpdateStep::RolledBack], true)) {
            $state = $updater->advance($id);
        }

        $this->assertSame(UpdateStep::Failed, $state->step);
        $this->assertTrue($h['maintenance']->isEnabled(), 'Manutenção deve permanecer ativa se o rollback falhar.');
        $this->assertStringContainsString('php os-cli.php system:rollback', (string)$state->error);
    }

    public function testConcurrentBeginIsBlockedByLock(): void
    {
        $h = $this->buildHarness();
        $updater = $h['updater'];

        $updater->begin($this->target);

        $this->expectException(UpdateException::class);
        $this->expectExceptionMessage('lock ativo');
        $updater->begin($this->target);
    }

    public function testInterruptedSafeStepRepeatsWhileInterruptedAppliedRollsBack(): void
    {
        $h = $this->buildHarness();
        $updater = $h['updater'];
        $stateStore = $h['stateStore'];

        $state = $updater->begin($this->target);
        $id = $state->updateId;

        // 1. Simula queda durante Downloaded (etapa segura): deve repetir e avançar para Downloaded
        $stateStore->save($state->withContext(['in_progress_step' => UpdateStep::Downloaded->value]));
        $resumed = $updater->advance($id);
        $this->assertSame(UpdateStep::Downloaded, $resumed->step);

        // Avança até MaintenanceOn (com backup criado)
        $updater->advance($id); // Verified
        $updater->advance($id); // Preflighted
        $updater->advance($id); // BackedUp
        $maintState = $updater->advance($id); // MaintenanceOn

        // 2. Simula queda durante Applied (etapa crítica pós-backup): deve tratar como falha e fazer rollback
        $stateStore->save($maintState->withContext(['in_progress_step' => UpdateStep::Applied->value]));
        $afterCrash = $updater->advance($id);
        $this->assertSame(UpdateStep::RolledBack, $afterCrash->step);
        $this->assertContains('revert_code', $this->callLog);
    }

    public function testUnsupportedDbBackupOrDestructiveMigrationRequiresExplicitConfirmation(): void
    {
        $h = $this->buildHarness(['dbSupported' => false]);
        $updater = $h['updater'];

        // Sem confirmNoDbBackup -> falha na etapa Preflighted
        $state = $updater->begin($this->target);
        $id = $state->updateId;
        $updater->advance($id); // Downloaded
        $updater->advance($id); // Verified
        $blockedState = $updater->advance($id); // Preflighted falha
        $this->assertSame(UpdateStep::Failed, $blockedState->step);
        $this->assertStringContainsString('confirmNoDbBackup', (string)$blockedState->error);

        // Com confirmNoDbBackup => true -> passa pela etapa Preflighted
        $state2 = $updater->begin($this->target, ['confirmNoDbBackup' => true]);
        $id2 = $state2->updateId;
        $updater->advance($id2); // Downloaded
        $updater->advance($id2); // Verified
        $passedState = $updater->advance($id2); // Preflighted
        $this->assertSame(UpdateStep::Preflighted, $passedState->step);
    }

    public function testPreexistingManualMaintenanceModeIsNotDisabledOnCompletion(): void
    {
        $h = $this->buildHarness(['initialMaintenance' => true]);
        $updater = $h['updater'];

        $state = $updater->begin($this->target, ['inlineHealthCheck' => true]);
        $id = $state->updateId;

        while (!in_array($state->step, [UpdateStep::Done, UpdateStep::Failed, UpdateStep::RolledBack], true)) {
            $state = $updater->advance($id);
        }

        $this->assertSame(UpdateStep::Done, $state->step);
        $this->assertTrue($h['maintenance']->isEnabled(), 'Manutenção manual preexistente não deve ser desligada.');
        $this->assertNotContains('maintenance_off', $this->callLog);
    }
}
