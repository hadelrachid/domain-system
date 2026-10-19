<?php

declare(strict_types=1);

namespace DomainSystem\SystemApps\SystemUpdater\Console;

use DomainSystem\Core\Contracts\Update\BackupInterface;
use DomainSystem\Core\Contracts\Update\CoreUpdaterInterface;
use DomainSystem\Core\Contracts\Update\HealthCheckInterface;
use DomainSystem\Core\Contracts\Update\UpdateStateStoreInterface;
use DomainSystem\Core\Contracts\Update\UpdateTargetResolverInterface;
use DomainSystem\Core\Update\Enum\UpdateKind;
use DomainSystem\Core\Update\Enum\UpdateStatus;
use DomainSystem\Core\Update\Enum\UpdateStep;
use DomainSystem\Core\Update\Value\BackupScope;
use DomainSystem\Core\Update\Value\UpdateState;
use Throwable;

/**
 * Camada de comando CLI para o atualizador do sistema (testável sem I/O real).
 *
 * Comandos suportados:
 * - `system:check`
 * - `system:status`
 * - `system:update [--yes] [--confirm-no-db-backup]`
 * - `system:rollback [id]`
 * - `system:health`
 */
final class UpdateCliCommandHandler
{
    public function __construct(
        private readonly CoreUpdaterInterface $updater,
        private readonly UpdateTargetResolverInterface $targetResolver,
        private readonly UpdateStateStoreInterface $stateStore,
        private readonly HealthCheckInterface $healthCheck,
        private readonly ?BackupInterface $backup = null,
    ) {
    }

    /**
     * @param list<string> $args Argumentos após o nome do comando (ex.: `['--yes']`).
     * @return array{exitCode: int, output: string}
     */
    public function execute(string $command, array $args = []): array
    {
        return match (strtolower(trim($command))) {
            'system:check'    => $this->handleCheck(),
            'system:status'   => $this->handleStatus(),
            'system:update'   => $this->handleUpdate($args),
            'system:rollback' => $this->handleRollback($args),
            'system:health'   => $this->handleHealth(),
            default           => [
                'exitCode' => 1,
                'output'   => sprintf("Comando desconhecido: %s\n", $command),
            ],
        };
    }

    /**
     * @return array{exitCode: int, output: string}
     */
    private function handleCheck(): array
    {
        $target = $this->targetResolver->core();
        $result = $this->updater->check($target, true);

        $lines = [
            sprintf('Alvo: %s (%s)', $target->slug, $target->repository),
            sprintf('Versão instalada: v%s', $target->installedVersion),
            sprintf('Status: %s', $result->status->value),
        ];

        if ($result->release !== null) {
            $lines[] = sprintf('Versão disponível: v%s (%s)', $result->release->version, $result->release->tag);
        }

        foreach ($result->reasons as $reason) {
            $lines[] = 'Motivo: ' . $reason;
        }

        return [
            'exitCode' => $result->status === UpdateStatus::Unknown ? 1 : 0,
            'output'   => implode("\n", $lines) . "\n",
        ];
    }

    /**
     * @return array{exitCode: int, output: string}
     */
    private function handleStatus(): array
    {
        $target = $this->targetResolver->core();
        $state = $this->stateStore->load($target);

        $lines = [
            sprintf('Update ID: %s', $state->updateId),
            sprintf('Versão instalada: v%s', $target->installedVersion),
            sprintf('Etapa atual: %s', $state->step->value),
            sprintf('Atualizado em: %s', $state->updatedAt),
        ];

        if ($state->release !== null) {
            $lines[] = sprintf('Release alvo: v%s', $state->release->version);
        }
        if ($state->error !== null) {
            $lines[] = sprintf('Erro: %s', $state->error);
        }

        return [
            'exitCode' => $state->step === UpdateStep::Failed ? 1 : 0,
            'output'   => implode("\n", $lines) . "\n",
        ];
    }

