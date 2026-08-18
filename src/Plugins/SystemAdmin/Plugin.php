<?php

namespace DomainSystem\Plugins\SystemAdmin;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Plugins\SystemAdmin\Controllers\AdminController;

class Plugin extends AbstractPlugin
{
    public function register(): void
    {
        /** @var Router $router */
        $router = $this->container->make(Router::class);
        
        $controller = new AdminController($this->container);

        // Registrar rotas administrativas
        $router->add('GET', '/admin/plugins', function() use ($controller) {
            return $controller->listPlugins();
        });

        $router->add('POST', '/admin/plugins/toggle', function() use ($controller) {
            return $controller->togglePlugin();
        });
    }
}
