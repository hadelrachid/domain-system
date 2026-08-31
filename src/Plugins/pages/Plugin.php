<?php

namespace DomainSystem\Plugins\pages;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Core\Events\EventDispatcher;
use DomainSystem\Plugins\pages\Controllers\PageAdminController;
use DomainSystem\Plugins\pages\Controllers\PageFrontController;

class Plugin extends AbstractPlugin
{
    public function register(): void
    {

        /** @var EventDispatcher $events */
        $events = $this->events();

        // Adiciona ao Menu do Painel
        $events->addListener('admin.menu', function($menus, $role = 'admin') {
            if (in_array($role, ['admin', 'manager', 'receptionist'])) {
                $menus[] = [
                    'title' => 'Páginas',
                    'url' => '/admin/pages',
                    'icon' => '📄'
                ];
            }
            return $menus;
        });

        // Rotas
        $events->addListener('router.register', function(Router $router) {
            // Rotas do Painel
            $router->addRoute('GET', '/admin/pages', [PageAdminController::class, 'index']);
            $router->addRoute('GET', '/admin/pages/create', [PageAdminController::class, 'create']);
            $router->addRoute('GET', '/admin/pages/edit/{id}', [PageAdminController::class, 'edit']);
            $router->addRoute('POST', '/admin/pages/store', [PageAdminController::class, 'store']);
            $router->addRoute('POST', '/admin/pages/delete/{id}', [PageAdminController::class, 'delete']);
            
            // Rota Pública (O site)
            $router->addRoute('GET', '/p/{slug}', [PageFrontController::class, 'show']);
        });
    }

    public function activate(): void
    {
        /** @var \DomainSystem\Plugins\Database\Connection $connection */
        $connection = $this->db();
        $db = $connection->getPdo();
        
        $db->exec("
            CREATE TABLE IF NOT EXISTS pages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                slug VARCHAR(255) UNIQUE NOT NULL,
                title VARCHAR(255) NOT NULL,
                content TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }
}
