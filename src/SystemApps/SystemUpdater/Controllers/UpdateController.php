<?php

declare(strict_types=1);

namespace DomainSystem\SystemApps\SystemUpdater\Controllers;

use DomainSystem\Core\Contracts\NotificationManagerInterface;
use DomainSystem\Core\Contracts\ResponseInterface;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Contracts\ThemeManagerInterface;
use DomainSystem\Core\Contracts\Update\BackupInterface;
use DomainSystem\Core\Contracts\Update\CoreUpdaterInterface;
use DomainSystem\Core\Contracts\Update\DatabaseBackupInterface;
use DomainSystem\Core\Contracts\Update\HealthCheckInterface;
use DomainSystem\Core\Contracts\Update\UpdateCheckerInterface;
use DomainSystem\Core\Contracts\Update\UpdateStateStoreInterface;
use DomainSystem\Core\Contracts\Update\UpdateTargetResolverInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Http\Responses\JsonResponse;
use DomainSystem\Core\Update\Enum\UpdateKind;
use DomainSystem\Core\Update\Enum\UpdateStep;
use DomainSystem\Core\Update\Service\UpdateNotifier;
use DomainSystem\Core\Update\Value\BackupScope;
use DomainSystem\Core\Update\Value\UpdateState;
use Throwable;

final class UpdateController
{
    private UpdateNotifier $notifier;

    public function __construct(
        private readonly ThemeManagerInterface $theme,
        private readonly SessionManagerInterface $session,
        private readonly UpdateCheckerInterface $checker,
        private readonly UpdateTargetResolverInterface $targetResolver,
        private readonly UpdateStateStoreInterface $stateStore,
        ?NotificationManagerInterface $notifications = null,
        ?UpdateNotifier $notifier = null,
        private readonly ?HealthCheckInterface $healthCheck = null,
        private readonly ?CoreUpdaterInterface $coreUpdater = null,
        private readonly ?BackupInterface $backup = null,
        private readonly ?DatabaseBackupInterface $databaseBackup = null,
    ) {
        $this->notifier = $notifier ?? new UpdateNotifier($this->stateStore, $notifications);
    }

    public function index(?Request $request = null): ResponseInterface
    {
        if (!$this->isAdmin()) {
            return new Response('Acesso restrito a administradores.', 403);
        }

        $target = $this->targetResolver->core();
        $checkResult = $this->checker->check($target, false);
        $this->notifier->notifyIfAvailable($checkResult);

        $currentState = $this->stateStore->load($target);
        $backups = $this->backup !== null
            ? $this->backup->list(new BackupScope(UpdateKind::Core, 'core'))
            : [];
        $dbBackupSupported = $this->databaseBackup === null || $this->databaseBackup->isSupported();

        $checkedNow = $request !== null && (string)($request->input('checked') ?? '') === '1';

        $html = $this->theme->render('updates', [
            'target'              => $target,
            'check_result'        => $checkResult,
            'update_state'        => $currentState,
            'backups'             => $backups,
            'db_backup_supported' => $dbBackupSupported,
            'csrf_token'          => $this->session->getCsrfToken(),
            'checked_now'         => $checkedNow,
            'base_url'            => defined('BASE_URL') ? rtrim((string)BASE_URL, '/') : '',
        ], dirname(__DIR__) . '/views');

        return new Response($html);
    }

    public function check(Request $request): ResponseInterface
    {
        if (!$this->isAdmin()) {
            return new Response('Acesso restrito a administradores.', 403);
        }

        $token = (string)($request->input('csrf_token') ?? '');
        if (!$this->session->validateCsrfToken($token)) {
            return new Response('Token CSRF inválido.', 403);
        }

        $target = $this->targetResolver->core();
        $checkResult = $this->checker->check($target, true);
        $this->notifier->notifyIfAvailable($checkResult);

        $baseUrl = defined('BASE_URL') ? rtrim((string)BASE_URL, '/') : '';
        return Response::redirect($baseUrl . '/admin/settings/updates?checked=1');
    }

