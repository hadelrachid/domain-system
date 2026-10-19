<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Service;

use DomainSystem\Core\Contracts\EventDispatcherInterface;
use DomainSystem\Core\Contracts\NotificationManagerInterface;
use DomainSystem\Core\Contracts\Update\BackupInterface;
use DomainSystem\Core\Contracts\Update\CoreUpdaterInterface;
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
use DomainSystem\Core\Update\Enum\UpdateKind;
use DomainSystem\Core\Update\Enum\UpdateStatus;
use DomainSystem\Core\Update\Enum\UpdateStep;
use DomainSystem\Core\Update\Exception\ApplyException;
use DomainSystem\Core\Update\Exception\PreflightFailedException;
use DomainSystem\Core\Update\Exception\RollbackFailedException;
use DomainSystem\Core\Update\Exception\SchemaMigrationException;
use DomainSystem\Core\Update\Exception\UpdateException;
use DomainSystem\Core\Update\Value\ApplyResult;
use DomainSystem\Core\Update\Value\BackupRecord;
use DomainSystem\Core\Update\Value\BackupScope;
use DomainSystem\Core\Update\Value\DownloadedPackage;
use DomainSystem\Core\Update\Value\HealthReport;
use DomainSystem\Core\Update\Value\SchemaPlan;
use DomainSystem\Core\Update\Value\UpdateCheckResult;
use DomainSystem\Core\Update\Value\UpdateState;
use DomainSystem\Core\Update\Value\UpdateTarget;
use DomainSystem\Core\Update\Value\VerifiedPackage;
use Throwable;

/**
 * Orquestrador da máquina de estados de atualização do Núcleo (Modelo A).
 *
 * Executa uma etapa por chamada a `advance()`, persistindo o estado antes e depois.
 * Depende exclusivamente de contratos (`DomainSystem\Core\Contracts\Update\*`).
 */
final class CoreUpdater implements CoreUpdaterInterface
{
    private const DEFAULT_LOCK_TTL = 1800;

    private ?VerifiedPackage $cachedVerifiedPackage = null;
    private ?SchemaPlan $cachedSchemaPlan = null;

    public function __construct(
        private readonly UpdateTargetResolverInterface $targetResolver,
        private readonly UpdateCheckerInterface $checker,
        private readonly ReleaseSourceInterface $releaseSource,
        private readonly UpdateManifestParserInterface $manifestParser,
        private readonly UpdatePackageVerifierInterface $packageVerifier,
        private readonly PreflightInterface $preflight,
        private readonly SchemaPlannerInterface $schemaPlanner,
        private readonly BackupInterface $backup,
        private readonly DatabaseBackupInterface $databaseBackup,
        private readonly MaintenanceModeInterface $maintenanceMode,
        private readonly UpdateApplierInterface $applier,
        private readonly SchemaMigratorInterface $schemaMigrator,
        private readonly SchemaVerifierInterface $schemaVerifier,
        private readonly HealthCheckInterface $healthCheck,
        private readonly UpdateStateStoreInterface $stateStore,
        private readonly UpdateLockInterface $lock,
        private readonly string $tempUpdatesDir,
        private readonly bool $autoRollback = true,
        private readonly ?EventDispatcherInterface $events = null,
        private readonly ?NotificationManagerInterface $notifications = null,
        private readonly int $lockTtlSeconds = self::DEFAULT_LOCK_TTL,
    ) {
    }

    public function kind(): UpdateKind
    {
        return UpdateKind::Core;
    }

    public function check(UpdateTarget $target, bool $force = false): UpdateCheckResult
    {
        $result = $this->checker->check($target, $force);
        if ($result->status === UpdateStatus::Available && $result->release !== null) {
            $this->dispatchEvent('update.available', [
                'target'  => $target,
                'release' => $result->release,
            ]);
        }

        return $result;
    }

