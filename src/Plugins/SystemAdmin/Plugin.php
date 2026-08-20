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

        /** @var Router $router */
        $router = $this->container->make(Router::class);

        // Redireciona a raiz para o admin
        $router->addRoute('GET', '/', function() { header("Location: " . BASE_URL . "/admin"); exit; });

        // Dashboard base
        $router->addRoute('GET', '/admin', [DashboardController::class, 'index']);

        // Rotas de Plugins
        $router->addRoute('GET', '/admin/plugins', [AdminController::class, 'listPlugins']);
        $router->addRoute('POST', '/admin/plugins/toggle', [AdminController::class, 'togglePlugin']);
        $router->addRoute('POST', '/admin/plugins/upload', [AdminController::class, 'uploadPlugin']);
        $router->addRoute('POST', '/admin/plugins/delete', [AdminController::class, 'deletePlugin']);
    }
}
