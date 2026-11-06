<?php

declare(strict_types=1);

namespace DomainSystem\SystemApps\nav_menus\Services;

use DomainSystem\Core\Contracts\PageLookupInterface;
use DomainSystem\Core\Theme\Parts\Contracts\MenuTreeBuilderInterface;
use DomainSystem\Core\Theme\Parts\Support\MenuTreeBuilder;
use DomainSystem\SystemApps\nav_menus\Contracts\MenuRepositoryInterface;
use Throwable;

class MenuRenderer
{
    private MenuRepositoryInterface $menuRepo;
    private ?PageLookupInterface $pageLookup;
    private MenuTreeBuilderInterface $treeBuilder;

    public function __construct(
        MenuRepositoryInterface $menuRepo,
        ?PageLookupInterface $pageLookup = null,
        ?MenuTreeBuilderInterface $treeBuilder = null
    ) {
        $this->menuRepo = $menuRepo;
        $this->pageLookup = $pageLookup;
        $this->treeBuilder = $treeBuilder ?? new MenuTreeBuilder();
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
            $currentUri = (string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

            $tree = $this->treeBuilder->buildTree($items);

            return '<ul class="nav-links" style="list-style: none; gap: 30px; display: flex; flex-direction: inherit; margin: 0; padding: 0;">'
                . $this->renderTreeNodes($tree, $pagesById, $baseUrl, $currentUri, 0)
                . '</ul>';
        } catch (Throwable $t) {
            return '';
        }
    }

    /**
     * @param array<int, array<string, mixed>> $nodes
     * @param array<int, string> $pagesById
     */
    private function renderTreeNodes(array $nodes, array $pagesById, string $baseUrl, string $currentUri, int $level): string
    {
        $html = '';
        foreach ($nodes as $item) {
            $pageId = !empty($item['page_id']) ? (int)$item['page_id'] : null;

            if ($pageId !== null && isset($pagesById[$pageId])) {
                $pageSlug = $pagesById[$pageId];
                $url = $baseUrl . '/' . ltrim($pageSlug, '/');
            } else {
                $rawUrl = (string)($item['url'] ?? '');
                if (str_starts_with($rawUrl, 'http://') || str_starts_with($rawUrl, 'https://') || str_starts_with($rawUrl, '#')) {
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
            $targetAttr = (($item['target'] ?? '_self') === '_blank') ? ' target="_blank" rel="noopener noreferrer"' : '';
            $children = !empty($item['children']) && is_array($item['children']) ? $item['children'] : [];
            $hasChildrenClass = !empty($children) ? ' menu-item-has-children' : '';
            $extraClasses = !empty($item['css_classes']) ? ' ' . htmlspecialchars((string)$item['css_classes'], ENT_QUOTES, 'UTF-8') : '';

            $html .= '<li class="menu-item' . $hasChildrenClass . $extraClasses . '" style="position: relative;">';
            $chevron = !empty($children) ? ' <span style="font-size:0.7em; opacity:0.75; margin-left:4px;">▾</span>' : '';
            $html .= '<a href="' . $safeUrl . '"' . $targetAttr . ' class="nav-link" style="' . $isActive . '">' . $safeTitle . $chevron . '</a>';

            if (!empty($children)) {
                $html .= '<ul class="sub-menu">'
                    . $this->renderTreeNodes($children, $pagesById, $baseUrl, $currentUri, $level + 1)
                    . '</ul>';
            }

            $html .= '</li>';
        }

        return $html;
    }
}