    public function begin(UpdateTarget $target, array $options = []): UpdateState
    {
        if (!$this->lock->acquire($target, $this->lockTtlSeconds)) {
            throw new UpdateException('Já existe uma atualização do núcleo em andamento (lock ativo).');
        }

        try {
            $checkResult = $this->checker->check($target, false);
            if ($checkResult->status !== UpdateStatus::Available || $checkResult->release === null) {
                $checkResult = $this->checker->check($target, true);
            }

            if ($checkResult->status !== UpdateStatus::Available || $checkResult->release === null) {
                throw new UpdateException('Nenhuma nova versão disponível para iniciar a atualização.');
            }

            $existing = $this->stateStore->load($target);
            $updateId = 'upd_' . gmdate('Ymd_His') . '_' . bin2hex(random_bytes(4));

            $context = [
                'options' => [
                    'confirmNoDbBackup' => !empty($options['confirmNoDbBackup']),
                    'inlineHealthCheck' => !empty($options['inlineHealthCheck']),
                ],
            ];
            if (isset($existing->context['notified_version'])) {
                $context['notified_version'] = $existing->context['notified_version'];
            }

            $state = new UpdateState(
                updateId: $updateId,
                target: $target,
                step: UpdateStep::Checked,
                release: $checkResult->release,
                etag: $existing->etag ?? $checkResult->release->etag,
                lastCheckAt: $checkResult->checkedAt,
                context: $context,
                error: null,
                updatedAt: gmdate('c'),
            );

            $this->stateStore->save($state);
            $this->dispatchEvent('update.started', [
                'updateId' => $updateId,
                'target'   => $target,
                'release'  => $checkResult->release,
            ]);

            return $state;
        } catch (Throwable $e) {
            $this->lock->release($target);
            if ($e instanceof UpdateException) {
                throw $e;
            }
            throw new UpdateException('Não foi possível iniciar a atualização: ' . $e->getMessage(), 0, $e);
        }
    }

    public function advance(string $updateId): UpdateState
    {
        $target = $this->targetResolver->core();
        $state = $this->stateStore->load($target);

        if ($state->updateId !== $updateId) {
            throw new UpdateException(sprintf(
                'ID de atualização "%s" não corresponde ao estado ativo ("%s").',
                $updateId,
                $state->updateId
            ));
        }

        if (in_array($state->step, [UpdateStep::Idle, UpdateStep::Done, UpdateStep::RolledBack, UpdateStep::Failed], true)) {
            return $state;
        }

        // Tratamento de etapa interrompida por queda anterior
        if (isset($state->context['in_progress_step']) && is_string($state->context['in_progress_step'])) {
            $interrupted = UpdateStep::tryFrom($state->context['in_progress_step']);
            if ($interrupted === UpdateStep::Applied || $interrupted === UpdateStep::Migrated) {
                return $this->handleStepFailure(
                    $state,
                    new ApplyException(sprintf('A etapa "%s" foi interrompida abruptamente na execução anterior.', $interrupted->value))
                );
            }
            // Etapas seguras (Downloaded, Verified, Preflighted) limpam o marcador e repetem
            $cleanContext = $state->context;
            unset($cleanContext['in_progress_step']);
            $state = $this->replaceContext($state, $cleanContext);
            $this->stateStore->save($state);
        }

        try {
            return match ($state->step) {
                UpdateStep::Checked        => $this->executeDownloadStep($state),
                UpdateStep::Downloaded     => $this->executeVerifyStep($state),
                UpdateStep::Verified       => $this->executePreflightStep($state),
                UpdateStep::Preflighted    => $this->executeBackupStep($state),
                UpdateStep::BackedUp       => $this->executeMaintenanceOnStep($state),
                UpdateStep::MaintenanceOn  => $this->executeApplyStep($state),
                UpdateStep::Applied        => $this->executeMigrateStep($state),
                UpdateStep::Migrated       => $this->executeVerifySchemaStep($state),
                UpdateStep::SchemaVerified => $this->executeHealthCheckStep($state),
                UpdateStep::HealthChecked  => $this->executeFinalizeStep($state),
                default                    => $state,
            };
        } catch (Throwable $e) {
            $latestState = $this->stateStore->load($target);
            $effectiveState = $latestState->updateId === $state->updateId ? $latestState : $state;
            return $this->handleStepFailure($effectiveState, $e);
        }
    }

