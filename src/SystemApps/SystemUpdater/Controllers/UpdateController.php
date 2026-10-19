<?php

declare(strict_types=1);

namespace DomainSystem\SystemApps\SystemUpdater\Controllers;

use DomainSystem\Core\Contracts\NotificationManagerInterface;
use DomainSystem\Core\Contracts\ResponseInterface;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Contracts\ThemeManagerInterface;
use DomainSystem\Core\Contracts\Update\HealthCheckInterface;
use DomainSystem\Core\Contracts\Update\UpdateCheckerInterface;
use DomainSystem\Core\Contracts\Update\UpdateStateStoreInterface;
use DomainSystem\Core\Contracts\Update\UpdateTargetResolverInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Http\Responses\JsonResponse;
use DomainSystem\Core\Update\Service\UpdateNotifier;

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

        $checkedNow = $request !== null && (string)($request->input('checked') ?? '') === '1';

        $html = $this->theme->render('updates', [
            'target'       => $target,
            'check_result' => $checkResult,
            'csrf_token'   => $this->session->getCsrfToken(),
            'checked_now'  => $checkedNow,
            'base_url'     => defined('BASE_URL') ? rtrim((string)BASE_URL, '/') : '',
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

        $updatedState = new \DomainSystem\Core\Update\Value\UpdateState(
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
