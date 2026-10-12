<?php

declare(strict_types=1);

namespace DomainSystem\Core\Http;

use DomainSystem\Core\Contracts\RequestInterface;
use DomainSystem\Core\Contracts\ResponseInterface;

/**
 * Política declarativa de cabeçalhos anti-cache HTTP.
 * Garante que rotas administrativas e logout nunca fiquem em cache de proxies/CDNs (Hostinger/Cloudflare).
 */
class CachePolicy
{
    private const SENSITIVE_PREFIXES = [
        '/admin',
        '/login',
        '/logout',
        '/setup',
        '/api',
        '/emergency',
    ];

    /**
     * Aplica os cabeçalhos apropriados à resposta antes do envio.
     */
    public function apply(RequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $uri = '/' . ltrim(strtok($request->uri(), '?'), '/');

        if ($this->isSensitivePath($uri) || $this->hasActiveSession()) {
            return $this->applyStrictNoCache($response, $uri);
        }

        return $response;
    }

    public function isSensitivePath(string $uri): bool
    {
        foreach (self::SENSITIVE_PREFIXES as $prefix) {
            if ($uri === $prefix || str_starts_with($uri, $prefix . '/')) {
                return true;
            }
        }
        return false;
    }

    private function applyStrictNoCache(ResponseInterface $response, string $uri): ResponseInterface
    {
        $headers = [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, private, max-age=0, post-check=0, pre-check=0',
            'Pragma'        => 'no-cache',
            'Expires'       => 'Thu, 01 Jan 1970 00:00:00 GMT',
            'Vary'          => 'Cookie, Authorization, Accept-Encoding',
        ];

        // No logout, compele o navegador a expurgar o cache local da aplicação
        if ($uri === '/logout') {
            $headers['Clear-Site-Data'] = '"cache", "storage"';
        }

        foreach ($headers as $name => $value) {
            if (method_exists($response, 'setHeader')) {
                $response->setHeader($name, $value);
            } elseif (method_exists($response, 'withHeader')) {
                $response = $response->withHeader($name, $value);
            }
        }

        return $response;
    }

    private function hasActiveSession(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['user_id']);
    }
}