    public function rollback(string $updateId): UpdateState
    {
        $target = $this->targetResolver->core();
        $state = $this->stateStore->load($target);

        if ($state->updateId !== $updateId && $state->updateId !== 'idle') {
            throw new UpdateException(sprintf('ID de atualização "%s" inválido para rollback.', $updateId));
        }

        return $this->executeRollback($state, $state->error ?? 'Rollback solicitado manualmente.');
    }

    public function status(string $updateId): UpdateState
    {
        $target = $this->targetResolver->core();
        $state = $this->stateStore->load($target);

        if ($updateId !== '' && $state->updateId !== 'idle' && $state->updateId !== $updateId) {
            throw new UpdateException(sprintf('ID de atualização "%s" não encontrado.', $updateId));
        }

        return $state;
    }

    private function executeDownloadStep(UpdateState $state): UpdateState
    {
        $state = $this->markInProgress($state, UpdateStep::Downloaded);
        if ($state->release === null) {
            throw new UpdateException('Release ausente no estado para download.');
        }

        $destDir = rtrim(str_replace('\\', '/', $this->tempUpdatesDir), '/') . '/' . $state->release->version;
        $downloaded = $this->releaseSource->fetchPackage($state->release, $destDir);

        return $this->completeStep($state, UpdateStep::Downloaded, [
            'archive_path' => $downloaded->archivePath,
            'archive_size' => $downloaded->sizeBytes,
        ]);
    }

    private function executeVerifyStep(UpdateState $state): UpdateState
    {
        $state = $this->markInProgress($state, UpdateStep::Verified);
        if ($state->release === null) {
            throw new UpdateException('Release ausente no estado para verificação.');
        }

        $archivePath = (string)($state->context['archive_path'] ?? '');
        $archiveSize = (int)($state->context['archive_size'] ?? $state->release->sizeBytes);

        $downloaded = new DownloadedPackage(
            release: $state->release,
            archivePath: $archivePath,
            sizeBytes: max(1, $archiveSize),
        );

        $verified = $this->packageVerifier->verify($downloaded);
        $this->cachedVerifiedPackage = $verified;

        return $this->completeStep($state, UpdateStep::Verified, [
            'extracted_path' => $verified->extractedPath,
        ]);
    }

    private function executePreflightStep(UpdateState $state): UpdateState
    {
        $state = $this->markInProgress($state, UpdateStep::Preflighted);
        $verified = $this->resolveVerifiedPackage($state);

        $report = $this->preflight->run($verified, $state->target);
        if (!$report->canProceed()) {
            $firstBlock = $report->blocks()[0]->message ?? 'Pré-checagem reprovada.';
            throw new PreflightFailedException($firstBlock);
        }

        $schemaPlan = $this->schemaPlanner->plan($verified->manifest, $verified->extractedPath);
        $this->cachedSchemaPlan = $schemaPlan;

        if ($schemaPlan->blocked !== []) {
            throw new PreflightFailedException(sprintf(
                'Downgrade de banco detectado: migrações já executadas ausentes no pacote (%s).',
                implode(', ', $schemaPlan->blocked)
            ));
        }

        $dbSupported = $this->databaseBackup->isSupported();
        $confirmNoDbBackup = !empty($state->context['options']['confirmNoDbBackup']);

        if (($schemaPlan->requiresDatabaseBackup || !$dbSupported) && !$confirmNoDbBackup) {
            throw new PreflightFailedException(
                'O plano exige confirmação explícita (confirmNoDbBackup) para prosseguir sem backup automático de banco ou com migração destrutiva.'
            );
        }

        return $this->completeStep($state, UpdateStep::Preflighted, [
            'preflight_report' => $report->toArray(),
            'schema_plan'      => [
                'scope'                  => $schemaPlan->scope,
                'pending'                => $schemaPlan->pending,
                'blocked'                => $schemaPlan->blocked,
                'destructive'            => $schemaPlan->destructive,
                'requiresDatabaseBackup' => $schemaPlan->requiresDatabaseBackup,
            ],
        ]);
    }

