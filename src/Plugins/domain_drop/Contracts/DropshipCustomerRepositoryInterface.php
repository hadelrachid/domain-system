<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Contracts;

/**
 * Contrato de persistência de clientes finais da loja DomainDrop / RachidD.com.
 */
interface DropshipCustomerRepositoryInterface
{
    public function create(array $data): int;

    public function findById(int $id): ?array;

    public function findByEmail(string $email): ?array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAll(int $limit = 100, int $offset = 0): array;

    /**
     * Lista paginada com filtro de busca e status para o painel administrativo.
     *
     * @return array{items: array<int, array<string, mixed>>, total: int}
     */
    public function listCustomers(int $page = 1, int $perPage = 15, ?string $search = null, ?string $status = null): array;

    /**
     * @return array{total_customers: int, active_customers: int, suspended_customers: int, linked_orders: int}
     */
    public function getCustomerMetrics(): array;

    public function updateStatus(int $id, string $status): bool;
}
