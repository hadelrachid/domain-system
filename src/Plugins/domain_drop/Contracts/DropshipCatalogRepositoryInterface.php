<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Contracts;

use DomainSystem\Plugins\domain_drop\DTO\DropshipProductDto;

/**
 * Contrato de persistência para o catálogo de produtos minerados do DomainDrop (DIP).
 */
interface DropshipCatalogRepositoryInterface
{
    /**
     * Salva ou atualiza um produto minerado no repositório.
     * Retorna o ID do registro no banco.
     */
    public function save(DropshipProductDto $product): int;

    /**
     * Localiza um produto pelo ID interno.
     */
    public function findById(int $id): ?DropshipProductDto;

    /**
     * Localiza um produto pelo ID original do AliExpress.
     */
    public function findByAliExpressId(string $aliexpressId): ?DropshipProductDto;

    /**
     * Localiza um produto pelo slug amigável da URL.
     */
    public function findBySlug(string $slug): ?DropshipProductDto;

    /**
     * Lista produtos minerados com paginação e filtros.
     * 
     * @param array<string, mixed> $criteria
     * @return array<int, DropshipProductDto>
     */
    public function list(array $criteria = [], int $limit = 20, int $offset = 0): array;

    /**
     * Retorna a quantidade total de produtos minerados.
     */
    public function count(array $criteria = []): int;

    /**
     * Remove um produto pelo ID.
     */
    public function delete(int $id): bool;

    /**
     * Atualiza o preço de venda (BRL) e opcionalmente o custo (USD) de um produto.
     */
    public function updatePrice(int $id, float $salePriceBrl, ?float $costPriceUsd = null): bool;

    /**
     * Retorna lista de categorias únicas disponíveis no catálogo.
     * @return array<int, string>
     */
    public function getCategories(): array;
}
