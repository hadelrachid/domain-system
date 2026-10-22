<?php

declare(strict_types=1);

namespace DomainSystem\SystemApps\nav_menus\Services;

use DomainSystem\Core\Theme\Parts\Contracts\MenuProviderInterface;
use DomainSystem\SystemApps\nav_menus\Contracts\MenuRepositoryInterface;

/**
 * Adaptador que conecta o repositório de nav_menus ao contrato Ring 0 MenuProviderInterface.
 */
class NavMenuProviderAdapter implements MenuProviderInterface
{
    private MenuRepositoryInterface $repo;

    public function __construct(MenuRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function getMenus(): array
    {
        try {
            return $this->repo->getMenus();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function getMenuItems(string $location = 'header', ?int $menuId = null): array
    {
        try {
            $menu = null;
            if ($menuId !== null && $menuId > 0) {
                $menu = $this->repo->getMenuById($menuId);
            }
            if ($menu === null && $location !== '') {
                $menu = $this->repo->getMenuByLocation($location);
            }
            if ($menu !== null && isset($menu['id'])) {
                return $this->repo->getMenuItems((int)$menu['id']);
            }
        } catch (\Throwable $e) {}

        return [];
    }
}
