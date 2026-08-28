<?php
namespace DomainSystem\Plugins\SystemMonitor;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Plugins\SystemMonitor\Controllers\MonitorController;

class Plugin extends AbstractPlugin
{
    public function register(): void
    {
        $events = $this->container->make(\DomainSystem\Core\Events\EventDispatcher::class);

        $events->addListener('router.register', function(Router $router) {
            $router->addRoute('GET', '/admin/monitor', [MonitorController::class, 'index'], 'SystemMonitor', ['admin']);
            $router->addRoute('POST', '/admin/monitor/clear', [MonitorController::class, 'clear'], 'SystemMonitor', ['admin']);
        });

        $events->addListener('admin.menu', function($menu) {
            $menu[] = [
                'title' => 'Supervisão (Erros)',
                'url' => '/admin/monitor',
                'icon' => '🚨'
            ];
            return $menu;
        }, 99);
    }
}
