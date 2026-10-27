<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Support;

use DomainSystem\Core\Theme\Parts\Contracts\PublicUrlResolverInterface;

/**
 * Resolvedor padrão de URLs públicas baseado na configuração de ambiente / BASE_URL.
 */
class BaseUrlResolver implements PublicUrlResolverInterface
{
    private ?string $explicitBaseUrl;

    public function __construct(?string $explicitBaseUrl = null)
    {
        $this->explicitBaseUrl = $explicitBaseUrl !== null ? rtrim($explicitBaseUrl, '/') : null;
    }

    public function getBaseUrl(): string
    {
        if ($this->explicitBaseUrl !== null) {
            return $this->explicitBaseUrl;
        }

        return defined('BASE_URL') ? rtrim((string)BASE_URL, '/') : '';
    }

    public function resolve(string $path = ''): string
    {
        $trimmed = trim($path);
        if ($trimmed === '') {
            $base = $this->getBaseUrl();
            return $base !== '' ? $base . '/' : '/';
        }

        // URLs absolutas ou âncoras permanecem intactas
        if (
            str_starts_with($trimmed, 'http://') ||
            str_starts_with($trimmed, 'https://') ||
            str_starts_with($trimmed, '//') ||
            str_starts_with($trimmed, '#') ||
            str_starts_with($trimmed, 'mailto:') ||
            str_starts_with($trimmed, 'tel:')
        ) {
            return $trimmed;
        }

        $base = $this->getBaseUrl();
        if ($base !== '' && str_starts_with($trimmed, $base . '/')) {
            return $trimmed;
        }

        return $base . '/' . ltrim($trimmed, '/');
    }
}
