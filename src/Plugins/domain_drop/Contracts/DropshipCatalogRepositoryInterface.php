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
     * Atualiza o status de publicação de um produto (ex: 'published', 'draft', 'quarantine').
     */
    public function updateStatus(int $id, string $status): bool;

    /**
     * Retorna lista de categorias únicas disponíveis no catálogo.
     * @return array<int, string>
     */
    public function getCategories(): array;

    /**
     * Limpa o catálogo de produtos de forma segura.
     * Se $preserveOrdered for true, preserva produtos vinculados a pedidos de clientes.
     * Retorna o número de produtos removidos.
     */
    public function clearAll(bool $preserveOrdered = true): int;

    /**
     * Exclui apenas produtos que estão em rascunho / despublicados (status != 'published').
     * Retorna o número de produtos removidos.
     */
    public function purgeDrafts(): int;

    /**
     * Publica todos os produtos em rascunho, tornando-os disponíveis para venda.
     * Retorna o número de produtos atualizados.
     */
    public function publishAll(): int;

    /**
     * Localiza ofertas similares / outros vendedores para o mesmo tipo de produto ou categoria,
     * ordenados pelo preço mais acessível (ascendente) para exibição comparativa.
     * 
     * @return array<int, DropshipProductDto>
     */
    public function findSimilarOffers(int $excludeId, string $category, string $title = '', int $limit = 4): array;
}
