<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Contracts;

/**
 * Contrato SOLID (inspirado no Walker_Nav_Menu do WordPress) para construção
 * e normalização de árvores hierárquicas de Menus e Submenus.
 */
interface MenuTreeBuilderInterface
{
    /**
     * Converte uma lista plana de itens de menu (com parent_id e/ou depth)
     * ou uma lista já aninhada em uma árvore hierárquica recursiva onde cada nó
     * contém a chave 'children' => array.
     *
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    public function buildTree(array $items): array;
}