    private function executeBackupStep(UpdateState $state): UpdateState
    {
        $state = $this->markInProgress($state, UpdateStep::BackedUp);
        $scope = new BackupScope($state->target->kind, $state->target->slug);
        $label = 'pre-update-' . ($state->release?->version ?? 'unknown');

        $record = $this->backup->create($scope, $label);

        if ($this->databaseBackup->isSupported()) {
            $dumpFile = dirname($record->codePath) . '/database.dump';
            $dumpResult = $this->databaseBackup->dump($dumpFile);
            $record = new BackupRecord(
                id: $record->id,
                scope: $record->scope,
                label: $record->label,
                createdAt: $record->createdAt,
                codePath: $record->codePath,
                dumpPath: $dumpResult->dumpPath,
            );
            if (method_exists($this->backup, 'attachDumpPath')) {
                $record = $this->backup->attachDumpPath($record, $dumpResult->dumpPath);
            }
        }

        $this->backup->prune($scope, 2);

        return $this->completeStep($state, UpdateStep::BackedUp, [
            'backup' => $record->toArray(),
        ]);
    }

    private function executeMaintenanceOnStep(UpdateState $state): UpdateState
    {
        $state = $this->markInProgress($state, UpdateStep::MaintenanceOn);
        $wasOn = $this->maintenanceMode->isEnabled();

        $this->maintenanceMode->enable('Atualizando núcleo para v' . ($state->release?->version ?? ''));

        return $this->completeStep($state, UpdateStep::MaintenanceOn, [
            'maintenance_was_on' => $wasOn,
        ]);
    }

    private function executeApplyStep(UpdateState $state): UpdateState
    {
        // Instancia/resolve todos os dados necessários antes da troca de arquivos
        $verified = $this->resolveVerifiedPackage($state);
        $backupRecord = $this->resolveBackupRecord($state);

        $state = $this->markInProgress($state, UpdateStep::Applied);

        $applyResult = $this->applier->apply($verified, $state->target, $backupRecord);

        return $this->completeStep($state, UpdateStep::Applied, [
            'apply_result' => [
                'written'          => $applyResult->written,
                'removed'          => $applyResult->removed,
                'skippedProtected' => $applyResult->skippedProtected,
            ],
        ]);
    }

    private function executeMigrateStep(UpdateState $state): UpdateState
    {
        $state = $this->markInProgress($state, UpdateStep::Migrated);
        $schemaPlan = $this->resolveSchemaPlan($state);

        // Registra que a etapa de migração de banco foi iniciada para que o rollback saiba restaurar o dump se falhar
        $stateWithFlag = $this->replaceContext($state, array_replace($state->context, ['migrations_ran' => true]));
        $this->stateStore->save($stateWithFlag);
        $state = $stateWithFlag;

        $result = $this->schemaMigrator->migrate($schemaPlan);
        if (!$result->isOk()) {
            throw new SchemaMigrationException(sprintf(
                'Falha na migração "%s": %s',
                (string)$result->failed,
                (string)$result->error
            ));
        }

        return $this->completeStep($state, UpdateStep::Migrated, [
            'migrations_ran' => true,
            'schema_result'  => [
                'executed' => $result->executed,
                'failed'   => $result->failed,
                'error'    => $result->error,
            ],
        ]);
    }

    private function executeVerifySchemaStep(UpdateState $state): UpdateState
    {
        $state = $this->markInProgress($state, UpdateStep::SchemaVerified);
        $verified = $this->resolveVerifiedPackage($state);

        $verification = $this->schemaVerifier->verify($verified->manifest);
        if (!$verification->ok) {
            throw new SchemaMigrationException('A verificação pós-migração do schema acusou inconsistências.');
        }

        return $this->completeStep($state, UpdateStep::SchemaVerified, [
            'schema_verification' => [
                'ok'             => $verification->ok,
                'missingTables'  => $verification->missingTables,
                'missingColumns' => $verification->missingColumns,
                'unrecorded'     => $verification->unrecorded,
            ],
        ]);
    }

