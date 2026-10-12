<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts;

/**
 * Contrato de busca de páginas selecionáveis para navegação/menus.
 * Desacopla o plugin nav_menus de consultas SQL diretas à tabela 'pages'.
 */
interface PageLookupInterface
{
    /**
     * Retorna a lista de páginas públicas disponíveis para seleção em menus.
     *
     * @return array<int, array{id: int|string, title: string, slug: string}>
     */
    public function getSelectablePages(): array;
}
