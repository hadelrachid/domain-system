<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit\Update;

use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Contracts\ThemeManagerInterface;
use DomainSystem\Core\Contracts\Update\BackupInterface;
use DomainSystem\Core\Contracts\Update\CoreUpdaterInterface;
use DomainSystem\Core\Contracts\Update\HealthCheckInterface;
use DomainSystem\Core\Contracts\Update\UpdateCheckerInterface;
use DomainSystem\Core\Contracts\Update\UpdateTargetResolverInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Update\Enum\CheckLevel;
use DomainSystem\Core\Update\Enum\UpdateKind;
use DomainSystem\Core\Update\Enum\UpdateStatus;
use DomainSystem\Core\Update\Enum\UpdateStep;
use DomainSystem\Core\Update\Infrastructure\JsonUpdateStateStore;
use DomainSystem\Core\Update\Value\BackupRecord;
use DomainSystem\Core\Update\Value\BackupScope;
use DomainSystem\Core\Update\Value\HealthReport;
use DomainSystem\Core\Update\Value\PreflightItem;
use DomainSystem\Core\Update\Value\ReleaseInfo;
use DomainSystem\Core\Update\Value\UpdateCheckResult;
use DomainSystem\Core\Update\Value\UpdateState;
use DomainSystem\Core\Update\Value\UpdateTarget;
use DomainSystem\SystemApps\SystemUpdater\Console\UpdateCliCommandHandler;
use DomainSystem\SystemApps\SystemUpdater\Controllers\UpdateController;
use PHPUnit\Framework\TestCase;

final class SystemUpdaterM2Test extends TestCase
{
    private const VALID_HASH = 'a948904f2f0f479b8f8197694b30184b0d2ed1c1cd2a1ec0fb85d299a192a447';

    private string $tempDir;
    private UpdateTarget $coreTarget;
    private ReleaseInfo $release240;

