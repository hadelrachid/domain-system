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
     * Retorna os itens de um menu por local ou ID.
     *
     * @param string $location Local do menu (ex: 'header', 'footer').
     * @param int|null $menuId ID específico do menu (se houver).
     * @return array<int, array{title: string, url?: ?string}>
     */
    public function getMenuItems(string $location = 'header', ?int $menuId = null): array;
}