    private function executeHealthCheckStep(UpdateState $state): UpdateState
    {
        // Emenda 5 / Passo 13: a saúde roda em requisição nova via GET /admin/settings/updates/core/health.
        // Caso o relatório ainda não esteja gravado no contexto, sinaliza `awaiting = health`
        // (exceto quando `inlineHealthCheck` está habilitado explicitamente).
        if (!isset($state->context['health_report']) || !is_array($state->context['health_report'])) {
            if (!empty($state->context['options']['inlineHealthCheck'])) {
                $inlineReport = $this->healthCheck->run($state->target);
                $state = $this->replaceContext($state, array_replace($state->context, [
                    'health_report' => $inlineReport->toArray(),
                ]));
            } else {
                $awaitingContext = $state->context;
                $awaitingContext['awaiting'] = 'health';
                $awaitingState = $this->replaceContext($state, $awaitingContext);
                $this->stateStore->save($awaitingState);
                return $awaitingState;
            }
        }

        $state = $this->markInProgress($state, UpdateStep::HealthChecked);
        $report = HealthReport::fromArray($state->context['health_report']);

        if (!$report->ok) {
            throw new UpdateException('O check de saúde pós-atualização reprovou o estado do sistema.');
        }

        $cleanContext = $state->context;
        unset($cleanContext['awaiting'], $cleanContext['in_progress_step']);

        $nextState = new UpdateState(
            updateId: $state->updateId,
            target: $state->target,
            step: UpdateStep::HealthChecked,
            release: $state->release,
            etag: $state->etag,
            lastCheckAt: $state->lastCheckAt,
            context: $cleanContext,
            error: null,
            updatedAt: gmdate('c'),
        );

        $this->stateStore->save($nextState);
        $this->dispatchEvent('update.step', [
            'updateId' => $nextState->updateId,
            'step'     => UpdateStep::HealthChecked->value,
        ]);

        return $nextState;
    }

    private function executeFinalizeStep(UpdateState $state): UpdateState
    {
        $wasMaintenanceOn = !empty($state->context['maintenance_was_on']);
        if (!$wasMaintenanceOn) {
            $this->maintenanceMode->disable();
        }

        $this->lock->release($state->target);

        $newVersion = $state->release?->version ?? $state->target->installedVersion;
        $updatedTarget = new UpdateTarget(
            kind: $state->target->kind,
            slug: $state->target->slug,
            repository: $state->target->repository,
            installedVersion: $newVersion,
            channel: $state->target->channel,
        );

        $cleanContext = $state->context;
        unset($cleanContext['in_progress_step'], $cleanContext['awaiting']);

        $doneState = new UpdateState(
            updateId: $state->updateId,
            target: $updatedTarget,
            step: UpdateStep::Done,
            release: $state->release,
            etag: $state->etag,
            lastCheckAt: $state->lastCheckAt,
            context: $cleanContext,
            error: null,
            updatedAt: gmdate('c'),
        );

        $this->stateStore->save($doneState);

        $this->dispatchEvent('update.completed', [
            'updateId' => $doneState->updateId,
            'version'  => $newVersion,
        ]);

        $this->notifications?->push(
            sprintf('Sistema atualizado com sucesso para a versão v%s.', $newVersion),
            'success',
            'SystemUpdater',
            ['version' => $newVersion]
        );

        return $doneState;
    }

    private function handleStepFailure(UpdateState $state, Throwable $e): UpdateState
    {
        $hasBackup = isset($state->context['backup']) && is_array($state->context['backup']);

        if ($hasBackup && $this->autoRollback) {
            return $this->executeRollback($state, $e->getMessage());
        }

        // Sem backup ainda (ou auto_rollback desligado): desliga manutenção se foi o updater que ligou e libera lock
        if (array_key_exists('maintenance_was_on', $state->context) && empty($state->context['maintenance_was_on'])) {
            $this->maintenanceMode->disable();
        }

        $this->lock->release($state->target);

        $cleanContext = $state->context;
        unset($cleanContext['in_progress_step'], $cleanContext['awaiting']);

        $failedState = new UpdateState(
            updateId: $state->updateId,
            target: $state->target,
            step: UpdateStep::Failed,
            release: $state->release,
            etag: $state->etag,
            lastCheckAt: $state->lastCheckAt,
            context: $cleanContext,
            error: $e->getMessage(),
            updatedAt: gmdate('c'),
        );

        $this->stateStore->save($failedState);

        $this->dispatchEvent('update.failed', [
            'updateId' => $failedState->updateId,
            'error'    => $e->getMessage(),
        ]);

        $this->notifications?->push(
            'Falha na atualização do sistema: ' . $e->getMessage(),
            'error',
            'SystemUpdater',
            ['updateId' => $failedState->updateId]
        );

        return $failedState;
    }

