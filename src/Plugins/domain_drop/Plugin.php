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
use DomainSystem\Plugins\domain_drop\Contracts\CategoryTaxonomyInterface;
use DomainSystem\Plugins\domain_drop\Contracts\CurrencyConverterInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipCatalogRepositoryInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipCustomerRepositoryInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipDisputeRepositoryInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipOrderRepositoryInterface;
use DomainSystem\Plugins\domain_drop\Contracts\ProductLinkScraperInterface;
use DomainSystem\Plugins\domain_drop\Contracts\ProductMediaSyncInterface;
use DomainSystem\Plugins\domain_drop\Contracts\ProductMiningServiceInterface;
use DomainSystem\Plugins\domain_drop\Controllers\CustomerAccountController;
use DomainSystem\Plugins\domain_drop\Controllers\DropshipAdminController;
use DomainSystem\Plugins\domain_drop\Repositories\PdoDropshipCatalogRepository;
use DomainSystem\Plugins\domain_drop\Repositories\PdoDropshipCustomerRepository;
use DomainSystem\Plugins\domain_drop\Repositories\PdoDropshipDisputeRepository;
use DomainSystem\Plugins\domain_drop\Repositories\PdoDropshipOrderRepository;
use DomainSystem\Plugins\domain_drop\Services\AiProductEnricherService;
use DomainSystem\Plugins\domain_drop\Services\AliExpressApiClient;
use DomainSystem\Plugins\domain_drop\Services\AliExpressLinkScraperService;
use DomainSystem\Plugins\domain_drop\Services\CategoryTaxonomyService;
use DomainSystem\Plugins\domain_drop\Services\CurrencyConverterService;
use DomainSystem\Plugins\domain_drop\Services\DisputeAttachmentService;
use DomainSystem\Plugins\domain_drop\Services\OrderSlaProgressService;
use DomainSystem\Plugins\domain_drop\Services\ProductMediaSyncService;
use DomainSystem\Plugins\domain_drop\Services\ProductMiningService;
use DomainSystem\Plugins\domain_drop\Services\SellerMediationService;
use DomainSystem\SystemApps\auth\Contracts\EmailSenderInterface;
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
        $os->requireLink('core.theme');
        $os->requireLink('core.mailer');
        $os->listenHook('admin.menu');
        $os->listenHook('router.register');
        $os->listenHook('commerce.register_item_resolvers');
        $os->listenHook('payment.approved');
        $os->listenHook('payment.refunded');
        $os->listenHook('dropship.order_created');
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
            $volatility = 12.0;
            if (file_exists($configFile)) {
                $data = @json_decode((string)file_get_contents($configFile), true);
                if (is_array($data)) {
                    $markup = (float)($data['default_markup_percent'] ?? $markup);
                    $minMargin = (float)($data['min_profit_margin_brl'] ?? $minMargin);
                    $volatility = (float)($data['volatility_index_percent'] ?? $volatility);
                }
            }

            return new CurrencyConverterService(
                cacheDirOrFile: $root . '/storage/cache/exchange_rates.json',
                defaultMarkupPercent: $markup,
                minProfitBrl: $minMargin,
                defaultVolatilityPercent: $volatility
            );
        });

        $runtime->singleton(AliExpressApiClientInterface::class, function () use ($configFile) {
            $appKey = '558628';
            $appSecret = '';
            $accessToken = null;
            $refreshToken = null;
            $expiresAt = 0;
            $refreshExpiresAt = 0;
            $data = [];

            if (file_exists($configFile)) {
                $decoded = @json_decode((string)file_get_contents($configFile), true);
                if (is_array($decoded)) {
                    $data = $decoded;
                    $appKey = (string)($data['app_key'] ?? $appKey);
                    $appSecret = (string)($data['app_secret'] ?? '');
                    $accessToken = !empty($data['access_token']) ? (string)$data['access_token'] : null;
                    $refreshToken = !empty($data['refresh_token']) ? (string)$data['refresh_token'] : null;
                    $expiresAt = (int)($data['access_token_expires_at'] ?? 0);
                    $refreshExpiresAt = (int)($data['refresh_token_expires_at'] ?? 0);
                }
            }

            $client = new AliExpressApiClient(
                appKey: $appKey,
                appSecret: $appSecret,
                gatewayUrl: 'https://api-sg.aliexpress.com/sync',
                timeout: 15,
                accessToken: $accessToken
            );

            // Renovação automática 30 minutos antes do vencimento (conforme documentação oficial AliExpress)
            if ($appKey !== '' && $appSecret !== '' && $accessToken !== null && $refreshToken !== null
                && $expiresAt > 0 && time() >= ($expiresAt - 1800)
                && ($refreshExpiresAt === 0 || time() < $refreshExpiresAt)
            ) {
                try {
                    $refreshed = $client->refreshAccessToken($refreshToken);
                    if (!empty($refreshed['access_token'])) {
                        $newToken = (string)$refreshed['access_token'];
                        $data['access_token'] = $newToken;
                        if (!empty($refreshed['refresh_token'])) {
                            $data['refresh_token'] = (string)$refreshed['refresh_token'];
                        }
                        $expIn = (int)($refreshed['expire_time'] ?? ($refreshed['expires_in'] ?? 2592000));
                        $data['access_token_expires_at'] = $expIn > 1000000000 ? (int)($expIn / 1000) : (time() + max(60, $expIn));
                        if (!empty($refreshed['refresh_token_valid_time'])) {
                            $rVal = (int)$refreshed['refresh_token_valid_time'];
                            $data['refresh_token_expires_at'] = $rVal > 1000000000 ? (int)($rVal / 1000) : (time() + $rVal);
                        }
                        @file_put_contents($configFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                        return new AliExpressApiClient(
                            appKey: $appKey,
                            appSecret: $appSecret,
                            gatewayUrl: 'https://api-sg.aliexpress.com/sync',
                            timeout: 15,
                            accessToken: $newToken
                        );
                    }
                } catch (Throwable $e) {
                    // Mantém o token atual em caso de falha transitória de rede
                }
            }

            return $client;
        });

        $runtime->singleton(AiProductEnricherInterface::class, function () {
            return new AiProductEnricherService();
        });

        $runtime->singleton(CategoryTaxonomyInterface::class, function () {
            return new CategoryTaxonomyService();
        });

        $runtime->singleton(DropshipCatalogRepositoryInterface::class, function () use ($runtime) {
            return new PdoDropshipCatalogRepository(
                $runtime->getLink('core.db'),
                $runtime->make(CategoryTaxonomyInterface::class)
            );
        });

        $runtime->singleton(DropshipOrderRepositoryInterface::class, function () use ($runtime) {
            return new PdoDropshipOrderRepository($runtime->getLink('core.db'));
        });

        $runtime->singleton(DropshipCustomerRepositoryInterface::class, function () use ($runtime) {
            return new PdoDropshipCustomerRepository($runtime->getLink('core.db'));
        });

        $runtime->singleton(DropshipDisputeRepositoryInterface::class, function () use ($runtime) {
            return new PdoDropshipDisputeRepository($runtime->getLink('core.db'));
        });

        $runtime->singleton(\DomainSystem\Plugins\domain_drop\Contracts\DemandMetricsRepositoryInterface::class, function () use ($runtime) {
            return new \DomainSystem\Plugins\domain_drop\Repositories\PdoDemandMetricsRepository($runtime->getLink('core.db'));
        });

        $runtime->singleton(\DomainSystem\Plugins\domain_drop\Contracts\DemandLogStorageInterface::class, function () use ($root) {
            return new \DomainSystem\Plugins\domain_drop\Services\FileDemandLogStorage(
                baseDir: $root . '/storage/demand_logs'
            );
        });

        $runtime->singleton(\DomainSystem\Plugins\domain_drop\Contracts\DemandTrackerInterface::class, function () use ($runtime) {
            return new \DomainSystem\Plugins\domain_drop\Services\SessionGuardedDemandTracker(
                repository: $runtime->make(\DomainSystem\Plugins\domain_drop\Contracts\DemandMetricsRepositoryInterface::class),
                session: $runtime->getLink('core.session'),
                logStorage: $runtime->make(\DomainSystem\Plugins\domain_drop\Contracts\DemandLogStorageInterface::class)
            );
        });

        $runtime->singleton(OrderSlaProgressService::class, function () {
            return new OrderSlaProgressService();
        });

        $runtime->singleton(DisputeAttachmentService::class, function () use ($root) {
            return new DisputeAttachmentService($root, true);
        });

        $runtime->singleton(SellerMediationService::class, function () use ($runtime) {
            $mailer = null;
            try {
                $mailer = $runtime->getLink('core.mailer');
            } catch (Throwable $e) {
                $mailer = null;
            }

            return new SellerMediationService(
                disputeRepo: $runtime->make(DropshipDisputeRepositoryInterface::class),
                mailer: $mailer instanceof EmailSenderInterface ? $mailer : null
            );
        });

        $runtime->singleton(CustomerAccountController::class, function () use ($runtime) {
            $themeManager = null;
            try {
                $themeManager = $runtime->getLink('core.theme');
            } catch (Throwable $e) {
            }

            return new CustomerAccountController(
                customerRepo: $runtime->make(DropshipCustomerRepositoryInterface::class),
                orderRepo: $runtime->make(DropshipOrderRepositoryInterface::class),
                disputeRepo: $runtime->make(DropshipDisputeRepositoryInterface::class),
                session: $runtime->getLink('core.session'),
                attachmentService: $runtime->make(DisputeAttachmentService::class),
                slaService: $runtime->make(OrderSlaProgressService::class),
                themeManager: $themeManager,
                apiClient: $runtime->make(AliExpressApiClientInterface::class),
                catalogRepo: $runtime->make(DropshipCatalogRepositoryInterface::class),
                demandTracker: $runtime->make(\DomainSystem\Plugins\domain_drop\Contracts\DemandTrackerInterface::class)
            );
        });

        $runtime->singleton(ProductLinkScraperInterface::class, function () use ($runtime) {
            return new AliExpressLinkScraperService(
                $runtime->make(CategoryTaxonomyInterface::class)
            );
        });

        $runtime->singleton(ProductMediaSyncInterface::class, function () use ($runtime) {
            return new ProductMediaSyncService(
                repository: $runtime->make(DropshipCatalogRepositoryInterface::class),
                scraper: $runtime->make(ProductLinkScraperInterface::class),
                aiEnricher: $runtime->make(AiProductEnricherInterface::class),
                currencyConverter: $runtime->make(CurrencyConverterInterface::class),
                apiClient: $runtime->make(AliExpressApiClientInterface::class)
            );
        });

        $runtime->singleton(ProductMiningServiceInterface::class, function () use ($runtime) {
            return new ProductMiningService(
                apiClient: $runtime->make(AliExpressApiClientInterface::class),
                currencyConverter: $runtime->make(CurrencyConverterInterface::class),
                aiEnricher: $runtime->make(AiProductEnricherInterface::class),
                repository: $runtime->make(DropshipCatalogRepositoryInterface::class),
                linkScraper: $runtime->make(ProductLinkScraperInterface::class),
                demandMetricsRepo: $runtime->make(\DomainSystem\Plugins\domain_drop\Contracts\DemandMetricsRepositoryInterface::class),
                taxonomy: $runtime->make(CategoryTaxonomyInterface::class)
            );
        });

        $runtime->singleton(\DomainSystem\Plugins\domain_drop\Services\DropshipCustomerNotificationService::class, function () use ($runtime) {
            $mailer = null;
            try {
                $mailer = $runtime->getLink('core.mailer');
            } catch (Throwable $e) {
                $mailer = null;
            }

            return new \DomainSystem\Plugins\domain_drop\Services\DropshipCustomerNotificationService(
                mailer: $mailer instanceof EmailSenderInterface ? $mailer : null,
                storeName: 'RachidD Store',
                supportEmail: 'contato@rachidd.com'
            );
        });

        $runtime->singleton(DropshipAdminController::class, function () use ($runtime, $root) {
            return new DropshipAdminController(
                catalogRepo: $runtime->make(DropshipCatalogRepositoryInterface::class),
                miningService: $runtime->make(ProductMiningServiceInterface::class),
                currencyConverter: $runtime->make(CurrencyConverterInterface::class),
                session: $runtime->getLink('core.session'),
                orderRepo: $runtime->make(DropshipOrderRepositoryInterface::class),
                mediaSync: $runtime->make(ProductMediaSyncInterface::class),
                basePath: $root,
                customerRepo: $runtime->make(DropshipCustomerRepositoryInterface::class),
                disputeRepo: $runtime->make(DropshipDisputeRepositoryInterface::class),
                slaService: $runtime->make(OrderSlaProgressService::class),
                mediationService: $runtime->make(SellerMediationService::class),
                apiClient: $runtime->make(AliExpressApiClientInterface::class),
                metricsRepo: $runtime->make(\DomainSystem\Plugins\domain_drop\Contracts\DemandMetricsRepositoryInterface::class),
                logStorage: $runtime->make(\DomainSystem\Plugins\domain_drop\Contracts\DemandLogStorageInterface::class),
                notificationService: $runtime->make(\DomainSystem\Plugins\domain_drop\Services\DropshipCustomerNotificationService::class)
            );
        });

        $runtime->singleton(\DomainSystem\Plugins\domain_drop\Controllers\DropshipFrontController::class, function () use ($runtime) {
            $themeManager = null;
            try {
                $themeManager = $runtime->getLink('core.theme');
            } catch (Throwable $e) {
            }

            $session = null;
            try {
                $session = $runtime->make(\DomainSystem\Core\Contracts\SessionManagerInterface::class);
            } catch (Throwable $e) {
            }

            return new \DomainSystem\Plugins\domain_drop\Controllers\DropshipFrontController(
                catalogRepo: $runtime->make(DropshipCatalogRepositoryInterface::class),
                themeManager: $themeManager,
                miningService: $runtime->make(ProductMiningServiceInterface::class),
                orderRepo: $runtime->make(DropshipOrderRepositoryInterface::class),
                apiClient: $runtime->make(AliExpressApiClientInterface::class),
                demandTracker: $runtime->make(\DomainSystem\Plugins\domain_drop\Contracts\DemandTrackerInterface::class),
                session: $session,
                customerRepo: $runtime->make(DropshipCustomerRepositoryInterface::class)
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
                            'title' => 'Clientes & Compradores',
                            'url'   => '/admin/dropship/customers',
                            'icon'  => '👥',
                        ],
                        [
                            'title' => 'Inteligência de Demanda',
                            'url'   => '/admin/dropship/demand',
                            'icon'  => '📊',
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
            $router->addRoute('POST', '/admin/dropship/import-browser-source', [DropshipAdminController::class, 'importBrowserSource'], 'domain_drop', ['admin']);
            $router->addRoute('GET', '/admin/dropship/orders', [DropshipAdminController::class, 'orders'], 'domain_drop', ['admin']);
            $router->addRoute('POST', '/admin/dropship/orders/{id}/fulfillment', [DropshipAdminController::class, 'updateFulfillment'], 'domain_drop', ['admin']);
            $router->addRoute('POST', '/admin/dropship/orders/{id}/check-sdk', [DropshipAdminController::class, 'checkOrderSdk'], 'domain_drop', ['admin']);
            $router->addRoute('POST', '/admin/dropship/orders/{id}/mediate', [DropshipAdminController::class, 'mediateDispute'], 'domain_drop', ['admin']);
            $router->addRoute('GET', '/admin/dropship/customers', [DropshipAdminController::class, 'customers'], 'domain_drop', ['admin']);
            $router->addRoute('POST', '/admin/dropship/customers/{id}/status', [DropshipAdminController::class, 'updateCustomerStatus'], 'domain_drop', ['admin']);
            $router->addRoute('GET', '/admin/dropship/demand', [DropshipAdminController::class, 'demandLedger'], 'domain_drop', ['admin']);
            $router->addRoute('GET', '/admin/dropship/settings', [DropshipAdminController::class, 'settings'], 'domain_drop', ['admin']);
            $router->addRoute('POST', '/admin/dropship/settings', [DropshipAdminController::class, 'saveSettings'], 'domain_drop', ['admin']);
            $router->addRoute('POST', '/admin/dropship/test-connection', [DropshipAdminController::class, 'testConnection'], 'domain_drop', ['admin']);
            $router->addRoute('GET', '/admin/dropship/oauth/authorize', [DropshipAdminController::class, 'oauthAuthorize'], 'domain_drop', ['admin']);
            $router->addRoute('GET', '/admin/dropship/oauth/callback', [DropshipAdminController::class, 'oauthCallback'], 'domain_drop', ['admin']);
            $router->addRoute('POST', '/admin/dropship/product/{id}/sync-images', [DropshipAdminController::class, 'syncImages'], 'domain_drop', ['admin']);
            $router->addRoute('POST', '/admin/dropship/product/{id}/images', [DropshipAdminController::class, 'updateImages'], 'domain_drop', ['admin']);
            $router->addRoute('POST', '/admin/dropship/product/{id}/price', [DropshipAdminController::class, 'updatePrice'], 'domain_drop', ['admin']);
            $router->addRoute('POST', '/admin/dropship/run-daily-cycle', [DropshipAdminController::class, 'runDailyCycle'], 'domain_drop', ['admin']);
            $router->addRoute('POST', '/admin/dropship/save-pricing', [DropshipAdminController::class, 'savePricing'], 'domain_drop', ['admin']);
            $router->addRoute('GET', '/admin/dropship/sync-queue', [DropshipAdminController::class, 'syncQueue'], 'domain_drop', ['admin']);
            $router->addRoute('POST', '/admin/dropship/sync-all-images', [DropshipAdminController::class, 'syncAllImages'], 'domain_drop', ['admin']);
            $router->addRoute('POST', '/admin/dropship/delete/{id}', [DropshipAdminController::class, 'delete'], 'domain_drop', ['admin']);

            // 4. Rotas Públicas da Loja Virtual, Páginas Dedicadas & Área do Cliente
            $router->addRoute('GET', '/loja', [\DomainSystem\Plugins\domain_drop\Controllers\DropshipFrontController::class, 'storefront'], 'domain_drop');
            $router->addRoute('GET', '/moda-intima', [\DomainSystem\Plugins\domain_drop\Controllers\DropshipFrontController::class, 'fashionAndLingerie'], 'domain_drop');
            $router->addRoute('GET', '/roupas', [\DomainSystem\Plugins\domain_drop\Controllers\DropshipFrontController::class, 'fashionAndLingerie'], 'domain_drop');
            $router->addRoute('GET', '/sexshop', [\DomainSystem\Plugins\domain_drop\Controllers\DropshipFrontController::class, 'sexShop'], 'domain_drop');
            $router->addRoute('POST', '/api/drop/track-demand', [\DomainSystem\Plugins\domain_drop\Controllers\DropshipFrontController::class, 'trackDemand'], 'domain_drop');
            $router->addRoute('POST', '/api/drop/check-availability', [\DomainSystem\Plugins\domain_drop\Controllers\DropshipFrontController::class, 'checkAvailability'], 'domain_drop');
            $router->addRoute('POST', '/dropship/cron-tick', [\DomainSystem\Plugins\domain_drop\Controllers\DropshipFrontController::class, 'cronTick'], 'domain_drop');
            $router->addRoute('GET', '/produto/{slug}', [\DomainSystem\Plugins\domain_drop\Controllers\DropshipFrontController::class, 'singleProduct'], 'domain_drop');
            $router->addRoute('GET', '/rastrear', [\DomainSystem\Plugins\domain_drop\Controllers\DropshipFrontController::class, 'trackOrder'], 'domain_drop');
            $router->addRoute('GET', '/politica-de-envio', [\DomainSystem\Plugins\domain_drop\Controllers\DropshipFrontController::class, 'shippingPolicy'], 'domain_drop');

            // 5. Cadastro, Login, Pedidos e Reclamações de Clientes
            $router->addRoute('GET', '/cadastro', [CustomerAccountController::class, 'showRegister'], 'domain_drop');
            $router->addRoute('POST', '/cadastro', [CustomerAccountController::class, 'register'], 'domain_drop');
            $router->addRoute('GET', '/entrar', [CustomerAccountController::class, 'showLogin'], 'domain_drop');
            $router->addRoute('POST', '/entrar', [CustomerAccountController::class, 'login'], 'domain_drop');
            $router->addRoute('GET', '/sair', [CustomerAccountController::class, 'logout'], 'domain_drop');
            $router->addRoute('POST', '/sair', [CustomerAccountController::class, 'logout'], 'domain_drop');
            $router->addRoute('GET', '/minha-conta', [CustomerAccountController::class, 'account'], 'domain_drop');
            $router->addRoute('POST', '/minha-conta/endereco', [CustomerAccountController::class, 'updateAddress'], 'domain_drop');
            $router->addRoute('POST', '/minha-conta/reclamar', [CustomerAccountController::class, 'submitComplaint'], 'domain_drop');
            $router->addRoute('GET', '/dropship/disputes/attachment/{id}/{file}', [CustomerAccountController::class, 'serveAttachment'], 'domain_drop');
        });

        // 4. Integração com DomainCommerce (Resolução de Itens Vendáveis no Checkout)
        $runtime->onHook('commerce.register_item_resolvers', function ($resolver) use ($runtime) {
            if ($resolver instanceof PurchasableItemResolverInterface) {
                $resolveDropshipItem = function (int $id, array $context = []) use ($runtime) {
                    try {
                        /** @var DropshipCatalogRepositoryInterface $repo */
                        $repo = $runtime->make(DropshipCatalogRepositoryInterface::class);
                        $product = $repo->findById($id);
                        if ($product !== null) {
                            // Resolve SKU a partir do contexto explicitamente fornecido (sem superglobais)
                            $requestedSku = trim((string)($context['sku'] ?? ''));
                            if ($requestedSku === '') {
                                // Fallback retrocompatível caso não injetado no contexto
                                $requestedSku = trim((string)($_GET['sku'] ?? ($_POST['sku'] ?? '')));
                            }
                            return new DropshipProductPurchasableAdapter($product, $requestedSku !== '' ? $requestedSku : null);
                        }
                        return null;
                    } catch (Throwable $e) {
                        return null;
                    }
                };

                $resolver->registerResolver('dropship_product', $resolveDropshipItem);
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
                        $sellerOrigin = 'CN';
                        $expectedDays = 25;
                        $sellerName = null;
                        $productSlug = null;
                        $productTitle = null;
                        $aeProductId = null;

                        if ($itemId > 0) {
                            try {
                                /** @var DropshipCatalogRepositoryInterface $catRepo */
                                $catRepo = $runtime->make(DropshipCatalogRepositoryInterface::class);
                                $productDto = $catRepo->findById($itemId);
                                if ($productDto !== null) {
                                    $sellerInfo = $productDto->getSellerInfo();
                                    $isBr = !empty($sellerInfo['is_brazil']);
                                    $sellerOrigin = $isBr ? 'BR' : 'CN';
                                    $expectedDays = $isBr ? 7 : 25;
                                    $sellerName = (string)($sellerInfo['store_name'] ?? '');
                                    $productSlug = $productDto->slug;
                                    $productTitle = $productDto->title;
                                    $aeProductId = $productDto->aliexpressId;
                                }
                            } catch (Throwable $e) {
                            }
                        }

                        $newDropOrder = [
                            'order_ref'              => $orderRef,
                            'customer_name'          => (string)($payload['customer_name'] ?? 'Cliente'),
                            'customer_email'         => (string)($payload['customer_email'] ?? ''),
                            'customer_document'      => isset($payload['customer_document']) ? (string)$payload['customer_document'] : null,
                            'shipping_address'       => isset($payload['shipping_address']) ? $payload['shipping_address'] : null,
                            'status'                 => 'paid',
                            'total_paid_brl'         => (float)($payload['total'] ?? 0.0),
                            'seller_origin'          => $sellerOrigin,
                            'seller_name'            => $sellerName,
                            'expected_delivery_days' => $expectedDays,
                            'product_slug'           => $productSlug,
                            'product_title'          => $productTitle,
                            'aliexpress_product_id'  => $aeProductId,
                        ];
                        $orderRepo->create($newDropOrder);

                        // Se houver endereço e cliente cadastrado, atualiza endereço no cliente caso ainda não tenha
                        try {
                            /** @var DropshipCustomerRepositoryInterface $customerRepo */
                            $customerRepo = $runtime->make(DropshipCustomerRepositoryInterface::class);
                            $shipAddr = $payload['shipping_address'] ?? null;
                            $cEmail = (string)($payload['customer_email'] ?? '');
                            if (is_array($shipAddr) && !empty($shipAddr['zip']) && $cEmail !== '') {
                                $existingCustomer = $customerRepo->findByEmail($cEmail);
                                if ($existingCustomer !== null && (empty($existingCustomer['shipping_zip']) || empty($existingCustomer['shipping_address']))) {
                                    $customerRepo->updateAddress((int)$existingCustomer['id'], $shipAddr);
                                }
                            }
                        } catch (Throwable $e) {
                            // Isolamento gracioso
                        }

                        // Dispara e-mail de confirmação de pagamento para o cliente
                        try {
                            /** @var \DomainSystem\Plugins\domain_drop\Services\DropshipCustomerNotificationService $notifService */
                            $notifService = $runtime->make(\DomainSystem\Plugins\domain_drop\Services\DropshipCustomerNotificationService::class);
                            $notifService->notifyOrderPaid($newDropOrder);
                        } catch (Throwable $e) {
                            // Isolamento gracioso
                        }
                    } else {
                        $orderRepo->updateFulfillment($orderRef, 'paid');
                    }

                    // Registra conversão de venda no Livro de Demanda com identidade completa do comprador
                    try {
                        /** @var \DomainSystem\Plugins\domain_drop\Contracts\DemandTrackerInterface $demandTracker */
                        $demandTracker = $runtime->make(\DomainSystem\Plugins\domain_drop\Contracts\DemandTrackerInterface::class);
                        $cId = (int)($payload['user_id'] ?? 0);
                        $cName = trim((string)($payload['customer_name'] ?? ''));
                        $cEmail = strtolower(trim((string)($payload['customer_email'] ?? '')));
                        $sessionId = session_id() ?: null;

                        $demandTracker->recordInteraction(new \DomainSystem\Plugins\domain_drop\DTO\DemandEvent(
                            productId: $aeProductId ?: (string)$itemId,
                            productTitle: $productTitle,
                            categorySlug: 'geral',
                            actionType: 'conversion',
                            sessionId: $sessionId,
                            customerId: $cId ?: null,
                            customerName: $cName ?: null,
                            customerEmail: $cEmail ?: null,
                            userType: ($cId > 0 || $cEmail !== '') ? 'cliente_logado' : 'externo'
                        ));
                    } catch (Throwable $e) {}
                } catch (Throwable $e) {
                    error_log('[DomainDrop] Falha ao registrar pedido em payment.approved: ' . $e->getMessage());
                }
            }
        });

        // 5.0.1 Sincronização de Reembolso do Commerce -> DomainDrop
        $runtime->onHook('payment.refunded', function ($payload) use ($runtime) {
            if (!is_array($payload)) {
                return;
            }
            $itemType = (string)($payload['item_type'] ?? '');
            $orderRef = (string)($payload['order_ref'] ?? '');
            if ($orderRef !== '' && ($itemType === '' || in_array($itemType, ['dropship_product', 'dropship', 'product'], true))) {
                try {
                    /** @var DropshipOrderRepositoryInterface $orderRepo */
                    $orderRepo = $runtime->make(DropshipOrderRepositoryInterface::class);
                    $existing = $orderRepo->findByOrderRef($orderRef);
                    if ($existing !== null) {
                        $orderRepo->updateFulfillment($orderRef, 'refunded');
                    }
                } catch (Throwable $e) {
                    error_log('[DomainDrop] Falha ao atualizar reembolso em payment.refunded: ' . $e->getMessage());
                }
            }
        });

        // 5.1 Registro de Intenção e Atribuição de Demanda ao Gerar Pedido no Checkout
        $runtime->onHook('dropship.order_created', function ($payload) use ($runtime) {
            if (!is_array($payload)) {
                return;
            }
            $itemType = (string)($payload['item_type'] ?? '');
            if (!in_array($itemType, ['dropship_product', 'dropship', 'product'], true)) {
                return;
            }

            try {
                /** @var \DomainSystem\Plugins\domain_drop\Contracts\DemandTrackerInterface $demandTracker */
                $demandTracker = $runtime->make(\DomainSystem\Plugins\domain_drop\Contracts\DemandTrackerInterface::class);
                $sessionId = session_id() ?: '';
                $cId = (int)($payload['user_id'] ?? 0);
                $cName = trim((string)($payload['customer_name'] ?? ''));
                $cEmail = strtolower(trim((string)($payload['customer_email'] ?? '')));

                $clientIp = \DomainSystem\Plugins\domain_drop\DTO\DemandEvent::resolveClientIp();
                if ($cId > 0) {
                    $demandTracker->promoteSession($sessionId, $cId, $cName, $cEmail, $clientIp);
                }
            } catch (Throwable $e) {
                // Isolamento gracioso
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
