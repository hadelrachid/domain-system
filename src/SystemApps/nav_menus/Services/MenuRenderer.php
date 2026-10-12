<?php

declare(strict_types=1);

namespace DomainSystem\SystemApps\nav_menus\Services;

use DomainSystem\SystemApps\nav_menus\Contracts\MenuRepositoryInterface;
use DomainSystem\Core\Contracts\PageLookupInterface;
use Throwable;

class MenuRenderer
{
    private MenuRepositoryInterface $menuRepo;
    private ?PageLookupInterface $pageLookup;

    public function __construct(MenuRepositoryInterface $menuRepo, ?PageLookupInterface $pageLookup = null)
    {
        $this->menuRepo = $menuRepo;
        $this->pageLookup = $pageLookup;
    }

    public function render(string $location): string
    {
        try {
            $menu = $this->menuRepo->getMenuByLocation($location);
            if (!$menu) {
                return '';
            }

            $items = $this->menuRepo->getMenuItems((int)$menu['id']);
            if (empty($items)) {
                return '';
            }

            $pagesById = [];
            if ($this->pageLookup !== null) {
                $pages = $this->pageLookup->getSelectablePages();
                foreach ($pages as $p) {
                    $pagesById[(int)$p['id']] = (string)$p['slug'];
                }
            }

            $baseUrl = defined('BASE_URL') ? BASE_URL : '';
            $currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

            $html = '<ul style="list-style: none; gap: 30px; display: flex; flex-direction: inherit; margin: 0; padding: 0;">';
            foreach ($items as $item) {
                $pageId = !empty($item['page_id']) ? (int)$item['page_id'] : null;

                if ($pageId !== null && isset($pagesById[$pageId])) {
                    $pageSlug = $pagesById[$pageId];
                    $url = $baseUrl . '/' . ltrim($pageSlug, '/');
                } else {
                    $rawUrl = (string)($item['url'] ?? '');
                    if (str_starts_with($rawUrl, 'http://') || str_starts_with($rawUrl, 'https://')) {
                        $url = $rawUrl;
                    } elseif ($rawUrl === '/' || $rawUrl === '') {
                        $url = $baseUrl !== '' ? $baseUrl : '/';
                    } else {
                        $url = $baseUrl . '/' . ltrim($rawUrl, '/');
                    }
                }

                $path = parse_url($url, PHP_URL_PATH);
                $isActive = ($currentUri === $path) ? 'color: var(--primary);' : '';

                $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
                $safeTitle = htmlspecialchars((string)$item['title'], ENT_QUOTES, 'UTF-8');

                $html .= '<li><a href="' . $safeUrl . '" class="nav-link" style="' . $isActive . '">' . $safeTitle . '</a></li>';
            }
            $html .= '</ul>';

            return $html;
        } catch (Throwable $t) {
            return '';
        }
    }
}