    private function executeRollback(UpdateState $state, string $reason): UpdateState
    {
        $rollingBackState = $state->withStep(UpdateStep::RollingBack);
        $this->stateStore->save($rollingBackState);

        try {
            if (isset($state->context['backup']) && is_array($state->context['backup'])) {
                $backupRecord = BackupRecord::fromArray($state->context['backup']);

                $applyData = is_array($state->context['apply_result'] ?? null)
                    ? $state->context['apply_result']
                    : ['written' => [], 'removed' => [], 'skippedProtected' => []];

                $applyResult = new ApplyResult(
                    backup: $backupRecord,
                    written: array_values(array_map('strval', $applyData['written'] ?? [])),
                    removed: array_values(array_map('strval', $applyData['removed'] ?? [])),
                    skippedProtected: array_values(array_map('strval', $applyData['skippedProtected'] ?? [])),
                );

                $this->applier->revert($applyResult);

                if (!empty($state->context['migrations_ran']) && $backupRecord->dumpPath !== null) {
                    $this->databaseBackup->restore($backupRecord->dumpPath);
                }
            }

            $wasMaintenanceOn = !empty($state->context['maintenance_was_on']);
            if (!$wasMaintenanceOn) {
                $this->maintenanceMode->disable();
            }

            $this->lock->release($state->target);

            $cleanContext = $state->context;
            unset($cleanContext['in_progress_step'], $cleanContext['awaiting']);

            $rolledBackState = new UpdateState(
                updateId: $state->updateId,
                target: $state->target,
                step: UpdateStep::RolledBack,
                release: $state->release,
                etag: $state->etag,
                lastCheckAt: $state->lastCheckAt,
                context: $cleanContext,
                error: $reason !== '' ? $reason : 'Atualização revertida.',
                updatedAt: gmdate('c'),
            );

            $this->stateStore->save($rolledBackState);

            $this->dispatchEvent('update.rolled_back', [
                'updateId' => $rolledBackState->updateId,
                'reason'   => $reason,
            ]);

            $this->notifications?->push(
                'A atualização falhou e foi revertida automaticamente: ' . $reason,
                'warning',
                'SystemUpdater',
                ['updateId' => $rolledBackState->updateId]
            );

            return $rolledBackState;
        } catch (RollbackFailedException|Throwable $rollbackError) {
            // Pior caso (Passo 14): mantém o modo de manutenção ligado para proteger os visitantes!
            try {
                if (!$this->maintenanceMode->isEnabled()) {
                    $this->maintenanceMode->enable('Manutenção de emergência após falha de rollback.');
                }
            } catch (Throwable) {
            }

            $this->lock->release($state->target);

            $criticalMessage = sprintf(
                'Falha crítica no rollback (%s) após erro (%s). Execute via CLI: php os-cli.php system:rollback',
                $rollbackError->getMessage(),
                $reason
            );

            $cleanContext = $state->context;
            unset($cleanContext['in_progress_step'], $cleanContext['awaiting']);

            $failedState = new UpdateState(
                updateId: $state->updateId,
                target: $state->target,
                step: UpdateStep::Failed,
                release: $state->release,
                etag: $state->etag,
                lastCheckAt: $state->lastCheckAt,
                context: $cleanContext,
                error: $criticalMessage,
                updatedAt: gmdate('c'),
            );

            $this->stateStore->save($failedState);

            $this->dispatchEvent('update.failed', [
                'updateId' => $failedState->updateId,
                'error'    => $criticalMessage,
                'critical' => true,
            ]);

            $this->notifications?->push(
                $criticalMessage,
                'error',
                'SystemUpdater',
                ['updateId' => $failedState->updateId, 'cli' => 'php os-cli.php system:rollback']
            );

            return $failedState;
        }
    }

