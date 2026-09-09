<?php

namespace DomainSystem\Plugins\installer;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Plugins\installer\Controllers\SetupController;

class Plugin extends AbstractPlugin
{
    public function register(): void
    {
        $events = $this->events();
        
        // Verifica se o arquivo installed.lock existe. Se não existir, o sistema entra em MODO SETUP.
        $isInstalled = file_exists(DOMAIN_SYSTEM_ROOT . '/config/installed.lock');

        if (!$isInstalled) {
            // Prioridade máxima (1000) para barrar qualquer outro plugin (ex: SystemAdmin/Auth)
            $events->addListener('router.before_dispatch', function(string $uri) {
                // Permite apenas as rotas do setup
                if (!str_starts_with($uri, '/setup') && !str_starts_with($uri, '/assets')) {
                    header("Location: " . BASE_URL . "/setup");
                    exit;
                }
            }, 1000);

            $events->addListener('router.register', function(Router $router) {
                $router->addRoute('GET', '/setup', [SetupController::class, 'step1']);
                $router->addRoute('GET', '/setup/logo', [SetupController::class, 'logo']);
                $router->addRoute('POST', '/setup/database', [SetupController::class, 'step2_database']);
                $router->addRoute('POST', '/setup/install', [SetupController::class, 'step3_install']);
            });
        }
    }
}
