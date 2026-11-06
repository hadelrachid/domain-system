<?php

declare(strict_types=1);

namespace DomainSystem\Core\Http;

use DomainSystem\Core\Contracts\ResponseInterface;
use DomainSystem\Core\Contracts\RouteBoundaryGuardInterface;
use DomainSystem\Core\Http\Responses\ViewResponse;

/**
 * Implementação padrão do Guarda de Fronteira de Rotas.
 * Garante que nenhuma rota fora de /admin jamais receba layout do painel administrativo.
 */
class RouteBoundaryGuard implements RouteBoundaryGuardInterface
{
    public function isAdminRoute(string $uri): bool
    {
        $path = parse_url($uri, PHP_URL_PATH) ?? $uri;
        $clean = '/' . ltrim((string)$path, '/');

        // Rotas administrativas iniciam estritamente com /admin
        if (!str_starts_with($clean, '/admin')) {
            return false;
        }

        // Exceções conhecidas no admin que rodam raw/isoladas
        if (str_starts_with($clean, '/admin/themes/preview')) {
            return false;
        }

        return true;
    }

    public function canWrapInAdminWorkspace(string $uri, ResponseInterface $response): bool
    {
        // Se não for estritamente rota administrativa, NUNCA permite envelopamento
        if (!$this->isAdminRoute($uri)) {
            return false;
        }

        if ($response instanceof ViewResponse) {
            return $response->shouldWrapLayout();
        }

        return false;
    }
}
