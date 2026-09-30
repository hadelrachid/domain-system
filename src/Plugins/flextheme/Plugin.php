<?php

namespace DomainSystem\Plugins\FlexTheme;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Plugins\FlexTheme\Controllers\ThemeController;
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
        $os->listenHook('shortcodes.register');
    }

    // ==========================================
    // 2. FASE DE EXECUÇÃO (OS 2.0)
    // ==========================================
    public function osBoot(OsRuntimeInterface $runtime): void
    {
        $runtime->onHook('router.register', function(Router $router) {
            // Rota CATCH-ALL para o Frontend (A Vitrine)
            // Só renderiza se não for uma rota /admin ou de api
            $router->addRoute('GET', '/', [ThemeController::class, 'renderHome']);
            $router->addRoute('GET', '/docs/{*slug}', [ThemeController::class, 'renderDoc']);
            $router->addRoute('GET', '/docs', function() {
                // Redireciona /docs para /docs/index
                $base = defined('BASE_URL') ? BASE_URL : '';
                header("Location: $base/docs/index");
                exit;
            });
            // Exemplo: $router->addRoute('GET', '/sobre', [ThemeController::class, 'renderPage']);
        });

        $runtime->onHook('shortcodes.register', function(\DomainSystem\Core\Theme\ShortcodeManager $sm) {
            $sm->add('base_url', function() {
                return defined('BASE_URL') ? BASE_URL : '';
            }, 'Retorna a URL base do site (ex: /domain-system/public)');
        });
    }
}