    protected function setUp(): void
    {
        $this->tempDir = str_replace('\\', '/', sys_get_temp_dir()) . '/ds_m2_updater_' . uniqid('', true);
        mkdir($this->tempDir, 0777, true);
        $this->coreTarget = new UpdateTarget(
            kind: UpdateKind::Core,
            slug: 'core',
            repository: 'hadelrachid/domain-system',
            installedVersion: '2.3.0',
        );
        $this->release240 = new ReleaseInfo(
            version: '2.4.0',
            tag: 'v2.4.0',
            publishedAt: '2026-10-19T12:00:00Z',
            changelog: 'Melhorias M2',
            assetUrl: 'https://github.com/hadelrachid/domain-system/releases/download/v2.4.0/domain-system-2.4.0.zip',
            assetName: 'domain-system-2.4.0.zip',
            sizeBytes: 1024,
            sha256: self::VALID_HASH,
        );
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testControllerM2EndpointsEnforceAdminAndCsrfAndReturnJsonStates(): void
    {
        $stateStore = new JsonUpdateStateStore($this->tempDir);
        $resolver = new class ($this->coreTarget) implements UpdateTargetResolverInterface {
            public function __construct(private readonly UpdateTarget $target) {}
            public function core(): UpdateTarget { return $this->target; }
        };

        $checkResult = new UpdateCheckResult(
            status: UpdateStatus::Available,
            target: $this->coreTarget,
            release: $this->release240,
            reasons: [],
            checkedAt: '2026-10-19T12:00:00Z',
        );

        $checker = new class ($checkResult) implements UpdateCheckerInterface {
            public function __construct(private readonly UpdateCheckResult $res) {}
            public function check(UpdateTarget $target, bool $force = false): UpdateCheckResult { return $this->res; }
        };

        $fakeUpdater = $this->createFakeCoreUpdater($stateStore, $checkResult);
        $fakeBackup = $this->createFakeBackup();

        $theme = new class implements ThemeManagerInterface {
            public function setActiveThemePath(string $path): void {}
            public function getActiveThemePath(): string { return ''; }
            public function get_header(array $args = []): string { return ''; }
            public function get_footer(array $args = []): string { return ''; }
            public function getAvailableThemes(): array { return []; }
            public function getAvailableTemplates(?string $themeName = null): array { return []; }
            public function getAvailablePages(?string $themeName = null): array { return []; }
            public function render(string $template, array $args = [], ?string $pluginViewsDir = null): string
            {
                $viewFile = ($pluginViewsDir ?? '') . '/' . $template . '.php';
                extract($args, EXTR_SKIP);
                ob_start();
                include $viewFile;
                return (string)ob_get_clean();
            }
        };

        $adminController = new UpdateController(
            theme: $theme,
            session: $this->createFakeSession('admin', 'csrf-m2'),
            checker: $checker,
            targetResolver: $resolver,
            stateStore: $stateStore,
            coreUpdater: $fakeUpdater,
            backup: $fakeBackup,
        );

        // 1. View M2 renderiza botão de atualização, painel de progresso e backups
        $indexRes = $adminController->index(new Request());
        $this->assertSame(200, $indexRes->getStatusCode());
        $html = $indexRes->getContent();
        $this->assertStringContainsString('Atualizar para v2.4.0', $html);
        $this->assertStringContainsString('ds-update-progress-panel', $html);
        $this->assertStringContainsString('bkp-20261019-01', $html);
        $this->assertStringContainsString('Remover antigos', $html);

        // 2. CSRF inválido recusado em begin, advance, rollback e pruneBackups
        $this->assertSame(403, $adminController->begin(new Request([], ['csrf_token' => 'bad']))->getStatusCode());
        $this->assertSame(403, $adminController->advance(new Request([], ['csrf_token' => 'bad', 'update_id' => 'u1']))->getStatusCode());
        $this->assertSame(403, $adminController->rollback(new Request([], ['csrf_token' => 'bad']))->getStatusCode());
        $this->assertSame(403, $adminController->pruneBackups(new Request([], ['csrf_token' => 'bad']))->getStatusCode());

        // 3. Papel não-admin recusado em todas as rotas M2
        $guestController = new UpdateController(
            theme: $theme,
            session: $this->createFakeSession('subscriber', 'csrf-m2'),
            checker: $checker,
            targetResolver: $resolver,
            stateStore: $stateStore,
            coreUpdater: $fakeUpdater,
            backup: $fakeBackup,
        );
        $this->assertSame(403, $guestController->begin(new Request([], ['csrf_token' => 'csrf-m2']))->getStatusCode());
        $this->assertSame(403, $guestController->advance(new Request([], ['csrf_token' => 'csrf-m2', 'update_id' => 'u1']))->getStatusCode());
        $this->assertSame(403, $guestController->rollback(new Request([], ['csrf_token' => 'csrf-m2']))->getStatusCode());
        $this->assertSame(403, $guestController->pruneBackups(new Request([], ['csrf_token' => 'csrf-m2']))->getStatusCode());

        // 4. Fluxo JSON válido: begin -> advance -> rollback -> pruneBackups
        $beginRes = $adminController->begin(new Request([], [
            'csrf_token'           => 'csrf-m2',
            'confirm_no_db_backup' => '1',
        ]));
        $this->assertSame(200, $beginRes->getStatusCode());
        $beginPayload = json_decode($beginRes->getContent(), true);
        $this->assertTrue($beginPayload['ok']);
        $this->assertSame('checked', $beginPayload['state']['step']);
        $this->assertTrue($fakeUpdater->lastConfirmNoDbBackup);

        $updateId = (string)$beginPayload['state']['updateId'];
        $advRes = $adminController->advance(new Request([], [
            'csrf_token' => 'csrf-m2',
            'update_id'  => $updateId,
        ]));
        $this->assertSame(200, $advRes->getStatusCode());
        $advPayload = json_decode($advRes->getContent(), true);
        $this->assertTrue($advPayload['ok']);
        $this->assertSame('downloaded', $advPayload['state']['step']);

        // Rollback de backup específico
        $rbSpecific = $adminController->rollback(new Request([], [
            'csrf_token' => 'csrf-m2',
            'backup_id'  => 'bkp-20261019-01',
        ]));
        $this->assertSame(200, $rbSpecific->getStatusCode());
        $this->assertSame('bkp-20261019-01', $fakeBackup->restoredId);

        // Prune de backups antigos
        $pruneRes = $adminController->pruneBackups(new Request([], ['csrf_token' => 'csrf-m2']));
        $this->assertSame(200, $pruneRes->getStatusCode());
        $prunePayload = json_decode($pruneRes->getContent(), true);
        $this->assertTrue($prunePayload['ok']);
        $this->assertSame(1, $prunePayload['removed']);
    }

    public function testCliCommandHandlerExecutesCheckStatusUpdateRollbackAndHealthEndToEnd(): void
    {
        $stateStore = new JsonUpdateStateStore($this->tempDir);
        $resolver = new class ($this->coreTarget) implements UpdateTargetResolverInterface {
            public function __construct(private readonly UpdateTarget $target) {}
            public function core(): UpdateTarget { return $this->target; }
        };

        $checkResult = new UpdateCheckResult(
            status: UpdateStatus::Available,
            target: $this->coreTarget,
            release: $this->release240,
            reasons: ['Nova versão 2.4.0 encontrada.'],
            checkedAt: '2026-10-19T12:00:00Z',
        );

        $fakeUpdater = $this->createFakeCoreUpdater($stateStore, $checkResult);
        $fakeBackup = $this->createFakeBackup();

        $healthCheck = new class implements HealthCheckInterface {
            public int $runs = 0;
            public function run(UpdateTarget $target): HealthReport
            {
                $this->runs++;
                return new HealthReport(true, [
                    ['id' => 'ds_version', 'ok' => true, 'message' => 'Versão carregada v2.4.0.'],
                ]);
            }
        };

        $cli = new UpdateCliCommandHandler(
            updater: $fakeUpdater,
            targetResolver: $resolver,
            stateStore: $stateStore,
            healthCheck: $healthCheck,
            backup: $fakeBackup,
        );

        // 1. system:check
        $checkOut = $cli->execute('system:check');
        $this->assertSame(0, $checkOut['exitCode']);
        $this->assertStringContainsString('v2.4.0', $checkOut['output']);

        // 2. system:update sem --yes deve recusar com instrução
        $noConfirmOut = $cli->execute('system:update', []);
        $this->assertSame(1, $noConfirmOut['exitCode']);
        $this->assertStringContainsString('--yes', $noConfirmOut['output']);

        // 3. system:update --yes --confirm-no-db-backup percorre todas as etapas, inclusive awaiting=health, até Done
        $updateOut = $cli->execute('system:update', ['--yes', '--confirm-no-db-backup']);
        $this->assertSame(0, $updateOut['exitCode']);
        $this->assertTrue($fakeUpdater->lastConfirmNoDbBackup);
        $this->assertSame(1, $healthCheck->runs);
        $this->assertStringContainsString('Atualização concluída com sucesso para v2.4.0', $updateOut['output']);

        // 4. system:status após Done
        $statusOut = $cli->execute('system:status');
        $this->assertSame(0, $statusOut['exitCode']);
        $this->assertStringContainsString('done', $statusOut['output']);

        // 5. system:health
        $healthOut = $cli->execute('system:health');
        $this->assertSame(0, $healthOut['exitCode']);
        $this->assertStringContainsString('Saúde do sistema: OK', $healthOut['output']);

        // 6. system:rollback com ID de backup específico
        $rbBackupOut = $cli->execute('system:rollback', ['bkp-20261019-02']);
        $this->assertSame(0, $rbBackupOut['exitCode']);
        $this->assertSame('bkp-20261019-02', $fakeBackup->restoredId);

        // 7. system:rollback da atualização ativa
        $rbUpdateOut = $cli->execute('system:rollback');
        $this->assertSame(0, $rbUpdateOut['exitCode']);
        $this->assertStringContainsString('Rollback concluído', $rbUpdateOut['output']);
    }

    private function createFakeCoreUpdater(JsonUpdateStateStore $stateStore, UpdateCheckResult $checkResult): CoreUpdaterInterface
    {
        return new class ($stateStore, $checkResult, $this->coreTarget, $this->release240) implements CoreUpdaterInterface {
            public bool $lastConfirmNoDbBackup = false;

            /** @var list<UpdateStep> */
            private array $sequence = [
                UpdateStep::Downloaded,
                UpdateStep::Verified,
                UpdateStep::Preflighted,
                UpdateStep::BackedUp,
                UpdateStep::MaintenanceOn,
                UpdateStep::Applied,
                UpdateStep::Migrated,
                UpdateStep::SchemaVerified,
                UpdateStep::HealthChecked,
                UpdateStep::Done,
            ];
            private int $cursor = 0;

            public function __construct(
                private readonly JsonUpdateStateStore $store,
                private readonly UpdateCheckResult $checkResult,
                private readonly UpdateTarget $target,
                private readonly ReleaseInfo $release,
            ) {
            }

            public function kind(): UpdateKind
            {
                return UpdateKind::Core;
            }

            public function check(UpdateTarget $target, bool $force = false): UpdateCheckResult
            {
                return $this->checkResult;
            }

            public function begin(UpdateTarget $target, array $options = []): UpdateState
            {
                $this->lastConfirmNoDbBackup = !empty($options['confirmNoDbBackup']);
                $this->cursor = 0;
                $state = new UpdateState(
                    updateId: 'upd-m2-test',
                    target: $target,
                    step: UpdateStep::Checked,
                    release: $this->release,
                    etag: null,
                    lastCheckAt: '2026-10-19T12:00:00Z',
                    context: [],
                    error: null,
                    updatedAt: '2026-10-19T12:00:00Z',
                );
                $this->store->save($state);
                return $state;
            }

            public function advance(string $updateId): UpdateState
            {
                $current = $this->store->load($this->target);
                $nextStep = $this->sequence[$this->cursor] ?? UpdateStep::Done;

                if ($nextStep === UpdateStep::HealthChecked && !isset($current->context['health_report'])) {
                    $ctx = $current->context;
                    $ctx['awaiting'] = 'health';
                    $waitingState = new UpdateState(
                        updateId: $updateId,
                        target: $this->target,
                        step: UpdateStep::SchemaVerified,
                        release: $this->release,
                        etag: null,
                        lastCheckAt: $current->lastCheckAt,
                        context: $ctx,
                        error: null,
                        updatedAt: '2026-10-19T12:01:00Z',
                    );
                    $this->store->save($waitingState);
                    return $waitingState;
                }

                $this->cursor++;
                $nextState = new UpdateState(
                    updateId: $updateId,
                    target: $this->target,
                    step: $nextStep,
                    release: $this->release,
                    etag: null,
                    lastCheckAt: $current->lastCheckAt,
                    context: $current->context,
                    error: null,
                    updatedAt: '2026-10-19T12:02:00Z',
                );
                $this->store->save($nextState);
                return $nextState;
            }

            public function rollback(string $updateId): UpdateState
            {
                $rbState = new UpdateState(
                    updateId: $updateId,
                    target: $this->target,
                    step: UpdateStep::RolledBack,
                    release: $this->release,
                    etag: null,
                    lastCheckAt: '2026-10-19T12:00:00Z',
                    context: [],
                    error: null,
                    updatedAt: '2026-10-19T12:03:00Z',
                );
                $this->store->save($rbState);
                return $rbState;
            }

            public function status(string $updateId): UpdateState
            {
                return $this->store->load($this->target);
            }
        };
    }

    private function createFakeBackup(): BackupInterface
    {
        return new class implements BackupInterface {
            public ?string $restoredId = null;

            public function create(BackupScope $scope, string $label, ?string $dumpPath = null): BackupRecord
            {
                return new BackupRecord('bkp-new', $scope, $label, '2026-10-19T12:00:00Z', '/tmp/code.zip', $dumpPath);
            }

            public function restore(BackupRecord $record): void
            {
                $this->restoredId = $record->id;
            }

            public function list(?BackupScope $scope = null): array
            {
                $scope ??= new BackupScope(UpdateKind::Core, 'core');
                return [
                    new BackupRecord('bkp-20261019-01', $scope, 'v2.3.0', '2026-10-19T10:00:00Z', '/tmp/1.zip', '/tmp/1.sql'),
                    new BackupRecord('bkp-20261019-02', $scope, 'v2.2.0', '2026-10-18T10:00:00Z', '/tmp/2.zip', null),
                    new BackupRecord('bkp-20261019-03', $scope, 'v2.1.0', '2026-10-17T10:00:00Z', '/tmp/3.zip', null),
                ];
            }

            public function prune(BackupScope $scope, int $keepLast = 2): int
            {
                return 1;
            }
        };
    }

    private function createFakeSession(string $role, string $csrfToken): SessionManagerInterface
    {
        return new class ($role, $csrfToken) implements SessionManagerInterface {
            public function __construct(
                private readonly string $role,
                private readonly string $csrf,
            ) {
            }
            public function start(): void {}
            public function getCsrfToken(): string { return $this->csrf; }
            public function validateCsrfToken(?string $token): bool
            {
                return $token !== null && hash_equals($this->csrf, $token);
            }
            public function get(string $key, $default = null)
            {
                if ($key === 'user_role') {
                    return $this->role;
                }
                return $default;
            }
            public function set(string $key, $value): void {}
            public function remove(string $key): void {}
            public function has(string $key): bool { return $key === 'user_role'; }
            public function destroy(): void {}
            public function setFlash(string $type, string $message): void {}
            public function getFlash(): ?array { return null; }
            public function regenerate(): void {}
        };
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
