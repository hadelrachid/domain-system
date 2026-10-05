<?php

namespace DomainSystem\SystemApps\nav_menus;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Contracts\OsConnectorInterface;
use DomainSystem\Core\Contracts\OsRuntimeInterface;

class Plugin extends AbstractPlugin implements OsExtensionInterface
{
    public function register(): void {}

    public function osRegister(OsConnectorInterface $os): void
    {
        $os->requireLink('core.db');
        $os->listenHook('router.register');
        $os->listenHook('shortcodes.register');
        $os->listenHook('admin.menu'); // Para injetar o submenu em Temas
    }

    public function osBoot(OsRuntimeInterface $runtime): void
    {
        // Interceptar o menu admin para adicionar o submenu "Menus" em "Temas"
        // Prioridade 99 para rodar APÓS o SystemAdmin (que cria o item "Temas")
        $runtime->onHook('admin.menu', function($menus, $role = 'admin') {
            if ($role === 'admin' || $role === 'manager') {
                foreach ($menus as &$menu) {
                    if ($menu['title'] === 'Temas') {
                        if (!isset($menu['submenu'])) {
                            $menu['submenu'] = [];
                        }
                        $menu['submenu'][] = [
                            'title' => 'Menus (Navegação)',
                            'url' => '/admin/themes/menus',
                            'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>'
                        ];
                    }
                }
            }
            return $menus;
        }, 99);

        $runtime->onHook('router.register', function(Router $router) {
            // Rotas Admin para Menus
            $router->addRoute('GET', '/admin/themes/menus', [\DomainSystem\SystemApps\nav_menus\Controllers\MenuAdminController::class, 'index'], 'nav_menus', ['admin']);
            $router->addRoute('POST', '/admin/themes/menus/store', [\DomainSystem\SystemApps\nav_menus\Controllers\MenuAdminController::class, 'storeMenu'], 'nav_menus', ['admin']);
            $router->addRoute('POST', '/admin/themes/menus/items', [\DomainSystem\SystemApps\nav_menus\Controllers\MenuAdminController::class, 'storeItems'], 'nav_menus', ['admin']);
            $router->addRoute('POST', '/admin/themes/menus/delete/{id}', [\DomainSystem\SystemApps\nav_menus\Controllers\MenuAdminController::class, 'deleteMenu'], 'nav_menus', ['admin']);
        });

        $runtime->onHook('shortcodes.register', function(\DomainSystem\Core\Theme\ShortcodeManager $sm) use ($runtime) {
            // Shortcode para injetar um menu no layout (Consumido pelo flextheme)
            $sm->add('nav_menu', function($attrs) use ($runtime) {
                $location = $attrs['location'] ?? 'header';
                $controller = $runtime->make(\DomainSystem\SystemApps\nav_menus\Controllers\MenuAdminController::class); return $controller->renderNavMenu($location);
            }, 'Renderiza um menu criado no painel. Ex: [nav_menu location="header"]');
        });
    }

    public function activate(\DomainSystem\Core\Contracts\OsRuntimeInterface $runtime): void
    {
        try {
            $schema = $runtime->make(\DomainSystem\SystemApps\Database\Schema\SchemaBuilder::class);
            
            $schema->create('theme_menus', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('location')->unique(); // ex: 'header', 'footer'
                $table->datetime('created_at')->nullable()->default('CURRENT_TIMESTAMP');
            });

            $schema->create('theme_menu_items', function ($table) {
                $table->id();
                $table->integer('menu_id');
                $table->string('title');
                $table->string('url')->nullable();
                $table->integer('page_id')->nullable(); // Se for link dinâmico para uma página
                $table->integer('order_index')->default(0);
                $table->datetime('created_at')->nullable()->default('CURRENT_TIMESTAMP');
            });
        } catch (\Exception $e) {}
    }
}
