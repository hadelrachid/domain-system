<?php

namespace DomainSystem\Plugins\nav_menus\Controllers;

use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Plugins\Database\Connection;
use DomainSystem\Core\Theme\ThemeManager;

class MenuAdminController
{
    private $db;
    private $theme;

    public function __construct(Connection $conn, ThemeManager $theme)
    {
        $this->db = $conn->getPdo();
        $this->theme = $theme;
    }

    public function index(Request $request): Response
    {
                // Auto-seed: Garante que os menus padrão existam
        $stmt = $this->db->query("SELECT location FROM theme_menus");
        $existingLocations = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        
        if (!in_array('header', $existingLocations)) {
            $this->db->query("INSERT INTO theme_menus (name, location) VALUES ('Menu Principal', 'header')");
        }
        if (!in_array('footer', $existingLocations)) {
            $this->db->query("INSERT INTO theme_menus (name, location) VALUES ('Menu do Rodapé', 'footer')");
        }

        $menus = $this->db->query("SELECT * FROM theme_menus ORDER BY name ASC")->fetchAll();
        $pages = $this->db->query("SELECT id, title, slug FROM pages ORDER BY title ASC")->fetchAll();
        
        // Injete a "Home" / "Início" no topo da lista para facilitar
        array_unshift($pages, [
            'id' => null, // Não tem ID real
            'title' => 'Início (Home)',
            'slug' => '/',
            'is_home' => true
        ]);
        
        $activeMenuIdRaw = $request->input('menu_id');
        
        if ($activeMenuIdRaw === 'new') {
            $activeMenuId = null;
        } else {
            $activeMenuId = $activeMenuIdRaw ?: ($menus[0]['id'] ?? null);
        }
        
        $menuItems = [];
        $activeMenu = null;
        if ($activeMenuId) {
            $stmt = $this->db->prepare("SELECT * FROM theme_menus WHERE id = ?");
            $stmt->execute([$activeMenuId]);
            $activeMenu = $stmt->fetch();
            
            $stmt = $this->db->prepare("SELECT * FROM theme_menu_items WHERE menu_id = ? ORDER BY order_index ASC");
            $stmt->execute([$activeMenuId]);
            $menuItems = $stmt->fetchAll();
        }

        $html = $this->theme->render('admin_menus', [
            'menus' => $menus,
            'pages' => $pages,
            'activeMenuId' => $activeMenuId,
            'activeMenu' => $activeMenu,
            'menuItems' => $menuItems
        ], dirname(__DIR__) . '/views');
        return new Response($html);
    }

    public function storeMenu(Request $request): Response
    {
        $jsonBody = json_decode(file_get_contents('php://input'), true);
        $name = $jsonBody['name'] ?? $request->input('name');
        $location = $jsonBody['location'] ?? $request->input('location');
        $id = $jsonBody['id'] ?? $request->input('id');

        if (empty($name) || empty($location)) {
            return Response::json(['success' => false, 'message' => 'Nome e Localização são obrigatórios.'], 400);
        }

        if ($id) {
            $stmt = $this->db->prepare('UPDATE theme_menus SET name = ?, location = ? WHERE id = ?');
            $stmt->execute([$name, $location, $id]);
            return Response::json(['success' => true, 'message' => 'Menu atualizado com sucesso.', 'id' => $id]);
        } else {
            $stmt = $this->db->prepare('SELECT id FROM theme_menus WHERE location = ?');
            $stmt->execute([$location]);
            if ($stmt->fetch()) {
                return Response::json(['success' => false, 'message' => 'Já existe um menu atribuído a esta localização.'], 400);
            }

            $stmt = $this->db->prepare('INSERT INTO theme_menus (name, location) VALUES (?, ?)');
            $stmt->execute([$name, $location]);
            $id = $this->db->lastInsertId();
            return Response::json(['success' => true, 'message' => 'Menu criado com sucesso.', 'id' => $id]);
        }
    }

    public function storeItems(Request $request): Response
    {
        $jsonBody = json_decode(file_get_contents('php://input'), true);
        $menuId = $jsonBody['menu_id'] ?? $request->input('menu_id');
        $items = $jsonBody['items'] ?? [];
        
        if (!$menuId) {
            return Response::json(['success' => false, 'message' => 'ID do menu não informado.'], 400);
        }

        $stmt = $this->db->prepare('DELETE FROM theme_menu_items WHERE menu_id = ?');
        $stmt->execute([$menuId]);

        if (is_array($items) && !empty($items)) {
            $stmt = $this->db->prepare('INSERT INTO theme_menu_items (menu_id, title, url, page_id, order_index) VALUES (?, ?, ?, ?, ?)');
            foreach ($items as $index => $item) {
                $pageId = !empty($item['page_id']) ? $item['page_id'] : null;
                $url = !empty($item['url']) ? $item['url'] : null;
                $stmt->execute([$menuId, $item['title'], $url, $pageId, $index]);
            }
        }

        return Response::json(['success' => true, 'message' => 'Estrutura do menu salva com sucesso!']);
    }

    public function deleteMenu(Request $request, array $vars): Response
    {
        $id = $vars['id'];
        
        $stmt = $this->db->prepare('DELETE FROM theme_menu_items WHERE menu_id = ?');
        $stmt->execute([$id]);

        $stmt = $this->db->prepare('DELETE FROM theme_menus WHERE id = ?');
        $stmt->execute([$id]);

        return Response::json(['success' => true, 'message' => 'Menu excluído com sucesso.']);
    }

    public static function renderNavMenu(string $location): string
    {
        try {
            $app = \DomainSystem\Core\Application::getInstance();
            if (!$app || !$app->getContainer()->has(Connection::class)) return '';
            
            $db = $app->getContainer()->make(Connection::class)->getPdo();
            
            $stmt = $db->prepare("SELECT id FROM theme_menus WHERE location = ?");
            $stmt->execute([$location]);
            $menu = $stmt->fetch();
            
            if (!$menu) return '';

            $stmt = $db->prepare("
                SELECT mi.*, p.slug as page_slug 
                FROM theme_menu_items mi
                LEFT JOIN pages p ON mi.page_id = p.id
                WHERE mi.menu_id = ? 
                ORDER BY mi.order_index ASC
            ");
            $stmt->execute([$menu['id']]);
            $items = $stmt->fetchAll();

            $baseUrl = defined('BASE_URL') ? BASE_URL : '';
            $currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH); 

            $html = '<ul style="list-style: none; gap: 30px; display: flex; flex-direction: inherit; margin: 0; padding: 0;">';
            foreach ($items as $item) {
                // Resolve a URL (página dinâmica vs link estático)
                if ($item['page_id'] && $item['page_slug']) {
                    $url = $baseUrl . '/' . ltrim($item['page_slug'], '/');
                } else {
                    $url = str_starts_with($item['url'] ?? '', 'http') ? $item['url'] : $baseUrl . '/' . ltrim($item['url'] ?? '', '/');
                }
                
                $path = parse_url($url, PHP_URL_PATH);
                $isActive = ($currentUri === $path) ? 'color: var(--primary);' : '';
                
                $html .= '<li><a href="'.htmlspecialchars($url).'" class="nav-link" style="'.$isActive.'">'.htmlspecialchars($item['title']).'</a></li>';
            }
            $html .= '</ul>';

            return $html;
        } catch (\Exception $e) {
            return '';
        }
    }
}
