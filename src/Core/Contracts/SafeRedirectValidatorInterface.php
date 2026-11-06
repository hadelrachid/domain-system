<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts;

/**
 * Contrato para Validação e Sanitização de Redirecionamentos (Prevenção de Open Redirect CWE-601).
 */
interface SafeRedirectValidatorInterface
{
    /**
     * Sanitiza a URL de destino, garantindo que o redirecionamento aponte estritamente
     * para o mesmo domínio e caminhos seguros locais.
     */
    public function sanitize(string $url, string $fallbackUrl = '/'): string;

    /**
     * Retorna se a URL é segura para redirecionamento.
     */
    public function isSafe(string $url): bool;
}
