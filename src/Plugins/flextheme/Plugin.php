<?php

namespace DomainSystem\Plugins\flextheme;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Plugins\flextheme\Controllers\ThemeController;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Contracts\OsConnectorInterface;
use DomainSystem\Core\Contracts\OsRuntimeInterface;

class Plugin extends AbstractPlugin implements OsExtensionInterface
{
    public function register(): void {}

    // ==========================================
    // 1. FASE DE NEGOCIAÇÃO (OS 2.0)
    // ==========================================
    public function osRegister(OsConnectorInterface $os): void
    {
        $os->requireLink('core.db');
        $os->listenHook('router.register');
        $os->listenHook('shortcodes.register');
        $os->listenHook('admin.menu'); // Para injetar o submenu
    }

    // ==========================================
    // 2. FASE DE EXECUÇÃO (OS 2.0)
    // ==========================================
    public function osBoot(OsRuntimeInterface $runtime): void
    {
        // Interceptar o menu admin para adicionar o submenu "Menus" em "Temas"
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
        });

        $runtime->onHook('router.register', function(Router $router) {
            // Rotas Admin para Menus
            $router->addRoute('GET', '/admin/themes/menus', [\DomainSystem\Plugins\flextheme\Controllers\MenuAdminController::class, 'index'], 'flextheme', ['admin']);
            $router->addRoute('POST', '/admin/themes/menus/store', [\DomainSystem\Plugins\flextheme\Controllers\MenuAdminController::class, 'storeMenu'], 'flextheme', ['admin']);
            $router->addRoute('POST', '/admin/themes/menus/items', [\DomainSystem\Plugins\flextheme\Controllers\MenuAdminController::class, 'storeItems'], 'flextheme', ['admin']);
            $router->addRoute('POST', '/admin/themes/menus/delete/{id}', [\DomainSystem\Plugins\flextheme\Controllers\MenuAdminController::class, 'deleteMenu'], 'flextheme', ['admin']);

            // Rota CATCH-ALL para o Frontend (A Vitrine)
            $router->addRoute('GET', '/', [ThemeController::class, 'renderHome']);
            $router->addRoute('GET', '/docs/{*slug}', [ThemeController::class, 'renderDoc']);
            $router->addRoute('GET', '/docs', function() {
                $base = defined('BASE_URL') ? BASE_URL : '';
                header("Location: $base/docs/index");
                exit;
            });
        });

        $runtime->onHook('shortcodes.register', function(\DomainSystem\Core\Theme\ShortcodeManager $sm) {
            $sm->add('base_url', function() {
                return defined('BASE_URL') ? BASE_URL : '';
            }, 'Retorna a URL base do site (ex: /domain-system/public)');
            
            // Shortcode para injetar um menu no layout
            $sm->add('nav_menu', function($attrs) {
                $location = $attrs['location'] ?? 'header';
                return \DomainSystem\Plugins\flextheme\Controllers\MenuAdminController::renderNavMenu($location);
            }, 'Renderiza um menu criado no painel. Ex: [nav_menu location="header"]');
        });
    }

    public function activate(): void
    {
        try {
            $schema = $this->container->make(\DomainSystem\Plugins\Database\Schema\SchemaBuilder::class);
            
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
