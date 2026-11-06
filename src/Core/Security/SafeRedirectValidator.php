<?php

declare(strict_types=1);

namespace DomainSystem\Core\Security;

use DomainSystem\Core\Contracts\SafeRedirectValidatorInterface;

/**
 * Validador e Sanitizador Oficial de Redirecionamentos HTTP.
 * Previne ataques de Open Redirect (CWE-601), Phishing por redirecionamento e CRLF injection.
 */
class SafeRedirectValidator implements SafeRedirectValidatorInterface
{
    public function sanitize(string $url, string $fallbackUrl = '/'): string
    {
        $url = trim($url);
        if ($url === '') {
            return $fallbackUrl;
        }

        // Impede CRLF Injection que possa manipular cabeçalhos HTTP
        if (str_contains($url, "\r") || str_contains($url, "\n")) {
            return $fallbackUrl;
        }

        // Rejeita esquemas perigosos para navegadores
        if (preg_match('#^(javascript|data|vbscript|file):#i', $url)) {
            return $fallbackUrl;
        }

        // Rejeita esquema relativo de protocolo: //evil.com ou barras invertidas \\evil.com
        if (str_starts_with($url, '//') || str_starts_with($url, '\\\\') || str_starts_with($url, '/\\')) {
            return $fallbackUrl;
        }

        // Se for URL absoluta (http:// ou https://)
        if (preg_match('#^https?://#i', $url)) {
            $parsed = parse_url($url);
            $targetHost = strtolower((string)($parsed['host'] ?? ''));
            $currentHost = strtolower((string)($_SERVER['HTTP_HOST'] ?? 'localhost'));

            // Remove porta de $currentHost se houver para comparação limpa
            $currentHostWithoutPort = explode(':', $currentHost)[0];
            $targetHostWithoutPort = explode(':', $targetHost)[0];

            if ($targetHostWithoutPort !== $currentHostWithoutPort && $targetHost !== 'localhost' && $targetHost !== '127.0.0.1') {
                return $fallbackUrl;
            }

            return $url;
        }

        // Se for URL relativa começando com barra simples '/', é segura
        if (str_starts_with($url, '/')) {
            return $url;
        }

        return $fallbackUrl;
    }

    public function isSafe(string $url): bool
    {
        $sanitized = $this->sanitize($url, '');
        return $sanitized !== '';
    }
}
