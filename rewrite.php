<?php
$f = "src/Plugins/nav_menus/Controllers/MenuAdminController.php";
$code = file_get_contents($f);

// Replace storeMenu
$code = preg_replace("/public function storeMenu\(Request \\\$request\): Response.*?public function storeItems/s", "public function storeMenu(Request \$request): Response
    {
        \$jsonBody = json_decode(file_get_contents('php://input'), true);
        \$name = \$jsonBody['name'] ?? \$request->input('name');
        \$location = \$jsonBody['location'] ?? \$request->input('location');
        \$id = \$jsonBody['id'] ?? \$request->input('id');

        if (empty(\$name) || empty(\$location)) {
            return Response::json(['success' => false, 'message' => 'Nome e Localização são obrigatórios.'], 400);
        }

        if (\$id) {
            \$stmt = \$this->db->prepare('UPDATE theme_menus SET name = ?, location = ? WHERE id = ?');
            \$stmt->execute([\$name, \$location, \$id]);
            return Response::json(['success' => true, 'message' => 'Menu atualizado com sucesso.', 'id' => \$id]);
        } else {
            \$stmt = \$this->db->prepare('SELECT id FROM theme_menus WHERE location = ?');
            \$stmt->execute([\$location]);
            if (\$stmt->fetch()) {
                return Response::json(['success' => false, 'message' => 'Já existe um menu atribuído a esta localização.'], 400);
            }

            \$stmt = \$this->db->prepare('INSERT INTO theme_menus (name, location) VALUES (?, ?)');
            \$stmt->execute([\$name, \$location]);
            \$id = \$this->db->lastInsertId();
            return Response::json(['success' => true, 'message' => 'Menu criado com sucesso.', 'id' => \$id]);
        }
    }

    public function storeItems", $code);

// Replace storeItems
$code = preg_replace("/public function storeItems\(Request \\\$request\): Response.*?public function deleteMenu/s", "public function storeItems(Request \$request): Response
    {
        \$jsonBody = json_decode(file_get_contents('php://input'), true);
        \$menuId = \$jsonBody['menu_id'] ?? \$request->input('menu_id');
        \$items = \$jsonBody['items'] ?? [];
        
        if (!\$menuId) {
            return Response::json(['success' => false, 'message' => 'ID do menu não informado.'], 400);
        }

        \$stmt = \$this->db->prepare('DELETE FROM theme_menu_items WHERE menu_id = ?');
        \$stmt->execute([\$menuId]);

        if (is_array(\$items) && !empty(\$items)) {
            \$stmt = \$this->db->prepare('INSERT INTO theme_menu_items (menu_id, title, url, page_id, order_index) VALUES (?, ?, ?, ?, ?)');
            foreach (\$items as \$index => \$item) {
                \$pageId = !empty(\$item['page_id']) ? \$item['page_id'] : null;
                \$url = !empty(\$item['url']) ? \$item['url'] : null;
                \$stmt->execute([\$menuId, \$item['title'], \$url, \$pageId, \$index]);
            }
        }

        return Response::json(['success' => true, 'message' => 'Estrutura do menu salva com sucesso!']);
    }

    public function deleteMenu", $code);

// Replace deleteMenu
$code = preg_replace("/public function deleteMenu\(Request \\\$request, array \\\$vars\): Response.*?public static function renderNavMenu/s", "public function deleteMenu(Request \$request, array \$vars): Response
    {
        \$id = \$vars['id'];
        
        \$stmt = \$this->db->prepare('DELETE FROM theme_menu_items WHERE menu_id = ?');
        \$stmt->execute([\$id]);

        \$stmt = \$this->db->prepare('DELETE FROM theme_menus WHERE id = ?');
        \$stmt->execute([\$id]);

        return Response::json(['success' => true, 'message' => 'Menu excluído com sucesso.']);
    }

    public static function renderNavMenu", $code);

file_put_contents($f, $code);
echo "Done";
