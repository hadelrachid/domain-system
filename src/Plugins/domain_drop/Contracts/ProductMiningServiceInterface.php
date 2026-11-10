<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Contracts;

use DomainSystem\Plugins\domain_drop\DTO\DropshipProductDto;

/**
 * Contrato do Orquestrador de Mineração de Produtos (SRP / DIP).
 */
interface ProductMiningServiceInterface
{
    /**
     * Minera produtos em alta no AliExpress, executa a conversão cambial,
     * enriquece títulos e descrições com I.A. e retorna os DTOs processados.
     * 
     * @param string $category Categoria ou palavra-chave de busca
     * @param int $limit Quantidade de produtos para minerar
     * @param bool $autoPersist Se deve persistir automaticamente no catálogo
     * @return array<int, DropshipProductDto>
     */
    public function mineTrendingProducts(string $category = 'Eletrônicos', int $limit = 10, bool $autoPersist = true): array;

    /**
     * Importa um produto específico através da URL ou ID do AliExpress.
     * 
     * @param string|int $productIdOrUrl
     * @return DropshipProductDto
     */
    public function importSingleProduct(string|int $productIdOrUrl): DropshipProductDto;
}
