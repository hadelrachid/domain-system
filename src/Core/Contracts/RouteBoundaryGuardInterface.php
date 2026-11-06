<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts;

/**
 * Contrato de Guarda de Fronteira de Rotas e Isolamento de Workspaces (DIP / SOLID).
 * Impede categoricamente que rotas públicas, de alunos ou de parceiros sejam
 * acidentalmente envelopadas pela casca (shell) ou painel de administração.
 */
interface RouteBoundaryGuardInterface
{
    /**
     * Verifica se a URI pertence estritamente à fronteira administrativa.
     */
    public function isAdminRoute(string $uri): bool;

    /**
     * Verifica se a resposta HTTP tem permissão explícita para ser envelopada pelo workspace do admin.
     */
    public function canWrapInAdminWorkspace(string $uri, ResponseInterface $response): bool;
}
