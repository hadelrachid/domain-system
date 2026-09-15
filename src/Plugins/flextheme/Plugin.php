<?php

namespace DomainSystem\Plugins\FlexTheme;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Plugins\FlexTheme\Controllers\ThemeController;

class Plugin extends AbstractPlugin
{
    public function boot(): void
    {
        // Opcional: injetar configurações de tema disponíveis no Container
    }

    public function registerRoutes(Router $router): void
    {
        // Rota CATCH-ALL para o Frontend (A Vitrine)
        // Só renderiza se não for uma rota /admin ou de api
        
        $router->get('/', [ThemeController::class, 'renderHome']);
        
        // Exemplo: $router->get('/sobre', [ThemeController::class, 'renderPage']);
    }
}
