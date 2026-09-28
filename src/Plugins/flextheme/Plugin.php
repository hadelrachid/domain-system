<?php

namespace DomainSystem\Plugins\FlexTheme;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Plugins\FlexTheme\Controllers\ThemeController;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Plugin\OsConnector;
use DomainSystem\Core\Plugin\OsRuntime;

class Plugin extends AbstractPlugin implements OsExtensionInterface
{
    public function register(): void {}

    // ==========================================
    // 1. FASE DE NEGOCIAÇÃO (OS 2.0)
    // ==========================================
    public function osRegister(OsConnector $os): void
    {
        $os->listenHook('router.register');
    }

    // ==========================================
    // 2. FASE DE EXECUÇÃO (OS 2.0)
    // ==========================================
    public function osBoot(OsRuntime $runtime): void
    {
        $runtime->onHook('router.register', function(Router $router) {
            // Rota CATCH-ALL para o Frontend (A Vitrine)
            // Só renderiza se não for uma rota /admin ou de api
            $router->addRoute('GET', '/', [ThemeController::class, 'renderHome']);
            // Exemplo: $router->addRoute('GET', '/sobre', [ThemeController::class, 'renderPage']);
        });
    }
}
