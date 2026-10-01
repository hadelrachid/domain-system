<?php
namespace DomainSystem\Plugins\SystemMonitor;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Plugins\SystemMonitor\Controllers\MonitorController;
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
            $router->addRoute('GET', '/admin/monitor', [MonitorController::class, 'index'], 'SystemMonitor', ['admin']);
            $router->addRoute('POST', '/admin/monitor/clear', [MonitorController::class, 'clear'], 'SystemMonitor', ['admin']);
        });

        $runtime->onHook('admin.menu', function($menu) {
            $menu[] = [
                'title' => 'Supervisão (Erros)',
                'url' => '/admin/monitor',
                'icon' => '🚨'
            ];
            return $menu;
        }, 99);
    }
}
