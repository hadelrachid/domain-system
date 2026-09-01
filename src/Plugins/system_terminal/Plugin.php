<?php

namespace DomainSystem\Plugins\system_terminal;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;

class Plugin extends AbstractPlugin
{
    public function register(): void
    {
        // Registre dependências no container
    }

    public function boot(): void
    {
        $events = $this->events();

        $events->addListener('router.register', function(Router $router) {
            $router->addRoute('GET', '/admin/terminal', [\DomainSystem\Plugins\system_terminal\Controllers\TerminalController::class, 'index'], 'system_terminal', ['admin']);
            $router->addRoute('POST', '/admin/terminal/execute', [\DomainSystem\Plugins\system_terminal\Controllers\TerminalController::class, 'execute'], 'system_terminal', ['admin']);
        });

        $events->addListener('admin.menu', function($menu) {
            $menu[] = [
                'title' => 'Web Terminal',
                'url' => '/admin/terminal',
                'icon' => '💻'
            ];
            return $menu;
        });
    }

    public function activate(): void
    {
        // Criação de tabelas no banco de dados
    }
}
