<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Core\Contracts\EventDispatcherInterface;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Plugins\commerce\Controllers\OrdersAdminController;
use DomainSystem\Plugins\commerce\Repositories\PdoOrderRepository;
use DomainSystem\Plugins\commerce\Services\Gateways\MercadoPagoGateway;
use DomainSystem\Plugins\commerce\Services\Gateways\PayPalGateway;
use DomainSystem\Plugins\commerce\Services\Gateways\StripeGateway;
use DomainSystem\Plugins\commerce\Services\JsonCommerceSettings;
use DomainSystem\Plugins\commerce\Services\PaymentGatewayRegistry;
use PDO;
use PHPUnit\Framework\TestCase;

final class CommerceSettingsAndStripeGatewayTest extends TestCase
{
    private string $tempSettingsFile;
    private PDO $pdo;
    private PdoOrderRepository $orderRepo;

    protected function setUp(): void
    {
        $this->tempSettingsFile = sys_get_temp_dir() . '/ds_test_settings_' . bin2hex(random_bytes(4)) . '.json';
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->orderRepo = new PdoOrderRepository($this->pdo);
    }

    protected function tearDown(): void
    {
        if (is_file($this->tempSettingsFile)) {
            @unlink($this->tempSettingsFile);
        }
    }

    public function testJsonCommerceSettingsCanSaveAndRetrieveSettings(): void
    {
        $settings = new JsonCommerceSettings($this->tempSettingsFile);
        $this->assertNull($settings->get('non_existent'));
        $this->assertSame('default_val', $settings->get('non_existent', 'default_val'));

        $settings->set('mercadopago_enabled', true);
        $settings->set('active_currency', 'BRL');

        $this->assertTrue($settings->get('mercadopago_enabled'));
        $this->assertSame('BRL', $settings->get('active_currency'));

        // Salvar em lote
        $saved = $settings->save([
            'mercadopago_access_token' => 'TEST_TOKEN_123',
            'paypal_client_id'         => 'PAYPAL_CLIENT_999',
        ]);
        $this->assertTrue($saved);

        // Recarrega instância separada apontando para o mesmo arquivo
        $fresh = new JsonCommerceSettings($this->tempSettingsFile);
        $this->assertSame('TEST_TOKEN_123', $fresh->get('mercadopago_access_token'));
        $this->assertSame('PAYPAL_CLIENT_999', $fresh->get('paypal_client_id'));
        $this->assertTrue($fresh->get('mercadopago_enabled'));
    }

