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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $events = $this->container->make(\DomainSystem\Core\Events\EventDispatcher::class);

        $events->addListener('workspace.register', function(\DomainSystem\Core\Workspace\WorkspaceManager $wm) {
            $theme = $this->container->make(\DomainSystem\Core\Theme\ThemeManager::class);
            $wm->registerWorkspace('receptionist', new \DomainSystem\Plugins\SystemAdmin\Workspace\ReceptionWorkspace($theme));
        });

        $events->addListener('router.register', function(Router $router) {
            // Redireciona a raiz para o admin
            $router->addRoute('GET', '/', function() { header("Location: " . BASE_URL . "/admin"); exit; });

            // Dashboard base
            $router->addRoute('GET', '/admin', [DashboardController::class, 'index']);

            // Rotas de Plugins
            $router->addRoute('GET', '/admin/plugins', [AdminController::class, 'listPlugins']);
            $router->addRoute('GET', '/admin/themes', [AdminController::class, 'listThemes']);
            $router->addRoute('POST', '/admin/plugins/toggle', [AdminController::class, 'togglePlugin']);
            $router->addRoute('POST', '/admin/plugins/upload', [AdminController::class, 'uploadPlugin']);
            $router->addRoute('POST', '/admin/plugins/delete', [AdminController::class, 'deletePlugin']);
        });
    }
}
