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
        /** @var Router $router */
        $router = $this->container->make(Router::class);
        
        $adminController = new AdminController($this->container);
        $dashboardController = new DashboardController($this->container);

        // Dashboard base
        $router->add('GET', '/admin', function() use ($dashboardController) {
            return $dashboardController->index();
        });

        // Rotas de Plugins
        $router->add('GET', '/admin/plugins', function() use ($adminController) {
            return $adminController->listPlugins();
        });

        $router->add('POST', '/admin/plugins/toggle', function() use ($adminController) {
            return $adminController->togglePlugin();
        });

        $router->add('POST', '/admin/plugins/upload', function() use ($adminController) {
            return $adminController->uploadPlugin();
        });

        $router->add('POST', '/admin/plugins/delete', function() use ($adminController) {
            return $adminController->deletePlugin();
        });
    }
}
