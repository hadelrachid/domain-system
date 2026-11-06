<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Contracts;

/**
 * Contrato Ring 0 para Provedor de Itens de Menu (DIP - Desacoplamento de SystemApps).
 */
interface MenuProviderInterface
{
    /**
     * Retorna a lista de menus cadastrados no sistema.
     *
     * @return array<int, array{id: int, name: string, location?: string}>
     */
    public function getMenus(): array;

    /**
     * Retorna os itens de um menu por local ou ID (suportando hierarquia de submenus via parent_id, depth ou children).
     *
     * @param string $location Local do menu (ex: 'header', 'footer').
     * @param int|null $menuId ID específico do menu (se houver).
     * @return array<int, array{id?: int, parent_id?: ?int, depth?: int, title: string, url?: ?string, target?: string, css_classes?: string, children?: array}>
     */
    public function getMenuItems(string $location = 'header', ?int $menuId = null): array;
}
