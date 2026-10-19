<?php

declare(strict_types=1);

namespace DomainSystem\Core\Http;

use DomainSystem\Core\Contracts\SiteStateInterface;

/**
 * Política de verificação e normalização de rotas para Modo de Manutenção (N-6).
 *
 * Previne bypass por query string (/?x=/login) ou falso prefixo (/blog/admin-dicas)
 * e integra o lock físico (.maintenance) com o SiteStateInterface.
 */
class MaintenancePolicy
{
    /**
     * Extrai e normaliza apenas o caminho (PHP_URL_PATH) da URI, removendo
     * subpastas de instalação (ex: /domain-system ou /domain-system/public) em limites de segmento.
     */
    public static function normalizePath(string $requestUri, string $baseFolder = '', string $scriptName = ''): string
    {
        $rawPath = parse_url($requestUri, PHP_URL_PATH);
        $path = is_string($rawPath) && $rawPath !== '' ? $rawPath : '/';
        $path = str_replace('\\', '/', $path);

        $scriptName = rtrim(str_replace('\\', '/', $scriptName), '/');
        if ($scriptName !== '' && $scriptName !== '/') {
            if ($path === $scriptName) {
                $path = '/';
            } elseif (str_starts_with($path, $scriptName . '/')) {
                $path = substr($path, strlen($scriptName));
            }
        }

        $baseFolder = rtrim(str_replace('\\', '/', $baseFolder), '/');
        if ($baseFolder !== '' && $baseFolder !== '/') {
            if ($path === $baseFolder) {
                $path = '/';
            } elseif (str_starts_with($path, $baseFolder . '/')) {
                $path = substr($path, strlen($baseFolder));
            }
        }

        if ($path === '' || $path[0] !== '/') {
            $path = '/' . ltrim($path, '/');
        }

        return $path;
    }

    /**
     * Verifica se o caminho normalizado pertence às rotas críticas isentas de bloqueio de manutenção.
     */
    public static function isExemptPath(string $normalizedPath): bool
    {
        return preg_match('#^/(admin|login|logout|api|setup)(/|$)#', $normalizedPath) === 1;
    }

    /**
     * Determina se a requisição deve receber 503 Service Unavailable.
     */
    public static function shouldBlock(
        string $normalizedPath,
        bool $maintenanceLockExists,
        ?SiteStateInterface $siteState = null,
        ?string $userRole = null
    ): bool {
        $isMaintenanceActive = $maintenanceLockExists || ($siteState !== null && $siteState->isMaintenance());
        if (!$isMaintenanceActive) {
            return false;
        }

        if ($userRole === 'admin') {
            return false;
        }

        return !self::isExemptPath($normalizedPath);
    }
}
