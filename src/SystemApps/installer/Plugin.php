<?php

namespace DomainSystem\SystemApps\installer;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\SystemApps\installer\Controllers\SetupController;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Contracts\OsConnectorInterface;
use DomainSystem\Core\Contracts\OsRuntimeInterface;

class Plugin extends AbstractPlugin implements OsExtensionInterface
{
    private bool $isInstalled = false;

    public function register(): void {}

    // ==========================================
    // 1. FASE DE NEGOCIAÇÃO (OS 2.0)
    // ==========================================
    public function osRegister(OsConnectorInterface $os): void
    {
        $os->requireLink('core.db');
        $os->listenHook('router.before_dispatch');
        $os->listenHook('router.register');
    }

    // ==========================================
    // 2. FASE DE EXECUÇÃO (OS 2.0)
    // ==========================================
    public function osBoot(OsRuntimeInterface $runtime): void
    {
        // Verifica se o arquivo de proteção existe. Se não existir, MODO SETUP.
        if (file_exists(DOMAIN_SYSTEM_ROOT . '/config/installed.lock')) {
            $this->isInstalled = true;
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
