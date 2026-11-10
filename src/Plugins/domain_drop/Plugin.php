<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop;

use DomainSystem\Core\Contracts\OsConnectorInterface;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Contracts\OsRuntimeInterface;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Plugins\commerce\Contracts\PurchasableItemResolverInterface;
use DomainSystem\Plugins\domain_drop\Adapters\DropshipProductPurchasableAdapter;
use DomainSystem\Plugins\domain_drop\Contracts\AiProductEnricherInterface;
use DomainSystem\Plugins\domain_drop\Contracts\AliExpressApiClientInterface;
use DomainSystem\Plugins\domain_drop\Contracts\CurrencyConverterInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipCatalogRepositoryInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipOrderRepositoryInterface;
use DomainSystem\Plugins\domain_drop\Contracts\ProductMiningServiceInterface;
use DomainSystem\Plugins\domain_drop\Controllers\DropshipAdminController;
use DomainSystem\Plugins\domain_drop\Repositories\PdoDropshipCatalogRepository;
use DomainSystem\Plugins\domain_drop\Repositories\PdoDropshipOrderRepository;
use DomainSystem\Plugins\domain_drop\Services\AiProductEnricherService;
use DomainSystem\Plugins\domain_drop\Services\AliExpressApiClient;
use DomainSystem\Plugins\domain_drop\Services\CurrencyConverterService;
use DomainSystem\Plugins\domain_drop\Services\ProductMiningService;
use Throwable;

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
        $os->listenHook('commerce.register_item_resolvers');
        $os->listenHook('payment.approved');
        $os->listenHook('theme.blocks.register');
        $os->listenHook('flexbuilder.register_blocks');
        $os->listenHook('shortcodes.register');
    }

    public function osBoot(OsRuntimeInterface $runtime): void
    {
        $root = defined('DOMAIN_SYSTEM_ROOT') ? DOMAIN_SYSTEM_ROOT : dirname(__DIR__, 3);
        $configFile = $root . '/config/plugins/domain_drop.json';

        // 1. Injeção de Dependências no Container via Contratos (SOLID / DIP)
        $runtime->singleton(CurrencyConverterInterface::class, function () use ($root, $configFile) {
            $markup = 100.0;
            $minMargin = 30.0;
            if (file_exists($configFile)) {
                $data = @json_decode((string)file_get_contents($configFile), true);
                if (is_array($data)) {
                    $markup = (float)($data['default_markup_percent'] ?? $markup);
                    $minMargin = (float)($data['min_profit_margin_brl'] ?? $minMargin);
                }
            }

            return new CurrencyConverterService(
                cacheDirOrFile: $root . '/storage/cache/exchange_rates.json',
                defaultMarkupPercent: $markup,
                minProfitBrl: $minMargin
            );
        });

        $runtime->singleton(AliExpressApiClientInterface::class, function () use ($configFile) {
            $appKey = '558628';
            $appSecret = '';
            $accessToken = null;

            if (file_exists($configFile)) {
                $data = @json_decode((string)file_get_contents($configFile), true);
                if (is_array($data)) {
                    $appKey = (string)($data['app_key'] ?? $appKey);
                    $appSecret = (string)($data['app_secret'] ?? '');
                    $accessToken = !empty($data['access_token']) ? (string)$data['access_token'] : null;
                }
            }

            return new AliExpressApiClient(
                appKey: $appKey,
                appSecret: $appSecret,
                gatewayUrl: 'https://api-sg.aliexpress.com/sync',
                timeout: 15,
                accessToken: $accessToken
            );
        });

        $runtime->singleton(AiProductEnricherInterface::class, function () {
            return new AiProductEnricherService();
        });

        $runtime->singleton(DropshipCatalogRepositoryInterface::class, function () use ($runtime) {
            return new PdoDropshipCatalogRepository($runtime->getLink('core.db'));
        });

        $runtime->singleton(DropshipOrderRepositoryInterface::class, function () use ($runtime) {
            return new PdoDropshipOrderRepository($runtime->getLink('core.db'));
        });

        $runtime->singleton(ProductMiningServiceInterface::class, function () use ($runtime) {
            return new ProductMiningService(
                apiClient: $runtime->make(AliExpressApiClientInterface::class),
                currencyConverter: $runtime->make(CurrencyConverterInterface::class),
                aiEnricher: $runtime->make(AiProductEnricherInterface::class),
                repository: $runtime->make(DropshipCatalogRepositoryInterface::class)
            );
        });

        $runtime->singleton(DropshipAdminController::class, function () use ($runtime, $root) {
            return new DropshipAdminController(
                catalogRepo: $runtime->make(DropshipCatalogRepositoryInterface::class),
                miningService: $runtime->make(ProductMiningServiceInterface::class),
                currencyConverter: $runtime->make(CurrencyConverterInterface::class),
                session: $runtime->getLink('core.session'),
                orderRepo: $runtime->make(DropshipOrderRepositoryInterface::class),
                basePath: $root
            );
        });

        $runtime->singleton(\DomainSystem\Plugins\domain_drop\Controllers\DropshipFrontController::class, function () use ($runtime) {
            $themeManager = null;
            try {
                $themeManager = $runtime->getLink('core.theme');
            } catch (Throwable $e) {
            }

            return new \DomainSystem\Plugins\domain_drop\Controllers\DropshipFrontController(
                catalogRepo: $runtime->make(DropshipCatalogRepositoryInterface::class),
                themeManager: $themeManager
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
                            'title' => 'Pedidos Dropshipping',
                            'url'   => '/admin/dropship/orders',
                            'icon'  => '🛍️',
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
            $router->addRoute('GET', '/admin/dropship/orders', [DropshipAdminController::class, 'orders'], 'domain_drop', ['admin']);
            $router->addRoute('POST', '/admin/dropship/orders/{id}/fulfillment', [DropshipAdminController::class, 'updateFulfillment'], 'domain_drop', ['admin']);
            $router->addRoute('GET', '/admin/dropship/settings', [DropshipAdminController::class, 'settings'], 'domain_drop', ['admin']);
            $router->addRoute('POST', '/admin/dropship/settings', [DropshipAdminController::class, 'saveSettings'], 'domain_drop', ['admin']);
            $router->addRoute('POST', '/admin/dropship/delete/{id}', [DropshipAdminController::class, 'delete'], 'domain_drop', ['admin']);

            // 4. Rotas Públicas da Loja Virtual & Vitrine de Produtos
            $router->addRoute('GET', '/loja', [\DomainSystem\Plugins\domain_drop\Controllers\DropshipFrontController::class, 'storefront'], 'domain_drop');
            $router->addRoute('GET', '/produto/{slug}', [\DomainSystem\Plugins\domain_drop\Controllers\DropshipFrontController::class, 'singleProduct'], 'domain_drop');
        });

        // 4. Integração com DomainCommerce (Resolução de Itens Vendáveis no Checkout)
        $runtime->onHook('commerce.register_item_resolvers', function ($resolver) use ($runtime) {
            if ($resolver instanceof PurchasableItemResolverInterface) {
                $resolveDropshipItem = function (int $id) use ($runtime) {
                    try {
                        /** @var DropshipCatalogRepositoryInterface $repo */
                        $repo = $runtime->make(DropshipCatalogRepositoryInterface::class);
                        $product = $repo->findById($id);
                        return $product !== null ? new DropshipProductPurchasableAdapter($product) : null;
                    } catch (Throwable $e) {
                        return null;
                    }
                };

                $resolver->registerResolver('dropship_product', $resolveDropshipItem);
                $resolver->registerResolver('dropship', $resolveDropshipItem);
                $resolver->registerResolver('product', $resolveDropshipItem);
            }
        });

        // 5. Integração com Pagamentos Aprovados (Criação de Pedido de Dropshipping)
        $runtime->onHook('payment.approved', function ($payload) use ($runtime) {
            if (!is_array($payload)) {
                return;
            }
            $itemType = (string)($payload['item_type'] ?? '');
            $itemId = (int)($payload['item_id'] ?? ($payload['course_id'] ?? 0));
            $orderRef = (string)($payload['order_ref'] ?? '');

            if (in_array($itemType, ['dropship_product', 'dropship', 'product'], true) && $orderRef !== '') {
                try {
                    /** @var DropshipOrderRepositoryInterface $orderRepo */
                    $orderRepo = $runtime->make(DropshipOrderRepositoryInterface::class);
                    $existing = $orderRepo->findByOrderRef($orderRef);
                    if ($existing === null) {
                        $orderRepo->create([
                            'order_ref'          => $orderRef,
                            'customer_name'      => (string)($payload['customer_name'] ?? 'Cliente'),
                            'customer_email'     => (string)($payload['customer_email'] ?? ''),
                            'customer_document'  => isset($payload['customer_document']) ? (string)$payload['customer_document'] : null,
                            'shipping_address'    => isset($payload['shipping_address']) ? $payload['shipping_address'] : null,
                            'status'             => 'paid',
                            'total_paid_brl'     => (float)($payload['total'] ?? 0.0),
                        ]);
                    } else {
                        $orderRepo->updateFulfillment($orderRef, 'paid');
                    }
                } catch (Throwable $e) {
                    // Isolamento de falha
                }
            }
        });

        // 6. Registro de Blocos Semânticos no Flex Builder via Contrato (SOLID - OCP / DIP)
        $registerDropshipBlocks = function ($registry) use ($runtime) {
            if (is_object($registry) && method_exists($registry, 'has') && !$registry->has('dropship_showcase')) {
                try {
                    $provider = new \DomainSystem\Plugins\domain_drop\Blocks\DropshipBlockProvider(
                        $runtime->make(DropshipCatalogRepositoryInterface::class)
                    );
                    $provider->registerBlocks($registry);
                } catch (Throwable $e) {
                    // Isolamento gracioso
                }
            }
        };

        $runtime->onHook('theme.blocks.register', $registerDropshipBlocks);
        $runtime->onHook('flexbuilder.register_blocks', $registerDropshipBlocks);

        // 7. Shortcode para Vitrine de Dropshipping: [dropship_vitrine limit="8" cols="4" category=""]
        $runtime->onHook('shortcodes.register', function (\DomainSystem\Core\Theme\ShortcodeManager $manager) use ($runtime) {
            $manager->add('dropship_vitrine', function ($attrs = []) use ($runtime) {
                try {
                    $block = new \DomainSystem\Plugins\domain_drop\Blocks\DropshipShowcaseBlock(
                        $runtime->make(DropshipCatalogRepositoryInterface::class)
                    );
                    $attributes = [
                        'limit'    => isset($attrs['limit']) ? (int)$attrs['limit'] : 8,
                        'columns'  => isset($attrs['cols']) ? (int)$attrs['cols'] : (isset($attrs['columns']) ? (int)$attrs['columns'] : 4),
                        'category' => isset($attrs['category']) ? (string)$attrs['category'] : '',
                        'title'    => isset($attrs['title']) ? (string)$attrs['title'] : 'Tendências em Alta & Mais Vendidos',
                    ];
                    return $block->render($attributes);
                } catch (Throwable $e) {
                    return "<div style='text-align:center; padding:30px; background:rgba(255,255,255,0.03); border-radius:8px; color:#8c8f94;'>Vitrine Dropshipping em configuração.</div>";
                }
            });
        });
    }
}
