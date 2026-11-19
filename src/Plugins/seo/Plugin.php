<?php

declare(strict_types=1);

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
        $os->provideLink('core.seo', \DomainSystem\Plugins\seo\Contracts\SeoManagerInterface::class);
        $os->listenHook('router.register');
    }

    // ==========================================
    // 2. FASE DE EXECUÇÃO (OS 2.0)
    // ==========================================
    public function osBoot(OsRuntimeInterface $runtime): void
    {
        // 1. Registra os serviços Nativos de SEO e Minificação na Injeção de Dependência
        $runtime->singleton(
            \DomainSystem\Plugins\seo\Contracts\AssetMinifierInterface::class,
            \DomainSystem\Plugins\seo\Services\NativeAssetMinifier::class
        );

        $runtime->singleton(
            \DomainSystem\Plugins\seo\Services\AssetPipeline::class,
            function($container) {
                $minifier = $container->make(\DomainSystem\Plugins\seo\Contracts\AssetMinifierInterface::class);
                $baseDir = dirname(__DIR__, 3);
                $cacheDir = $baseDir . '/public/assets/cache';
                $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
                return new \DomainSystem\Plugins\seo\Services\AssetPipeline($minifier, $cacheDir, $baseUrl . '/assets/cache');
            }
        );

        $runtime->singleton(
            \DomainSystem\Plugins\seo\Contracts\SeoManagerInterface::class,
            function($container) {
                $pipeline = $container->make(\DomainSystem\Plugins\seo\Services\AssetPipeline::class);
                return new \DomainSystem\Plugins\seo\Services\SeoManager($pipeline);
            }
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
