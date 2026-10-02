<?php

namespace DomainSystem\Plugins\nav_menus\Controllers;

use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Plugins\Database\Connection;
use DomainSystem\Plugins\SystemAdmin\Contracts\AdminThemeInterface;

class MenuAdminController
{
    private $db;
    private $theme;

    public function __construct(Connection $conn, AdminThemeInterface $theme)
    {
        $this->db = $conn->getPdo();
        $this->theme = $theme;
    }

    public function index(Request $request): Response
    {
        $menus = $this->db->query("SELECT * FROM theme_menus ORDER BY name ASC")->fetchAll();
        $pages = $this->db->query("SELECT id, title, slug FROM pages ORDER BY title ASC")->fetchAll();
        
        $activeMenuId = $request->get('menu_id') ?: ($menus[0]['id'] ?? null);
        
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

        return $this->theme->render('admin_menus', [
            'menus' => $menus,
            'pages' => $pages,
            'activeMenuId' => $activeMenuId,
            'activeMenu' => $activeMenu,
            'menuItems' => $menuItems
        ], dirname(__DIR__) . '/views');
    }

    public function storeMenu(Request $request): Response
    {
        $name = $request->post('name');
        $location = $request->post('location');
        $id = $request->post('id');

        if (empty($name) || empty($location)) {
            $_SESSION['admin_error'] = 'Nome e Localização são obrigatórios.';
            header("Location: " . BASE_URL . "/admin/themes/menus");
            exit;
        }

        if ($id) {
            $stmt = $this->db->prepare("UPDATE theme_menus SET name = ?, location = ? WHERE id = ?");
            $stmt->execute([$name, $location, $id]);
            $_SESSION['admin_success'] = 'Menu atualizado com sucesso.';
        } else {
            // Se já existe um menu na location (header/footer), avisa (por ser unique)
            $stmt = $this->db->prepare("SELECT id FROM theme_menus WHERE location = ?");
            $stmt->execute([$location]);
            if ($stmt->fetch()) {
                $_SESSION['admin_error'] = "Já existe um menu atribuído à localização '{$location}'.";
                header("Location: " . BASE_URL . "/admin/themes/menus");
                exit;
            }

            $stmt = $this->db->prepare("INSERT INTO theme_menus (name, location) VALUES (?, ?)");
            $stmt->execute([$name, $location]);
            $id = $this->db->lastInsertId();
            $_SESSION['admin_success'] = 'Menu criado com sucesso.';
        }

        header("Location: " . BASE_URL . "/admin/themes/menus?menu_id=" . $id);
        exit;
    }

    public function storeItems(Request $request): Response
    {
        $menuId = $request->post('menu_id');
        $itemsData = $request->post('items'); // Array JSON de itens
        
        if (!$menuId) {
            header("Location: " . BASE_URL . "/admin/themes/menus");
            exit;
        }

        // Limpa itens antigos deste menu
        $stmt = $this->db->prepare("DELETE FROM theme_menu_items WHERE menu_id = ?");
        $stmt->execute([$menuId]);

        if (!empty($itemsData)) {
            $items = json_decode($itemsData, true);
            if (is_array($items)) {
                $stmt = $this->db->prepare("INSERT INTO theme_menu_items (menu_id, title, url, page_id, order_index) VALUES (?, ?, ?, ?, ?)");
                foreach ($items as $index => $item) {
                    $pageId = !empty($item['page_id']) ? $item['page_id'] : null;
                    $url = !empty($item['url']) ? $item['url'] : null;
                    $stmt->execute([$menuId, $item['title'], $url, $pageId, $index]);
                }
            }
        }

        $_SESSION['admin_success'] = 'Estrutura do menu salva com sucesso!';
        header("Location: " . BASE_URL . "/admin/themes/menus?menu_id=" . $menuId);
        exit;
    }

    public function deleteMenu(Request $request, array $vars): Response
    {
        $id = $vars['id'];
        
        $stmt = $this->db->prepare("DELETE FROM theme_menu_items WHERE menu_id = ?");
        $stmt->execute([$id]);

        $stmt = $this->db->prepare("DELETE FROM theme_menus WHERE id = ?");
        $stmt->execute([$id]);

        $_SESSION['admin_success'] = 'Menu excluído com sucesso.';
        header("Location: " . BASE_URL . "/admin/themes/menus");
        exit;
    }

    // Método estático para ser chamado pelo Shortcode [nav_menu location="header"]
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
