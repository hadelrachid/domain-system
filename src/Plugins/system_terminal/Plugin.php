<?php

namespace DomainSystem\Plugins\system_terminal;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
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
        $os->listenHook('router.register');
        $os->listenHook('admin.menu');
    }

    // ==========================================
    // 2. FASE DE EXECUÇÃO (OS 2.0)
    // ==========================================
    public function osBoot(OsRuntimeInterface $runtime): void
    {
        $runtime->onHook('router.register', function(Router $router) {
            $router->addRoute('GET', '/admin/terminal', [\DomainSystem\Plugins\system_terminal\Controllers\TerminalController::class, 'index'], 'system_terminal', ['admin']);
            $router->addRoute('POST', '/admin/terminal/execute', [\DomainSystem\Plugins\system_terminal\Controllers\TerminalController::class, 'execute'], 'system_terminal', ['admin']);
        });

        $runtime->onHook('admin.menu', function($menu) {
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
