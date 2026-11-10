<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop;

use DomainSystem\Core\Contracts\OsConnectorInterface;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Contracts\OsRuntimeInterface;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Plugins\domain_drop\Contracts\AiProductEnricherInterface;
use DomainSystem\Plugins\domain_drop\Contracts\AliExpressApiClientInterface;
use DomainSystem\Plugins\domain_drop\Contracts\CurrencyConverterInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipCatalogRepositoryInterface;
use DomainSystem\Plugins\domain_drop\Contracts\ProductMiningServiceInterface;
use DomainSystem\Plugins\domain_drop\Controllers\DropshipAdminController;
use DomainSystem\Plugins\domain_drop\Repositories\PdoDropshipCatalogRepository;
use DomainSystem\Plugins\domain_drop\Services\AiProductEnricherService;
use DomainSystem\Plugins\domain_drop\Services\AliExpressApiClient;
use DomainSystem\Plugins\domain_drop\Services\CurrencyConverterService;
use DomainSystem\Plugins\domain_drop\Services\ProductMiningService;

/**
 * ════════════════════════════════════════════════════════════════════════════
 * PLUGIN: DomainDrop (Motor Autônomo de Dropshipping & Mineração E-commerce)
 * ════════════════════════════════════════════════════════════════════════════
 *
 * Responsável pelo Hub central de dropshipping, integração direta com a API
 * AliExpress Open Platform (App ISV Drop Shipping 558628), conversão cambial
 * em tempo real, enriquecimento de produtos com IA e orquestração de catálogo.
 */
class Plugin extends AbstractPlugin implements OsExtensionInterface
{
    public function register(): void
    {
    }

    public function osRegister(OsConnectorInterface $os): void
    {
        $os->requireLink('core.db.schema');
        $os->requireLink('core.db');
        $os->requireLink('core.session');
        $os->listenHook('admin.menu');
        $os->listenHook('router.register');
    }

    public function osBoot(OsRuntimeInterface $runtime): void
    {
        $root = defined('DOMAIN_SYSTEM_ROOT') ? DOMAIN_SYSTEM_ROOT : dirname(__DIR__, 3);
        $configFile = $root . '/config/plugins/domain_drop.json';

        // 1. Injeção de Dependências no Container via Contratos (SOLID / DIP)
        $runtime->singleton(CurrencyConverterInterface::class, function () use ($root) {
            return new CurrencyConverterService(
                cacheFilePath: $root . '/storage/cache/exchange_rates.json',
                cacheTtlSeconds: 43200
            );
        });

        $runtime->singleton(AliExpressApiClientInterface::class, function () use ($configFile) {
            $appKey = '558628';
            $appSecret = '';

            if (file_exists($configFile)) {
                $data = @json_decode((string)file_get_contents($configFile), true);
                if (is_array($data)) {
                    $appKey = (string)($data['app_key'] ?? $appKey);
                    $appSecret = (string)($data['app_secret'] ?? '');
                }
            }

            return new AliExpressApiClient(
                appKey: $appKey,
                appSecret: $appSecret,
                gatewayUrl: 'https://api-sg.aliexpress.com/sync'
            );
        });

        $runtime->singleton(AiProductEnricherInterface::class, function () {
            return new AiProductEnricherService();
        });

        $runtime->singleton(DropshipCatalogRepositoryInterface::class, function () use ($runtime) {
            return new PdoDropshipCatalogRepository($runtime->getLink('core.db'));
        });

        $runtime->singleton(ProductMiningServiceInterface::class, function () use ($runtime, $configFile) {
            $markup = 100.0;
            $minMargin = 30.0;

            if (file_exists($configFile)) {
                $data = @json_decode((string)file_get_contents($configFile), true);
                if (is_array($data)) {
                    $markup = (float)($data['default_markup_percent'] ?? $markup);
                    $minMargin = (float)($data['min_profit_margin_brl'] ?? $minMargin);
                }
            }

            return new ProductMiningService(
                apiClient: $runtime->make(AliExpressApiClientInterface::class),
                currencyConverter: $runtime->make(CurrencyConverterInterface::class),
                enricher: $runtime->make(AiProductEnricherInterface::class),
                catalogRepo: $runtime->make(DropshipCatalogRepositoryInterface::class),
                defaultMarkupPercent: $markup,
                minProfitMarginBrl: $minMargin
            );
        });

        $runtime->singleton(DropshipAdminController::class, function () use ($runtime, $root) {
            return new DropshipAdminController(
                catalogRepo: $runtime->make(DropshipCatalogRepositoryInterface::class),
                miningService: $runtime->make(ProductMiningServiceInterface::class),
                currencyConverter: $runtime->make(CurrencyConverterInterface::class),
                session: $runtime->make(SessionManagerInterface::class),
                basePath: $root
            );
        });

        // 2. Menu Administrativo
        $runtime->onHook('admin.menu', function ($menus, $role = 'guest') {
            if ($role === 'admin') {
                $menus[] = [
                    'title'   => 'DomainDrop',
                    'url'     => '#',
                    'icon'    => '📦',
                    'submenu' => [
                        [
                            'title' => 'Catálogo & Mineração',
                            'url'   => '/admin/dropship',
                            'icon'  => '🛰️',
                        ],
                        [
                            'title' => 'Configurações & API',
                            'url'   => '/admin/dropship/settings',
                            'icon'  => '⚙️',
                        ],
                    ],
                ];
            }
            return $menus;
        });

        // 3. Rotas Administrativas
        $runtime->onHook('router.register', function (Router $router) {
            $router->addRoute('GET', '/admin/dropship', [DropshipAdminController::class, 'index'], 'domain_drop', ['admin']);
            $router->addRoute('POST', '/admin/dropship/mine', [DropshipAdminController::class, 'mine'], 'domain_drop', ['admin']);
            $router->addRoute('POST', '/admin/dropship/import-url', [DropshipAdminController::class, 'importUrl'], 'domain_drop', ['admin']);
            $router->addRoute('GET', '/admin/dropship/settings', [DropshipAdminController::class, 'settings'], 'domain_drop', ['admin']);
            $router->addRoute('POST', '/admin/dropship/settings', [DropshipAdminController::class, 'saveSettings'], 'domain_drop', ['admin']);
            $router->addRoute('POST', '/admin/dropship/delete/{id}', [DropshipAdminController::class, 'delete'], 'domain_drop', ['admin']);
        });
    }
}
