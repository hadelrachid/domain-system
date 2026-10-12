<?php

declare(strict_types=1);

namespace DomainSystem\SystemApps\nav_menus\Contracts;

interface MenuRepositoryInterface
{
    /**
     * @return array<int, array{id: int, name: string, location: string, created_at?: string}>
     */
    public function getMenus(): array;

    /**
     * @return array{id: int, name: string, location: string, created_at?: string}|null
     */
    public function getMenuById(int $id): ?array;

    /**
     * @return array{id: int, name: string, location: string, created_at?: string}|null
     */
    public function getMenuByLocation(string $location): ?array;

    /**
     * @return array<int, array{id: int, menu_id: int, title: string, url: ?string, page_id: ?int, order_index: int}>
     */
    public function getMenuItems(int $menuId): array;

    /**
     * Salva ou atualiza um menu.
     * @return array{success: bool, message: string, id?: int}
     */
    public function saveMenu(string $name, string $location, ?int $id = null): array;

    /**
     * Substitui itens de um menu de forma estritamente transacional (com rollback em falhas).
     * @param array<int, array{title: string, url?: ?string, page_id?: ?int}> $items
     */
    public function replaceMenuItems(int $menuId, array $items): bool;

    /**
     * Exclui um menu e seus itens vinculados.
     */
    public function deleteMenu(int $id): bool;

    /**
     * Garante a existência das tabelas de menus e o auto-seed dos locais básicos ('header', 'footer').
     */
    public function ensureTables(): void;
}
