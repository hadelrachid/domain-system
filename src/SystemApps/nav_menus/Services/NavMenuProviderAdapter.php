<?php

declare(strict_types=1);

namespace DomainSystem\SystemApps\nav_menus\Services;

use DomainSystem\Core\Contracts\PageLookupInterface;
use DomainSystem\Core\Theme\Parts\Contracts\MenuProviderInterface;
use DomainSystem\SystemApps\nav_menus\Contracts\MenuRepositoryInterface;

/**
 * Adaptador que conecta o repositório de nav_menus ao contrato Ring 0 MenuProviderInterface,
 * resolvendo URLs de páginas dinâmicas (PageLookupInterface) e preservando hierarquia de submenus.
 */
class NavMenuProviderAdapter implements MenuProviderInterface
{
    private MenuRepositoryInterface $repo;
    private ?PageLookupInterface $pageLookup;

    public function __construct(MenuRepositoryInterface $repo, ?PageLookupInterface $pageLookup = null)
    {
        $this->repo = $repo;
        $this->pageLookup = $pageLookup;
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
                $rawItems = $this->repo->getMenuItems((int)$menu['id']);
                return $this->hydratePageUrls($rawItems);
            }
        } catch (\Throwable $e) {}

        return [];
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private function hydratePageUrls(array $items): array
    {
        if (empty($items)) {
            return [];
        }

        $pagesById = [];
        if ($this->pageLookup !== null) {
            try {
                foreach ($this->pageLookup->getSelectablePages() as $p) {
                    if (isset($p['id'], $p['slug'])) {
                        $pagesById[(int)$p['id']] = (string)$p['slug'];
                    }
                }
            } catch (\Throwable $e) {}
        }

        $baseUrl = defined('BASE_URL') ? (string)BASE_URL : '';

        foreach ($items as &$item) {
            $pageId = !empty($item['page_id']) ? (int)$item['page_id'] : null;
            $rawUrl = (string)($item['url'] ?? '');

            if ($pageId !== null && isset($pagesById[$pageId]) && ($rawUrl === '' || $rawUrl === '#')) {
                $slug = ltrim($pagesById[$pageId], '/');
                $item['url'] = $baseUrl . '/' . $slug;
            } elseif ($rawUrl !== '' && !str_starts_with($rawUrl, 'http://') && !str_starts_with($rawUrl, 'https://') && !str_starts_with($rawUrl, '#') && !str_starts_with($rawUrl, 'javascript:')) {
                if ($rawUrl === '/') {
                    $item['url'] = $baseUrl !== '' ? $baseUrl . '/' : '/';
                } elseif ($baseUrl !== '' && !str_starts_with($rawUrl, $baseUrl)) {
                    $item['url'] = $baseUrl . '/' . ltrim($rawUrl, '/');
                }
            }
        }
        unset($item);

        return $items;
    }
}
