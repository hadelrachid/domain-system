<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\seo;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Contracts\OsConnectorInterface;
use DomainSystem\Core\Contracts\OsRuntimeInterface;
use DomainSystem\Plugins\seo\Contracts\AssetMinifierInterface;
use DomainSystem\Plugins\seo\Contracts\HtmlOptimizerInterface;
use DomainSystem\Plugins\seo\Contracts\PageSpeedAuditInterface;
use DomainSystem\Plugins\seo\Contracts\SeoManagerInterface;
use DomainSystem\Plugins\seo\Controllers\SeoAdminController;
use DomainSystem\Plugins\seo\Middlewares\SeoMiddleware;
use DomainSystem\Plugins\seo\Repositories\PdoPageSpeedAuditRepository;
use DomainSystem\Plugins\seo\Services\AssetPipeline;
use DomainSystem\Plugins\seo\Services\HtmlOptimizer;
use DomainSystem\Plugins\seo\Services\NativeAssetMinifier;
use DomainSystem\Plugins\seo\Services\PageSpeedAuditService;
use DomainSystem\Plugins\seo\Services\SeoManager;

class Plugin extends AbstractPlugin implements OsExtensionInterface
{
    public function register(): void {}

    // ==========================================
    // 1. FASE DE NEGOCIAÇÃO (OS 2.0)
    // ==========================================
    public function osRegister(OsConnectorInterface $os): void
    {
        $os->requireLink('core.db');
        $os->requireLink('core.session');
        $os->provideLink('core.seo', SeoManagerInterface::class);
        $os->listenHook('router.register');
        $os->listenHook('admin.menu');
    }

    // ==========================================
    // 2. FASE DE EXECUÇÃO (OS 2.0)
    // ==========================================
    public function osBoot(OsRuntimeInterface $runtime): void
    {
        // 1. Registra os serviços Nativos de SEO e Minificação na Injeção de Dependência
        $runtime->singleton(
            AssetMinifierInterface::class,
            NativeAssetMinifier::class
        );

        $runtime->singleton(
            AssetPipeline::class,
            function ($container) {
                $minifier = $container->make(AssetMinifierInterface::class);
                $baseDir = dirname(__DIR__, 3);
                $cacheDir = $baseDir . '/public/assets/cache';
                $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
                return new AssetPipeline($minifier, $cacheDir, $baseUrl . '/assets/cache');
            }
        );

        $runtime->singleton(
            SeoManagerInterface::class,
            function ($container) {
                $pipeline = $container->make(AssetPipeline::class);
                return new SeoManager($pipeline);
            }
        );

        $runtime->singleton(
            HtmlOptimizerInterface::class,
            HtmlOptimizer::class
        );

        // 2. Auditoria de Performance Google PageSpeed & Core Web Vitals
        $runtime->singleton(
            PageSpeedAuditInterface::class,
            PageSpeedAuditService::class
        );

        $runtime->singleton(
            PdoPageSpeedAuditRepository::class,
            function () use ($runtime) {
                return new PdoPageSpeedAuditRepository($runtime->getLink('core.db'));
            }
        );

        $runtime->singleton(
            SeoAdminController::class,
            function ($container) use ($runtime) {
                $auditService = $container->make(PageSpeedAuditInterface::class);
                $auditRepo = $container->make(PdoPageSpeedAuditRepository::class);
                $session = $runtime->getLink('core.session');
                return new SeoAdminController($auditService, $auditRepo, $session);
            }
        );

        // 3. Registra o Middleware Global e Rotas Administrativas no Router
        $runtime->onHook('router.register', function (Router $router) use ($runtime) {
            $router->addGlobalMiddleware(SeoMiddleware::class);

            $router->addRoute('GET', '/admin/seo', [SeoAdminController::class, 'index'], 'seo', ['admin']);
            $router->addRoute('POST', '/admin/seo/audit', [SeoAdminController::class, 'runAudit'], 'seo', ['admin']);
            $router->addRoute('GET', '/admin/seo/ai-feed', [SeoAdminController::class, 'getAiFeed'], 'seo', ['admin']);
        });

        // 4. Menu Lateral no Cockpit Admin
        $runtime->onHook('admin.menu', function ($menus, $role = 'guest') {
            if ($role === 'admin') {
                $menus[] = [
                    'title' => 'SEO & Performance',
                    'url'   => '/admin/seo',
                    'icon'  => '⚡',
                ];
            }
            return $menus;
        });
    }
}
