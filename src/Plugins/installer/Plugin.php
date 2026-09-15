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
        
        // Verifica se existe um admin no banco de dados. Se não existir (ou der erro), o sistema entra em MODO SETUP.
        $isInstalled = false;
        try {
            $db = $this->container->make(\DomainSystem\Plugins\Database\Connection::class)->getPdo();
            $stmt = $db->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
            if ($stmt && $stmt->fetchColumn()) {
                $isInstalled = true;
            }
        } catch (\Exception $e) {
            // Tabela users não existe, banco inválido, etc.
        }

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
