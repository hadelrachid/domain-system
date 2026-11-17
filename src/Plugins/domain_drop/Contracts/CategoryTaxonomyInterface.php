<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Contracts;

/**
 * Contrato de Taxonomia e Classificação Semântica de Categorias (SOLID / DIP).
 * 
 * Desacopla a taxonomia de categorias, sinônimos, classificação de anúncios
 * e construção de filtros SQL inteligentes de qualquer repositório ou scraper específico.
 */
interface CategoryTaxonomyInterface
{
    /**
     * Retorna a lista canônica de todas as categorias suportadas na plataforma.
     * 
     * @return array<int, string>
     */
    public function getCanonicalCategories(): array;

    /**
     * Normaliza um nome de categoria livre (singular, plural, acentos, abreviações)
     * para o nome canônico oficial da plataforma.
     */
    public function normalizeCategory(string $category): string;

    /**
     * Infere a categoria canônica apropriada a partir do título do produto,
     * palavras-chave e/ou categoria bruta do fornecedor.
     */
    public function classifyFromText(string $title, ?string $rawCategory = null): string;

    /**
     * Constrói cláusula SQL inteligente para filtragem no catálogo, combinando
     * correspondência exata de categoria, sinônimos e palavras-chave de título.
     * 
     * @param array<int, mixed> $params Parâmetros PDO passados por referência
     */
    public function buildSqlFilter(string $requestedCategory, array &$params): string;
}
