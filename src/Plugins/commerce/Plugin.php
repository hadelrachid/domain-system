<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\commerce;

use DomainSystem\Core\Contracts\EventDispatcherInterface;
use DomainSystem\Core\Contracts\OsConnectorInterface;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Contracts\OsRuntimeInterface;
use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Plugins\commerce\Contracts\CommerceSettingsInterface;
use DomainSystem\Plugins\commerce\Contracts\OrderRepositoryInterface;
use DomainSystem\Plugins\commerce\Contracts\PaymentGatewayRegistryInterface;
use DomainSystem\Plugins\commerce\Contracts\PurchasableItemResolverInterface;
use DomainSystem\Plugins\commerce\Controllers\CheckoutController;
use DomainSystem\Plugins\commerce\Controllers\OrdersAdminController;
use DomainSystem\Plugins\commerce\Controllers\WebhookController;
use DomainSystem\Plugins\commerce\Repositories\PdoOrderRepository;
use DomainSystem\Plugins\commerce\Services\Gateways\ClickBankGateway;
use DomainSystem\Plugins\commerce\Services\Gateways\EduzzGateway;
use DomainSystem\Plugins\commerce\Services\Gateways\HotmartGateway;
use DomainSystem\Plugins\commerce\Services\Gateways\MercadoPagoGateway;
use DomainSystem\Plugins\commerce\Services\Gateways\MonetizzeGateway;
use DomainSystem\Plugins\commerce\Services\Gateways\PayPalGateway;
use DomainSystem\Plugins\commerce\Services\Gateways\StripeGateway;
use DomainSystem\Plugins\commerce\Services\JsonCommerceSettings;
use DomainSystem\Plugins\commerce\Services\PaymentGatewayRegistry;
use DomainSystem\Plugins\commerce\Services\PurchasableItemResolver;

class Plugin extends AbstractPlugin implements OsExtensionInterface
{
    public function register(): void {}

    public function osRegister(OsConnectorInterface $os): void
    {
        $os->requireLink('core.db.schema');
        $os->requireLink('core.db');
        $os->requireLink('core.events');
        $os->listenHook('router.register');
        $os->listenHook('admin.menu');
    }

    public function osBoot(OsRuntimeInterface $runtime): void
    {
        // 1. Configurações do DomainCommerce (DIP)
        $runtime->singleton(CommerceSettingsInterface::class, function () {
            return new JsonCommerceSettings();
        });

        // 2. Repositório de Pedidos Financeiros (DIP)
        $runtime->singleton(OrderRepositoryInterface::class, function () use ($runtime) {
            return new PdoOrderRepository($runtime->getLink('core.db'));
        });

        // 3. Registro e Resolução de Gateways de Pagamento (Mercado Pago, PayPal, Stripe e Extensíveis)
        $runtime->singleton(PaymentGatewayRegistryInterface::class, function () use ($runtime) {
            /** @var CommerceSettingsInterface $settings */
            $settings = $runtime->make(CommerceSettingsInterface::class);

            $registry = new PaymentGatewayRegistry();
            $registry->register(new MercadoPagoGateway(settings: $settings));
            $registry->register(new PayPalGateway(settings: $settings));
            $registry->register(new StripeGateway(settings: $settings));
            $registry->register(new HotmartGateway(settings: $settings));
            $registry->register(new EduzzGateway(settings: $settings));
            $registry->register(new MonetizzeGateway(settings: $settings));
            $registry->register(new ClickBankGateway(settings: $settings));

            // Permite que outros plugins registrem gateways adicionais via Hook (ex: Asaas, PagSeguro)
            try {
                $dispatcher = $runtime->getLink('core.events');
                if ($dispatcher instanceof EventDispatcherInterface) {
                    $dispatcher->dispatch('commerce.register_gateways', $registry);
                }
            } catch (\Throwable $e) {
                // Silencia se não houver listeners externos
            }

            return $registry;
        });

        // 4. Resolvedor Desacoplado de Itens Vendíveis (SOLID / DIP)
        $runtime->singleton(PurchasableItemResolverInterface::class, function () use ($runtime) {
            $resolver = new PurchasableItemResolver();
            try {
                $dispatcher = $runtime->getLink('core.events');
                if ($dispatcher instanceof EventDispatcherInterface) {
                    $dispatcher->dispatch('commerce.register_item_resolvers', $resolver);
                }
            } catch (\Throwable $e) {
                // Silencia se não houver listeners externos
            }
            return $resolver;
        });

        // 5. Menu Administrativo do DomainCommerce
        $runtime->onHook('admin.menu', function ($menus, $role = 'guest') {
            if ($role === 'admin') {
                $menus[] = [
                    'title' => 'DomainCommerce',
                    'url'   => '/admin/commerce',
                    'icon'  => '🛒',
                ];
            }
            return $menus;
        });

        // 6. Rotas Públicas e Administrativas
        $runtime->onHook('router.register', function (Router $router) {
            // Front / Checkout & Webhooks
            $router->addRoute('GET', '/checkout/curso/{id}', [CheckoutController::class, 'showCheckout'], 'commerce');
            $router->addRoute('GET', '/checkout/produto/{id}', [CheckoutController::class, 'showProductCheckout'], 'commerce');
            $router->addRoute('GET', '/checkout/sucesso/{orderRef}', [CheckoutController::class, 'orderSuccess'], 'commerce');
            $router->addRoute('POST', '/checkout/process', [CheckoutController::class, 'process'], 'commerce');
            $router->addRoute('GET', '/checkout/status/{orderRef}', [CheckoutController::class, 'checkStatus'], 'commerce');
            $router->addRoute('GET', '/checkout/stripe/success', [CheckoutController::class, 'stripeSuccess'], 'commerce');
            $router->addRoute('GET', '/checkout/paypal/success', [CheckoutController::class, 'paypalSuccess'], 'commerce');
            $router->addRoute('POST', '/webhook/payment/{gateway}', [WebhookController::class, 'handle'], 'commerce');

            // Admin / Painel de Vendas e Configuração de Gateways
            $router->addRoute('GET', '/admin/commerce', [OrdersAdminController::class, 'index'], 'commerce', ['admin']);
            $router->addRoute('GET', '/admin/commerce/export', [OrdersAdminController::class, 'export'], 'commerce', ['admin']);
            $router->addRoute('GET', '/admin/commerce/settings', [OrdersAdminController::class, 'settings'], 'commerce', ['admin']);
            $router->addRoute('POST', '/admin/commerce/settings', [OrdersAdminController::class, 'saveSettings'], 'commerce', ['admin']);
            $router->addRoute('POST', '/admin/commerce/test-mercadopago', [OrdersAdminController::class, 'testMercadoPago'], 'commerce', ['admin']);
            $router->addRoute('POST', '/admin/commerce/test-stripe', [OrdersAdminController::class, 'testStripe'], 'commerce', ['admin']);
            $router->addRoute('POST', '/admin/commerce/test-paypal', [OrdersAdminController::class, 'testPayPal'], 'commerce', ['admin']);
            $router->addRoute('POST', '/admin/commerce/orders/{id}/status', [OrdersAdminController::class, 'updateOrderStatus'], 'commerce', ['admin']);
        });
    }

    public function activate(OsRuntimeInterface $runtime): void
    {
        $db = $runtime->getLink('core.db');
        $orderRepo = new PdoOrderRepository($db);
        $orderRepo->ensureTable();
    }
}
