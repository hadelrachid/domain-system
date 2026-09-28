<?php

namespace DomainSystem\Plugins\installer;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Plugins\installer\Controllers\SetupController;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Plugin\OsConnector;
use DomainSystem\Core\Plugin\OsRuntime;

class Plugin extends AbstractPlugin implements OsExtensionInterface
{
    private bool $isInstalled = false;

    public function register(): void {}

    // ==========================================
    // 1. FASE DE NEGOCIAÇÃO (OS 2.0)
    // ==========================================
    public function osRegister(OsConnector $os): void
    {
        $os->requireLink('core.db');
        $os->listenHook('router.before_dispatch');
        $os->listenHook('router.register');
    }

    // ==========================================
    // 2. FASE DE EXECUÇÃO (OS 2.0)
    // ==========================================
    public function osBoot(OsRuntime $runtime): void
    {
        // Verifica se existe um admin no banco de dados. Se não existir (ou der erro), o sistema entra em MODO SETUP.
        try {
            $db = $runtime->getLink('core.db')->getPdo();
            $stmt = $db->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
            if ($stmt && $stmt->fetchColumn()) {
                $this->isInstalled = true;
            }
        } catch (\Exception $e) {
            // Tabela users não existe, banco inválido, etc.
        }

        if (!$this->isInstalled) {
            // Prioridade máxima (1000) para barrar qualquer outro plugin (ex: SystemAdmin/Auth)
            $runtime->onHook('router.before_dispatch', function(string $uri) {
                // Permite apenas as rotas do setup
                if (!str_starts_with($uri, '/setup') && !str_starts_with($uri, '/assets')) {
                    header("Location: " . BASE_URL . "/setup");
                    exit;
                }
            }, 1000);

            $runtime->onHook('router.register', function(Router $router) {
                $router->addRoute('GET', '/setup', [SetupController::class, 'step1']);
                $router->addRoute('GET', '/setup/logo', [SetupController::class, 'logo']);
                $router->addRoute('POST', '/setup/database', [SetupController::class, 'step2_database']);
                $router->addRoute('POST', '/setup/install', [SetupController::class, 'step3_install']);
            });
        }
    }
}
