<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Contracts;

/**
 * Contrato de persistência e relatórios de demanda (ISP / DIP).
 */
interface DemandMetricsRepositoryInterface
{
    /**
     * Incrementa atomicamente a métrica correspondente no período YYYY-MM
     * e registra a trilha de auditoria identificando o usuário (logado ou externo).
     */
    public function incrementMetric(
        string $productId,
        string $productTitle,
        string $categorySlug,
        string $actionType,
        ?\DomainSystem\Plugins\domain_drop\DTO\DemandEvent $event = null
    ): void;

    /**
     * Retorna os últimos acessos e cliques identificados com perfil do usuário (logado vs externo).
     * @return array<int, array<string, mixed>>
     */
    public function getRecentActivityLogs(int $limit = 25): array;

    /**
     * Retorna métricas ao vivo / tempo real (ex: hoje / últimas 24h).
     * @return array<string, mixed>
     */
    public function getLivePulseMetrics(): array;

    /**
     * Retorna os produtos mais quentes do período mensal especificado (Livro de Presença).
     * @return array<int, array<string, mixed>>
     */
    public function getMonthlyRankings(string $yearMonth, int $limit = 20): array;

    /**
     * Agrupa o interesse acumulado por categoria para compor o perfil do comprador.
     * @return array<string, int>
     */
    public function getAudienceCategoryProfile(string $yearMonth): array;
}
