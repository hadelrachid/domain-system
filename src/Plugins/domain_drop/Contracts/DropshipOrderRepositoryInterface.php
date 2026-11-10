<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Contracts;

/**
 * Contrato de Persistência de Pedidos de Dropshipping (DIP / SOLID).
 */
interface DropshipOrderRepositoryInterface
{
    /**
     * Cria um novo registro de pedido de dropshipping.
     * 
     * @param array<string, mixed> $data
     * @return int ID da ordem criada
     */
    public function create(array $data): int;

    /**
     * Localiza uma ordem pela referência oficial (order_ref).
     * 
     * @param string $orderRef
     * @return array<string, mixed>|null
     */
    public function findByOrderRef(string $orderRef): ?array;

    /**
     * Atualiza o status e/ou código de rastreamento do AliExpress.
     * 
     * @param string $orderRef
     * @param string $status
     * @param string|null $aliExpressOrderId
     * @param string|null $trackingCode
     * @return bool
     */
    public function updateFulfillment(string $orderRef, string $status, ?string $aliExpressOrderId = null, ?string $trackingCode = null): bool;

    /**
     * Lista pedidos de dropshipping com paginação e filtros.
     * 
     * @param array<string, mixed> $filters
     * @param int $limit
     * @param int $offset
     * @return array<int, array<string, mixed>>
     */
    public function list(array $filters = [], int $limit = 50, int $offset = 0): array;
}