    /**
     * @param list<string> $args
     * @return array{exitCode: int, output: string}
     */
    private function handleUpdate(array $args): array
    {
        $confirmed = in_array('--yes', $args, true) || in_array('-y', $args, true);
        if (!$confirmed) {
            return [
                'exitCode' => 1,
                'output'   => "Confirmação obrigatória: execute 'php os-cli.php system:update --yes' para aplicar a atualização.\n",
            ];
        }

        $confirmNoDbBackup = in_array('--confirm-no-db-backup', $args, true);
        $target = $this->targetResolver->core();
        $lines = [];

        try {
            $state = $this->updater->begin($target, [
                'confirmNoDbBackup' => $confirmNoDbBackup,
            ]);
            $lines[] = sprintf('[%s] Atualização iniciada (ID: %s)', $state->step->value, $state->updateId);
            $updateId = $state->updateId;

            $maxIterations = 20;
            $iteration = 0;

            while ($iteration < $maxIterations) {
                $iteration++;
                $state = $this->updater->advance($updateId);
                $lines[] = sprintf('[%s] Etapa processada', $state->step->value);

                if (($state->context['awaiting'] ?? null) === 'health') {
                    $lines[] = '[health] Executando verificação de saúde pós-atualização...';
                    $report = $this->healthCheck->run($target);
                    $ctx = $state->context;
                    $ctx['health_report'] = $report->toArray();
                    unset($ctx['awaiting']);
                    $stateWithHealth = new UpdateState(
                        updateId: $state->updateId,
                        target: $state->target,
                        step: $state->step,
                        release: $state->release,
                        etag: $state->etag,
                        lastCheckAt: $state->lastCheckAt,
                        context: $ctx,
                        error: $state->error,
                        updatedAt: gmdate('c'),
                    );
                    $this->stateStore->save($stateWithHealth);
                    continue;
                }

                if ($state->step === UpdateStep::Done) {
                    $lines[] = sprintf(
                        '✅ Atualização concluída com sucesso para v%s.',
                        $state->release?->version ?? $state->target->installedVersion
                    );
                    return [
                        'exitCode' => 0,
                        'output'   => implode("\n", $lines) . "\n",
                    ];
                }

                if ($state->step === UpdateStep::RolledBack || $state->step === UpdateStep::Failed) {
                    $lines[] = sprintf(
                        '❌ Atualização encerrada em estado "%s": %s',
                        $state->step->value,
                        (string)$state->error
                    );
                    return [
                        'exitCode' => 1,
                        'output'   => implode("\n", $lines) . "\n",
                    ];
                }
            }

            $lines[] = '❌ Limite de iterações excedido durante atualização.';
            return [
                'exitCode' => 1,
                'output'   => implode("\n", $lines) . "\n",
            ];
        } catch (Throwable $e) {
            $lines[] = '❌ Erro ao executar atualização: ' . $e->getMessage();
            return [
                'exitCode' => 1,
                'output'   => implode("\n", $lines) . "\n",
            ];
        }
    }

    /**
     * @param list<string> $args
     * @return array{exitCode: int, output: string}
     */
    private function handleRollback(array $args): array
    {
        $target = $this->targetResolver->core();
        $state = $this->stateStore->load($target);
        $requestedId = isset($args[0]) && !str_starts_with($args[0], '-') ? trim($args[0]) : '';

        try {
            // Se o argumento corresponder a um ID de backup específico na listagem, restaura-o
            if ($requestedId !== '' && $requestedId !== $state->updateId && $this->backup !== null) {
                $scope = new BackupScope(UpdateKind::Core, 'core');
                foreach ($this->backup->list($scope) as $record) {
                    if ($record->id === $requestedId) {
                        $this->backup->restore($record);
                        return [
                            'exitCode' => 0,
                            'output'   => sprintf("✅ Backup '%s' restaurado com sucesso.\n", $record->id),
                        ];
                    }
                }
            }

            $updateId = $requestedId !== '' ? $requestedId : $state->updateId;
            $rolledBack = $this->updater->rollback($updateId);

            if ($rolledBack->step === UpdateStep::RolledBack) {
                return [
                    'exitCode' => 0,
                    'output'   => sprintf("✅ Rollback concluído para a atualização '%s'.\n", $rolledBack->updateId),
                ];
            }

            return [
                'exitCode' => 1,
                'output'   => sprintf(
                    "❌ Rollback terminou em estado '%s': %s\n",
                    $rolledBack->step->value,
                    (string)$rolledBack->error
                ),
            ];
        } catch (Throwable $e) {
            return [
                'exitCode' => 1,
                'output'   => '❌ Erro ao executar rollback: ' . $e->getMessage() . "\n",
            ];
        }
    }

    /**
     * @return array{exitCode: int, output: string}
     */
    private function handleHealth(): array
    {
        $target = $this->targetResolver->core();
        $report = $this->healthCheck->run($target);

        $lines = [
            sprintf('Saúde do sistema: %s', $report->ok ? 'OK' : 'FALHA'),
        ];

        foreach ($report->toArray()['checks'] as $check) {
            $id = (string)($check['id'] ?? 'check');
            $level = (string)($check['level'] ?? (($check['ok'] ?? false) ? 'ok' : 'block'));
            $msg = (string)($check['message'] ?? '');
            $lines[] = sprintf(' - [%s] %s: %s', strtoupper($level), $id, $msg);
        }

        return [
            'exitCode' => $report->ok ? 0 : 1,
            'output'   => implode("\n", $lines) . "\n",
        ];
    }
}