    public function testStripeGatewaySimulationAndWebhook(): void
    {
        $settings = new JsonCommerceSettings($this->tempSettingsFile);
        $settings->set('commerce_demo_mode', true);
        $stripe = new StripeGateway(settings: $settings);

        $this->assertSame('stripe', $stripe->getId());
        $this->assertStringContainsString('Stripe', $stripe->getTitle());
        $this->assertFalse($stripe->isConfigured());

        // Processa pagamento em modo sandbox / simulação
        $result = $stripe->processPayment([
            'order_ref'     => 'ORD_STRIPE_1',
            'total'         => 199.90,
            'student_name'  => 'Maria Aluna',
            'student_email' => 'maria@teste.com',
        ], ['method' => 'credit_card']);

        $this->assertTrue($result->isApproved());
        $this->assertSame('ORD_STRIPE_1', $result->orderRef);
        $this->assertSame('stripe', $result->paymentMethod);

        // Webhook do Stripe com assinatura válida (checkout.session.completed)
        $secret = 'whsec_test_secret';
        $settings->set('stripe_webhook_secret', $secret);

        $webhookPayload = [
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id'                  => 'cs_test_123456',
                    'client_reference_id' => 'ORD_STRIPE_1',
                ],
            ],
        ];
        $rawJson = (string)json_encode($webhookPayload);
        $ts = time();
        $sig = 't=' . $ts . ',v1=' . hash_hmac('sha256', $ts . '.' . $rawJson, $secret);

        $requestWebhook = new Request([], $webhookPayload, ['HTTP_STRIPE_SIGNATURE' => $sig], [], [], $webhookPayload, $rawJson);

        $webhookResult = $stripe->handleWebhook($requestWebhook);
        $this->assertNotNull($webhookResult);
        $this->assertSame('ORD_STRIPE_1', $webhookResult['order_ref']);
        $this->assertSame('approved', $webhookResult['status']);
        $this->assertSame('cs_test_123456', $webhookResult['transaction_id']);

        // Webhook ignorado para eventos desconhecidos
        $requestIgnore = new Request([], ['type' => 'customer.created'], ['HTTP_STRIPE_SIGNATURE' => $sig], [], [], ['type' => 'customer.created'], (string)json_encode(['type' => 'customer.created']));
        $this->assertNull($stripe->handleWebhook($requestIgnore));
    }

    public function testOrderRepositoryGetAllOrdersAndMetrics(): void
    {
        // Cria 3 pedidos: 2 pagos e 1 pendente
        $this->orderRepo->createOrder([
            'user_id'        => 1,
            'item_id'        => 10,
            'total'          => 100.00,
            'status'         => 'paid',
            'gateway'        => 'mercadopago',
            'payment_method' => 'pix',
            'order_ref'      => 'ORD_A',
        ]);

        $this->orderRepo->createOrder([
            'user_id'        => 2,
            'item_id'        => 10,
            'total'          => 200.00,
            'status'         => 'paid',
            'gateway'        => 'stripe',
            'payment_method' => 'credit_card',
            'order_ref'      => 'ORD_B',
        ]);

        $this->orderRepo->createOrder([
            'user_id'        => 3,
            'item_id'        => 12,
            'total'          => 50.00,
            'status'         => 'pending',
            'gateway'        => 'mercadopago',
            'payment_method' => 'pix',
            'order_ref'      => 'ORD_C',
        ]);

        $allOrders = $this->orderRepo->getAllOrders(10);
        $this->assertCount(3, $allOrders);
        $this->assertSame('ORD_C', $allOrders[0]['order_ref']); // Mais recente primeiro

        $metrics = $this->orderRepo->getMetrics();
        $this->assertSame(3, $metrics['total_orders']);
        $this->assertEquals(300.00, (float)$metrics['total_sales']);
        $this->assertSame(2, $metrics['paid_count']);
        $this->assertSame(1, $metrics['pending_count']);
    }

    public function testOrdersAdminControllerDashboardAndSettingsWorkflow(): void
    {
        $session = $this->createMock(SessionManagerInterface::class);
        $session->method('getCsrfToken')->willReturn('token_admin_csrf_123');

        $registry = new PaymentGatewayRegistry();
        $registry->register(new MercadoPagoGateway());
        $registry->register(new PayPalGateway());
        $registry->register(new StripeGateway());

        $settings = new JsonCommerceSettings($this->tempSettingsFile);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $controller = new OrdersAdminController(
            $session,
            $this->orderRepo,
            $registry,
            $settings,
            $dispatcher
        );

        $request = new Request();

        // 1. Renderiza Dashboard
        $indexResponse = $controller->index($request);
        $this->assertSame(200, $indexResponse->getStatusCode());
        $indexContent = $indexResponse->getContent();
        $this->assertStringContainsString('DomainCommerce', $indexContent);

        // 2. Renderiza Configurações
        $settingsResponse = $controller->settings($request);
        $this->assertSame(200, $settingsResponse->getStatusCode());
        $settingsContent = $settingsResponse->getContent();
        $this->assertStringContainsString('Configurações de Pagamentos', $settingsContent);

        // 3. Salva Configurações via POST
        $saveRequest = new Request(request: [
            'csrf_token'               => 'token_admin_csrf_123',
            'mercadopago_enabled'      => '1',
            'mercadopago_access_token' => 'TOKEN_MP_LIVE_ABC',
            'paypal_enabled'           => '1',
            'active_currency'          => 'BRL',
        ]);

        $saveResponse = $controller->saveSettings($saveRequest);
        $this->assertSame(302, $saveResponse->getStatusCode());
        $this->assertSame('TOKEN_MP_LIVE_ABC', $settings->get('mercadopago_access_token'));
        $this->assertTrue($settings->get('mercadopago_enabled'));

        // 4. Cria pedido pendente e aprova manualmente via Admin
        $orderId = $this->orderRepo->createOrder([
            'user_id'        => 55,
            'item_id'        => 22,
            'total'          => 250.00,
            'status'         => 'pending',
            'gateway'        => 'mercadopago',
            'payment_method' => 'pix',
            'order_ref'      => 'ORD_MANUAL_APPROVE',
        ]);

        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with('payment.approved', [
                'user_id'   => 55,
                'course_id' => 22,
                'order_ref' => 'ORD_MANUAL_APPROVE',
            ]);

        $approveRequest = new Request(request: [
            'csrf_token' => 'token_admin_csrf_123',
            'status'     => 'paid',
        ]);

        $approveResponse = $controller->updateOrderStatus($approveRequest, $orderId);
        $this->assertSame(200, $approveResponse->getStatusCode());
        $data = json_decode($approveResponse->getContent(), true);
        $this->assertTrue($data['success']);

        $updatedOrder = $this->orderRepo->findById($orderId);
        $this->assertSame('paid', $updatedOrder['status']);
    }

    public function testStripeAndPayPalTestCredentialsValidation(): void
    {
        $settings = new JsonCommerceSettings($this->tempSettingsFile);
        $stripe = new StripeGateway(settings: $settings);
        $pp = new PayPalGateway(settings: $settings);

        // Sem credenciais
        $resStripeEmpty = $stripe->testCredentials('');
        $this->assertFalse($resStripeEmpty['ok']);
        $this->assertStringContainsString('Nenhuma Secret Key', $resStripeEmpty['message']);

        $resPpEmpty = $pp->testCredentials('', '');
        $this->assertFalse($resPpEmpty['ok']);
        $this->assertStringContainsString('Client ID e Client Secret', $resPpEmpty['message']);
    }

    public function testCheckoutControllerStripeAndPayPalSuccessCallbacks(): void
    {
        $orderIdStripe = $this->orderRepo->createOrder([
            'user_id'        => 70,
            'item_id'        => 15,
            'total'          => 180.00,
            'status'         => 'pending',
            'gateway'        => 'stripe',
            'payment_method' => 'stripe',
            'order_ref'      => 'ORD_STRIPE_CALLBACK',
        ]);

        $orderIdPp = $this->orderRepo->createOrder([
            'user_id'        => 80,
            'item_id'        => 16,
            'total'          => 220.00,
            'status'         => 'pending',
            'gateway'        => 'paypal',
            'payment_method' => 'paypal',
            'order_ref'      => 'ORD_PAYPAL_CALLBACK',
        ]);

        $settings = new JsonCommerceSettings($this->tempSettingsFile);
        $settings->set('commerce_demo_mode', true);

        $session = $this->createMock(SessionManagerInterface::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $registry = new PaymentGatewayRegistry();
        $registry->register(new StripeGateway(settings: $settings));
        $registry->register(new PayPalGateway(settings: $settings));

        $controller = new \DomainSystem\Plugins\commerce\Controllers\CheckoutController(
            session: $session,
            orderRepo: $this->orderRepo,
            gatewayRegistry: $registry,
            dispatcher: $dispatcher,
            settings: $settings
        );

        // 1. Stripe Success Callback
        $reqStripe = new Request(query: ['order_ref' => 'ORD_STRIPE_CALLBACK', 'session_id' => 'cs_test_mock']);
        $respStripe = $controller->stripeSuccess($reqStripe);
        $this->assertSame(302, $respStripe->getStatusCode());

        $paidStripeOrder = $this->orderRepo->findById($orderIdStripe);
        $this->assertSame('paid', $paidStripeOrder['status']);

        // 2. PayPal Success Callback
        $reqPp = new Request(query: ['order_ref' => 'ORD_PAYPAL_CALLBACK', 'token' => 'PP_ORDER_MOCK']);
        $respPp = $controller->paypalSuccess($reqPp);
        $this->assertSame(302, $respPp->getStatusCode());

        $paidPpOrder = $this->orderRepo->findById($orderIdPp);
        $this->assertSame('paid', $paidPpOrder['status']);
    }
}
