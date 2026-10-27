<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Contracts;

/**
 * Contrato para resolução agnóstica de URLs públicas do sistema (SOLID - DIP).
 */
interface PublicUrlResolverInterface
{
    /**
     * Retorna a URL base pública sem barra no final (ex: "/domain-system/public" ou "").
     */
    public function getBaseUrl(): string;

    /**
     * Resolve um caminho relativo para uma URL pública completa.
     */
    public function resolve(string $path = ''): string;
}
