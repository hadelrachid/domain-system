<?php

namespace DomainSystem\Plugins\SystemAdmin;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Plugins\SystemAdmin\Controllers\AdminController;
use DomainSystem\Plugins\SystemAdmin\Controllers\DashboardController;

class Plugin extends AbstractPlugin
{
    public function register(): void
    {
        $this->container->bind(
            \DomainSystem\Plugins\SystemAdmin\Contracts\DashboardRepositoryInterface::class,
            \DomainSystem\Plugins\SystemAdmin\Repositories\SqliteDashboardRepository::class
        );

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $events = $this->container->make(\DomainSystem\Core\Events\EventDispatcher::class);

        $events->addListener('workspace.register', function(\DomainSystem\Core\Workspace\WorkspaceManager $wm) {
            $theme = $this->container->make(\DomainSystem\Core\Theme\ThemeManager::class);
            $wm->registerWorkspace('receptionist', new \DomainSystem\Plugins\SystemAdmin\Workspace\ReceptionWorkspace($theme));
        });

        $events->addListener('admin.menu', function($menus, $role = 'admin') {
            if ($role === 'admin') {
                $menus[] = [
                    'title' => 'Catálogo de Shortcodes',
                    'url' => '/admin/shortcodes',
                    'icon' => '🧩'
                ];
            }
            return $menus;
        });

        // O Plugue (Macho) se conectando à Régua de Tomadas!
        $events->addListener('init', function() {
            add_shortcode('info_sistema', function($attr) {
                $color = $attr['color'] ?? 'black';
                return "<div style='padding: 10px; background-color: {$color}; color: white; border-radius: 5px;'>
                            <strong>CockPIT Info:</strong> Versão PHP: " . phpversion() . "
                        </div>";
            }, 'Exibe as informações do sistema.', ['color' => 'Cor de fundo do widget']);
        });

        $events->addListener('router.register', function(Router $router) {
            // Redireciona a raiz para o admin
            $router->addRoute('GET', '/', function() { header("Location: " . BASE_URL . "/admin"); exit; });

            // Dashboard base
            $router->addRoute('GET', '/admin', [DashboardController::class, 'index']);

            $router->addRoute('GET', '/admin/shortcodes', [AdminController::class, 'listShortcodes']);
            $router->addRoute('GET', '/admin/plugins', [AdminController::class, 'listPlugins']);
            $router->addRoute('GET', '/admin/themes', [AdminController::class, 'listThemes']);
            $router->addRoute('POST', '/admin/themes/create', [AdminController::class, 'createTheme']);
            $router->addRoute('POST', '/admin/themes/delete', [AdminController::class, 'deleteTheme']);
            $router->addRoute('POST', '/admin/plugins/toggle', [AdminController::class, 'togglePlugin']);
            $router->addRoute('POST', '/admin/plugins/upload', [AdminController::class, 'uploadPlugin']);
            $router->addRoute('POST', '/admin/plugins/delete', [AdminController::class, 'deletePlugin']);
        });
    }
}