    public function begin(Request $request): ResponseInterface
    {
        if (!$this->isAdmin()) {
            return new JsonResponse(['ok' => false, 'error' => 'Acesso restrito a administradores.'], 403);
        }

        $token = (string)($request->input('csrf_token') ?? '');
        if (!$this->session->validateCsrfToken($token)) {
            return new JsonResponse(['ok' => false, 'error' => 'Token CSRF inválido.'], 403);
        }

        if ($this->coreUpdater === null) {
            return new JsonResponse(['ok' => false, 'error' => 'Orquestrador CoreUpdater não disponível.'], 500);
        }

        $confirmNoDbBackup = in_array(
            (string)($request->input('confirm_no_db_backup') ?? ''),
            ['1', 'true', 'on'],
            true
        );

        try {
            $target = $this->targetResolver->core();
            $state = $this->coreUpdater->begin($target, [
                'confirmNoDbBackup' => $confirmNoDbBackup,
            ]);

            return new JsonResponse([
                'ok'    => true,
                'state' => $state->toArray(),
            ]);
        } catch (Throwable $e) {
            return new JsonResponse([
                'ok'    => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function advance(Request $request): ResponseInterface
    {
        if (!$this->isAdmin()) {
            return new JsonResponse(['ok' => false, 'error' => 'Acesso restrito a administradores.'], 403);
        }

        $token = (string)($request->input('csrf_token') ?? '');
        if (!$this->session->validateCsrfToken($token)) {
            return new JsonResponse(['ok' => false, 'error' => 'Token CSRF inválido.'], 403);
        }

        if ($this->coreUpdater === null) {
            return new JsonResponse(['ok' => false, 'error' => 'Orquestrador CoreUpdater não disponível.'], 500);
        }

        $updateId = trim((string)($request->input('update_id') ?? ''));
        if ($updateId === '') {
            return new JsonResponse(['ok' => false, 'error' => 'O parâmetro update_id é obrigatório.'], 400);
        }

        try {
            $state = $this->coreUpdater->advance($updateId);
            $isOk = !in_array($state->step, [UpdateStep::Failed, UpdateStep::RolledBack], true);

            return new JsonResponse([
                'ok'    => $isOk,
                'state' => $state->toArray(),
            ]);
        } catch (Throwable $e) {
            return new JsonResponse([
                'ok'    => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function rollback(Request $request): ResponseInterface
    {
        if (!$this->isAdmin()) {
            return new JsonResponse(['ok' => false, 'error' => 'Acesso restrito a administradores.'], 403);
        }

        $token = (string)($request->input('csrf_token') ?? '');
        if (!$this->session->validateCsrfToken($token)) {
            return new JsonResponse(['ok' => false, 'error' => 'Token CSRF inválido.'], 403);
        }

        $backupId = trim((string)($request->input('backup_id') ?? ''));
        if ($backupId !== '' && $this->backup !== null) {
            try {
                $scope = new BackupScope(UpdateKind::Core, 'core');
                foreach ($this->backup->list($scope) as $record) {
                    if ($record->id === $backupId) {
                        $this->backup->restore($record);
                        if ($record->dumpPath !== null && $this->databaseBackup !== null) {
                            $this->databaseBackup->restore($record->dumpPath);
                        }
                        return new JsonResponse([
                            'ok'      => true,
                            'message' => sprintf('Backup %s restaurado com sucesso.', $record->id),
                        ]);
                    }
                }
                return new JsonResponse(['ok' => false, 'error' => 'Backup não encontrado.'], 404);
            } catch (Throwable $e) {
                return new JsonResponse(['ok' => false, 'error' => $e->getMessage()], 500);
            }
        }

        if ($this->coreUpdater === null) {
            return new JsonResponse(['ok' => false, 'error' => 'Orquestrador CoreUpdater não disponível.'], 500);
        }

        $target = $this->targetResolver->core();
        $currentState = $this->stateStore->load($target);
        $updateId = trim((string)($request->input('update_id') ?? $currentState->updateId));

        try {
            $state = $this->coreUpdater->rollback($updateId);
            return new JsonResponse([
                'ok'    => $state->step === UpdateStep::RolledBack,
                'state' => $state->toArray(),
            ]);
        } catch (Throwable $e) {
            return new JsonResponse([
                'ok'    => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function pruneBackups(Request $request): ResponseInterface
    {
        if (!$this->isAdmin()) {
            return new JsonResponse(['ok' => false, 'error' => 'Acesso restrito a administradores.'], 403);
        }

        $token = (string)($request->input('csrf_token') ?? '');
        if (!$this->session->validateCsrfToken($token)) {
            return new JsonResponse(['ok' => false, 'error' => 'Token CSRF inválido.'], 403);
        }

        if ($this->backup === null) {
            return new JsonResponse(['ok' => false, 'error' => 'Serviço de backup não configurado.'], 500);
        }

        $removed = $this->backup->prune(new BackupScope(UpdateKind::Core, 'core'), 2);

        return new JsonResponse([
            'ok'      => true,
            'removed' => $removed,
        ]);
    }

    public function health(Request $request): ResponseInterface
    {
        if (!$this->isAdmin()) {
            return new JsonResponse(['ok' => false, 'error' => 'Acesso restrito a administradores.'], 403);
        }

        if ($this->healthCheck === null) {
            return new JsonResponse(['ok' => false, 'error' => 'Serviço HealthCheck não configurado.'], 500);
        }

        $target = $this->targetResolver->core();
        $state = $this->stateStore->load($target);

        $requestedId = trim((string)($request->input('update_id') ?? ''));
        if ($requestedId !== '' && $state->updateId !== $requestedId) {
            return new JsonResponse(['ok' => false, 'error' => 'update_id inválido ou divergente do estado ativo.'], 400);
        }

        $report = $this->healthCheck->run($target);

        $context = $state->context;
        $context['health_report'] = $report->toArray();
        unset($context['awaiting']);

        $updatedState = new UpdateState(
            updateId: $state->updateId,
            target: $state->target,
            step: $state->step,
            release: $state->release,
            etag: $state->etag,
            lastCheckAt: $state->lastCheckAt,
            context: $context,
            error: $state->error,
            updatedAt: gmdate('c'),
        );
        $this->stateStore->save($updatedState);

        return new JsonResponse([
            'ok'     => $report->ok,
            'report' => $report->toArray(),
            'state'  => $updatedState->toArray(),
        ]);
    }

    private function isAdmin(): bool
    {
        $role = strtolower((string)$this->session->get('user_role', 'admin'));
        return $role === 'admin';
    }
}
