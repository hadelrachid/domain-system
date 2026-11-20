<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\seo\Contracts;

/**
 * Contrato oficial para auditorias de performance e Core Web Vitals (SOLID / DIP).
 */
interface PageSpeedAuditInterface
{
    /**
     * Executa a auditoria de performance para uma URL e estratégia específica.
     *
     * @param string $url URL pública (ex: https://rachidd.com) ou interna.
     * @param string $strategy 'mobile' ou 'desktop'.
     * @return array{
     *     url: string,
     *     strategy: string,
     *     score: int,
     *     source: string,
     *     lcp: string,
     *     cls: string,
     *     inp: string,
     *     fcp: string,
     *     ttfb: string,
     *     metrics: array<string, mixed>,
     *     opportunities: array<int, array<string, mixed>>,
     *     ai_diagnostics: array<string, mixed>,
     *     raw_report_url: ?string,
     *     audited_at: string
     * }
     */
    public function audit(string $url, string $strategy = 'mobile'): array;

    /**
     * Retorna a URL oficial de análise web do Google PageSpeed Insights.
     */
    public function getPublicTestUrl(string $url, string $strategy = 'mobile'): string;
}
