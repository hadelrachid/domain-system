<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Contracts;

/**
 * Contrato de Sanitização Segura de HTML inline para blocos tipográficos (SOLID - SRP / DIP).
 */
interface HtmlSanitizerInterface
{
    /**
     * Sanitiza HTML inline preservando apenas tags seguras permitidas e removendo
     * vetores XSS (atributos de evento on*, javascript:, data:, style malicioso).
     */
    public function sanitizeInlineHtml(string $html): string;

    /**
     * Valida e higieniza uma URL contra protocolos maliciosos (javascript:, vbscript:, data:).
     * Retorna o fallback seguro se a URL contiver um esquema proibido.
     */
    public function sanitizeUrl(string $url, string $fallback = '#'): string;
}
