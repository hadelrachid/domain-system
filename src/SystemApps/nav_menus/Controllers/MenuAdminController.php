<?php

declare(strict_types=1);

namespace DomainSystem\SystemApps\nav_menus\Controllers;

use DomainSystem\Core\Contracts\ThemeManagerInterface;
use DomainSystem\Core\Contracts\ResponseInterface;
use DomainSystem\Core\Contracts\PageLookupInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Http\Responses\ViewResponse;
use DomainSystem\SystemApps\nav_menus\Contracts\MenuRepositoryInterface;
use DomainSystem\SystemApps\nav_menus\Services\MenuRenderer;

class MenuAdminController
{
    private MenuRepositoryInterface $menuRepo;
    private ThemeManagerInterface $theme;
    private ?PageLookupInterface $pageLookup;
    private ?MenuRenderer $menuRenderer;

    public function __construct(
        MenuRepositoryInterface $menuRepo,
        ThemeManagerInterface $theme,
        ?PageLookupInterface $pageLookup = null,
        ?MenuRenderer $menuRenderer = null
    ) {
        $this->menuRepo = $menuRepo;
        $this->theme = $theme;
        $this->pageLookup = $pageLookup;
        $this->menuRenderer = $menuRenderer ?? new MenuRenderer($menuRepo, $pageLookup);
    }

    public function index(Request $request): ResponseInterface
    {
        $menus = $this->menuRepo->getMenus();

        $pages = $this->pageLookup !== null ? $this->pageLookup->getSelectablePages() : [];

        // Injeta a Home (Início) no topo para facilitar seleção pelo usuário
        array_unshift($pages, [
            'id' => null,
            'title' => 'Início (Home)',
            'slug' => '/',
            'is_home' => true
        ]);

        $activeMenuIdRaw = $request->input('menu_id');

        if ($activeMenuIdRaw === 'new') {
            $activeMenuId = null;
        } else {
            $activeMenuId = $activeMenuIdRaw ? (int)$activeMenuIdRaw : ($menus[0]['id'] ?? null);
        }

        $menuItems = [];
        $activeMenu = null;
        if ($activeMenuId !== null) {
            $activeMenu = $this->menuRepo->getMenuById((int)$activeMenuId);
            if ($activeMenu !== null) {
                $menuItems = $this->menuRepo->getMenuItems((int)$activeMenuId);
            }
        }

        $html = $this->theme->render('admin_menus', [
            'menus' => $menus,
            'pages' => $pages,
            'activeMenuId' => $activeMenuId,
            'activeMenu' => $activeMenu,
            'menuItems' => $menuItems
        ], dirname(__DIR__) . '/views');

        return new ViewResponse($html);
    }

    public function storeMenu(Request $request): ResponseInterface
    {
        $name = (string)$request->input('name', '');
        $location = (string)$request->input('location', '');
        $id = $request->input('id') ? (int)$request->input('id') : null;

        if (empty($name) || empty($location)) {
            return Response::json(['success' => false, 'message' => 'Nome e Localização são obrigatórios.'], 400);
        }

        $result = $this->menuRepo->saveMenu($name, $location, $id);
        $status = $result['success'] ? 200 : 400;

        return Response::json($result, $status);
    }

    public function storeItems(Request $request): ResponseInterface
    {
        $menuId = $request->input('menu_id');
        $items = $request->input('items', []);

        if (!$menuId) {
            return Response::json(['success' => false, 'message' => 'ID do menu não informado.'], 400);
        }

        $success = $this->menuRepo->replaceMenuItems((int)$menuId, (array)$items);

        if ($success) {
            return Response::json(['success' => true, 'message' => 'Estrutura do menu salva com sucesso!']);
        }

        return Response::json(['success' => false, 'message' => 'Erro ao salvar a estrutura do menu.'], 500);
    }

    public function deleteMenu(Request $request, string $id): ResponseInterface
    {
        $this->menuRepo->deleteMenu((int)$id);
        return Response::json(['success' => true, 'message' => 'Menu excluído com sucesso.']);
    }

    /**
     * Mantido para compatibilidade com eventuais chamadas diretas legadas.
     */
    public function renderNavMenu(string $location): string
    {
        return $this->menuRenderer->render($location);
    }
}
