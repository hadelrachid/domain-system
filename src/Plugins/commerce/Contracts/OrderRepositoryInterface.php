<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\commerce\Contracts;

/**
 * Contrato de Repositório de Pedidos Financeiros (DIP / SOLID).
 */
interface OrderRepositoryInterface
{
    /**
     * Cria a tabela de pedidos de forma segura e idempotente.
     */
    public function ensureTable(): void;

    /**
     * Cria um novo pedido no banco de dados e retorna o ID inserido.
     *
     * @param array<string, mixed> $data
     */
    public function createOrder(array $data): int;

    /**
     * Busca um pedido pelo ID numérico.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $orderId): ?array;

    /**
     * Busca um pedido pelo código de referência único (ex: 'ORD_2026_ABCD12').
     *
     * @return array<string, mixed>|null
     */
    public function findByOrderRef(string $orderRef): ?array;

    /**
     * Atualiza o status do pedido (ex: 'paid', 'cancelled', 'refunded').
     */
    public function updateStatus(int $orderId, string $status, ?string $transactionId = null): bool;

    /**
     * Retorna o histórico de pedidos de um usuário/aluno.
     *
     * @return array<array<string, mixed>>
     */
    public function getUserOrders(int $userId): array;

    /**
     * Retorna a lista geral de pedidos para administração com filtro opcional por período (YYYY-MM).
     *
     * @return array<array<string, mixed>>
     */
    public function getAllOrders(int $limit = 100, int $offset = 0, ?string $period = null): array;

    /**
     * Retorna métricas financeiras consolidadas (faturamento total, pedidos pagos, etc.) com filtro opcional de período.
     *
     * @return array<string, mixed>
     */
    public function getMetrics(?string $period = null): array;

    /**
     * Retorna os períodos mensais (YYYY-MM) disponíveis com histórico de pedidos.
     *
     * @return array<string>
     */
    public function getAvailablePeriods(): array;

    /**
     * Retorna a linha do tempo de faturamento (dias do mês ou meses do ano) para plotagem de gráficos.
     *
     * @return array<string, mixed> ['labels' => [...], 'totals' => [...], 'counts' => [...], 'mode' => 'days'|'months']
     */
    public function getSalesTimeline(?string $period = null): array;

    /**
     * Retorna a distribuição de faturamento por método de pagamento (Pix, Cartão, Boleto, etc.).
     *
     * @return array<string, array<string, mixed>>
     */
    public function getPaymentMethodBreakdown(?string $period = null): array;

    /**
     * Retorna os pedidos filtrados para exportação contábil / balanço anual (CSV / Excel).
     *
     * @return array<array<string, mixed>>
     */
    public function getOrdersForExport(?string $period = null, ?string $year = null): array;
}
