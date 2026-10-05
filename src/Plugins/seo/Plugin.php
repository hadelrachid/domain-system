<?php

namespace DomainSystem\Plugins\seo;

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
    }

    // ==========================================
    // 2. FASE DE EXECUÇÃO (OS 2.0)
    // ==========================================
    public function osBoot(OsRuntimeInterface $runtime): void
    {
        // 1. Registra os serviços Nativos de SEO na Injeção de Dependência
        $runtime->singleton(
            \DomainSystem\Plugins\seo\Contracts\AssetMinifierInterface::class,
            \DomainSystem\Plugins\seo\Services\NativeAssetMinifier::class
        );
        $runtime->singleton(
            \DomainSystem\Plugins\seo\Contracts\SeoManagerInterface::class,
            \DomainSystem\Plugins\seo\Services\SeoManager::class
        );
        $runtime->singleton(
            \DomainSystem\Plugins\seo\Contracts\HtmlOptimizerInterface::class,
            \DomainSystem\Plugins\seo\Services\HtmlOptimizer::class
        );

        // 2. Registra o Middleware Global no Router
        $runtime->onHook('router.register', function(Router $router) {
            $router->addGlobalMiddleware(\DomainSystem\Plugins\seo\Middlewares\SeoMiddleware::class);
        });
    }
}