    private function markInProgress(UpdateState $state, UpdateStep $targetStep): UpdateState
    {
        $ctx = $state->context;
        $ctx['in_progress_step'] = $targetStep->value;
        $updated = $this->replaceContext($state, $ctx);
        $this->stateStore->save($updated);
        return $updated;
    }

    /**
     * @param array<string, mixed> $extraContext
     */
    private function completeStep(UpdateState $state, UpdateStep $completedStep, array $extraContext = []): UpdateState
    {
        $ctx = array_replace($state->context, $extraContext);
        unset($ctx['in_progress_step'], $ctx['awaiting']);

        $next = new UpdateState(
            updateId: $state->updateId,
            target: $state->target,
            step: $completedStep,
            release: $state->release,
            etag: $state->etag,
            lastCheckAt: $state->lastCheckAt,
            context: $ctx,
            error: null,
            updatedAt: gmdate('c'),
        );

        $this->stateStore->save($next);

        $this->dispatchEvent('update.step', [
            'updateId' => $next->updateId,
            'step'     => $completedStep->value,
        ]);

        return $next;
    }

    /**
     * @param array<string, mixed> $newContext
     */
    private function replaceContext(UpdateState $state, array $newContext): UpdateState
    {
        return new UpdateState(
            updateId: $state->updateId,
            target: $state->target,
            step: $state->step,
            release: $state->release,
            etag: $state->etag,
            lastCheckAt: $state->lastCheckAt,
            context: $newContext,
            error: $state->error,
            updatedAt: gmdate('c'),
        );
    }

    private function resolveVerifiedPackage(UpdateState $state): VerifiedPackage
    {
        if ($this->cachedVerifiedPackage !== null) {
            return $this->cachedVerifiedPackage;
        }

        if ($state->release === null) {
            throw new UpdateException('Release ausente no estado.');
        }

        $extractedPath = (string)($state->context['extracted_path'] ?? '');
        if ($extractedPath === '') {
            throw new UpdateException('Caminho de quarentena (extracted_path) ausente no estado.');
        }

        $manifestPath = rtrim(str_replace('\\', '/', $extractedPath), '/') . '/manifest.json';
        $rawManifest = is_file($manifestPath) ? (string)file_get_contents($manifestPath) : '';
        $manifest = $this->manifestParser->parse($rawManifest);

        $this->cachedVerifiedPackage = new VerifiedPackage(
            release: $state->release,
            manifest: $manifest,
            extractedPath: $extractedPath,
        );

        return $this->cachedVerifiedPackage;
    }

    private function resolveBackupRecord(UpdateState $state): BackupRecord
    {
        if (!isset($state->context['backup']) || !is_array($state->context['backup'])) {
            throw new UpdateException('Registro de backup ausente no estado.');
        }

        return BackupRecord::fromArray($state->context['backup']);
    }

    private function resolveSchemaPlan(UpdateState $state): SchemaPlan
    {
        if ($this->cachedSchemaPlan !== null) {
            return $this->cachedSchemaPlan;
        }

        if (isset($state->context['schema_plan']) && is_array($state->context['schema_plan'])) {
            $raw = $state->context['schema_plan'];
            return new SchemaPlan(
                scope: (string)($raw['scope'] ?? '__core__'),
                pending: is_array($raw['pending'] ?? null) ? $raw['pending'] : [],
                blocked: is_array($raw['blocked'] ?? null) ? $raw['blocked'] : [],
                destructive: !empty($raw['destructive']),
                requiresDatabaseBackup: !empty($raw['requiresDatabaseBackup']),
            );
        }

        $verified = $this->resolveVerifiedPackage($state);
        return $this->schemaPlanner->plan($verified->manifest, $verified->extractedPath);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function dispatchEvent(string $eventName, array $payload): void
    {
        try {
            $this->events?->dispatch($eventName, $payload);
        } catch (Throwable) {
        }
    }
}
