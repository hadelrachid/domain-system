<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Core\Contracts\EventDispatcherInterface;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Plugins\academy\Contracts\CourseRepositoryInterface;
use DomainSystem\Plugins\commerce\Contracts\CommerceSettingsInterface;
use DomainSystem\Plugins\commerce\Controllers\CheckoutController;
use DomainSystem\Plugins\commerce\Controllers\WebhookController;
use DomainSystem\Plugins\commerce\DTO\PaymentResult;
use DomainSystem\Plugins\commerce\Repositories\PdoOrderRepository;
use DomainSystem\Plugins\commerce\Services\Gateways\MercadoPagoGateway;
use DomainSystem\Plugins\commerce\Services\Gateways\PayPalGateway;
use DomainSystem\Plugins\commerce\Services\PaymentGatewayRegistry;
use PDO;
use PHPUnit\Framework\TestCase;

final class CommercePaymentGatewayTest extends TestCase
{
    private PDO $pdo;
    private PdoOrderRepository $orderRepo;
    private CommerceSettingsInterface $settings;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->orderRepo = new PdoOrderRepository($this->pdo);

        $this->settings = $this->createMock(CommerceSettingsInterface::class);
        $this->settings->method('get')->willReturnCallback(function ($k, $def = null) {
            if ($k === 'commerce_demo_mode') {
                return true;
            }
            return $def;
        });
    }

    public function testOrderRepositoryLifecycleCreateFindUpdate(): void
    {
        $orderId = $this->orderRepo->createOrder([
            'user_id'        => 10,
            'item_type'      => 'course',
            'item_id'        => 5,
            'total'          => 150.00,
            'status'         => 'pending',
            'gateway'        => 'mercadopago',
            'payment_method' => 'pix',
            'order_ref'      => 'ORD_TEST_1001',
        ]);

        $this->assertGreaterThan(0, $orderId);

        // Busca por ID
        $order = $this->orderRepo->findById($orderId);
        $this->assertNotNull($order);
        $this->assertSame('ORD_TEST_1001', $order['order_ref']);
        $this->assertSame('pending', $order['status']);
        $this->assertEquals(150.00, (float)$order['total']);

        // Busca por Ref
        $orderRef = $this->orderRepo->findByOrderRef('ORD_TEST_1001');
        $this->assertNotNull($orderRef);
        $this->assertSame($orderId, (int)$orderRef['id']);

        // Atualiza status para pago
        $updated = $this->orderRepo->updateStatus($orderId, 'paid', 'TX_MP_999');
        $this->assertTrue($updated);

        $orderPaid = $this->orderRepo->findById($orderId);
        $this->assertSame('paid', $orderPaid['status']);
        $this->assertSame('TX_MP_999', $orderPaid['transaction_id']);

        // Histórico de pedidos do aluno
        $userOrders = $this->orderRepo->getUserOrders(10);
        $this->assertCount(1, $userOrders);
    }

    public function testPaymentGatewayRegistryResolvesGateways(): void
    {
        $registry = new PaymentGatewayRegistry();
        $mp = new MercadoPagoGateway();
        $pp = new PayPalGateway();

        $registry->register($mp);
        $registry->register($pp);

        $this->assertSame($mp, $registry->get('mercadopago'));
        $this->assertSame($pp, $registry->get('paypal'));
        $this->assertSame($mp, $registry->getDefault());
        $this->assertCount(2, $registry->getAll());
    }

    public function testMercadoPagoGatewayGeneratesPixPayload(): void
    {
        $mp = new MercadoPagoGateway(settings: $this->settings);

        $result = $mp->processPayment(
            [
                'order_ref'     => 'ORD_PIX_555',
                'total'         => 97.00,
                'description'   => 'Curso Academy',
                'student_name'  => 'Maria Silva',
                'student_email' => 'maria@teste.com',
            ],
            ['method' => 'pix']
        );

        $this->assertTrue($result->success);
        $this->assertSame('pending', $result->status);
        $this->assertSame('pix', $result->paymentMethod);
        $this->assertNotEmpty($result->pixCopiaECola);
        $this->assertStringContainsString('00020126580014BR.GOV.BCB.PIX', $result->pixCopiaECola);
    }

    public function testMercadoPagoGatewayCheckoutProPreferenceGenerationInDemoMode(): void
    {
        $mp = new MercadoPagoGateway(settings: $this->settings);

        $result = $mp->processPayment(
            [
                'order_ref'     => 'ORD_PRO_888',
                'total'         => 149.90,
                'description'   => 'Curso Avançado',
                'student_name'  => 'Hadel Rachid',
                'student_email' => 'hadel@teste.com',
            ],
            ['method' => 'checkout_pro']
        );

        $this->assertTrue($result->success);
        $this->assertSame('pending', $result->status);
        $this->assertSame('mercadopago', $result->paymentMethod);
        $this->assertNotNull($result->redirectUrl);
        $this->assertStringContainsString('aluno/painel', $result->redirectUrl);
        $this->assertStringContainsString('payment_status=approved', $result->redirectUrl);
    }

    public function testPayPalGatewayGeneratesApprovedDemoPayment(): void
    {
        $pp = new PayPalGateway(settings: $this->settings);

        $result = $pp->processPayment(
            [
                'order_ref' => 'ORD_PP_777',
                'total'     => 120.00,
            ],
            ['method' => 'paypal']
        );

        $this->assertTrue($result->success);
        $this->assertSame('approved', $result->status);
        $this->assertSame('paypal', $result->paymentMethod);
    }

    public function testCheckoutControllerAndWebhookDispatchesPaymentApprovedEvent(): void
    {
        $session = $this->createMock(SessionManagerInterface::class);
        $session->method('get')->willReturnCallback(function ($k) {
            return match ($k) {
                'user_id'    => 25,
                'user_name'  => 'Carlos Aluno',
                'user_email' => 'carlos@aluno.com',
                default      => null,
            };
        });
        $session->method('getCsrfToken')->willReturn('token_checkout_valido');

        $courseRepo = $this->createMock(CourseRepositoryInterface::class);
        $courseRepo->method('findById')->with(8)->willReturn([
            'id'     => 8,
            'title'  => 'Curso de Arquitetura PHP',
            'price'  => 297.00,
            'status' => 'published',
        ]);

        $registry = new PaymentGatewayRegistry();
        $mp = new MercadoPagoGateway(settings: $this->settings);
        $registry->register($mp);

        $dispatchedEvents = [];
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(function ($event, $payload = null) use (&$dispatchedEvents) {
            $dispatchedEvents[] = ['event' => $event, 'payload' => $payload];
            return null;
        });

        $checkoutController = new CheckoutController(
            $session,
            $this->orderRepo,
            $registry,
            $courseRepo,
            $dispatcher,
            null,
            $this->settings
        );

        // 1. Processa pedido Pix via Checkout
        $requestCheckout = new Request(
            [],
            [
                'csrf_token'     => 'token_checkout_valido',
                'course_id'      => 8,
                'gateway'        => 'mercadopago',
                'payment_method' => 'pix',
            ],
            ['REQUEST_METHOD' => 'POST']
        );

        $checkoutResponse = $checkoutController->process($requestCheckout);
        $this->assertSame(200, $checkoutResponse->getStatusCode());
        $checkoutData = json_decode($checkoutResponse->getContent(), true);

        $this->assertTrue($checkoutData['success']);
        $this->assertSame('pending', $checkoutData['status']);
        $orderRef = $checkoutData['order_ref'];
        $this->assertNotEmpty($orderRef);

        // Pedido criado com status 'pending'
        $orderInDb = $this->orderRepo->findByOrderRef($orderRef);
        $this->assertSame('pending', $orderInDb['status']);

        // 2. Banco confirma o Pix e envia Webhook oficial para o Domain System
        $webhookController = new WebhookController(
            $this->orderRepo,
            $registry,
            $dispatcher
        );

        $requestWebhook = new Request(
            [],
            [
                'id'        => '123456789',
                'type'      => 'payment',
                'order_ref' => $orderRef,
            ],
            ['REQUEST_METHOD' => 'POST']
        );

        $webhookResponse = $webhookController->handle($requestWebhook, 'mercadopago');
        $this->assertSame(200, $webhookResponse->getStatusCode());

        // 3. O pedido foi atualizado para 'paid'
        $orderPaid = $this->orderRepo->findByOrderRef($orderRef);
        $this->assertSame('paid', $orderPaid['status']);

        // 4. O evento 'payment.approved' foi disparado no Kernel para matricular o aluno no Academy
        $this->assertCount(1, $dispatchedEvents);
        $this->assertSame('payment.approved', $dispatchedEvents[0]['event']);
        $this->assertSame(25, $dispatchedEvents[0]['payload']['user_id']);
        $this->assertSame(8, $dispatchedEvents[0]['payload']['course_id']);
        $this->assertSame($orderRef, $dispatchedEvents[0]['payload']['order_ref']);
    }

    public function testMercadoPagoTestCredentialsMethodReturnsStructuredResults(): void
    {
        $mp = new MercadoPagoGateway();

        // Sem credenciais
        $resEmpty = $mp->testCredentials('', '');
        $this->assertFalse($resEmpty['access_token']['ok']);
        $this->assertFalse($resEmpty['public_key']['ok']);

        // Com credencial fictícia de teste (falha na API externa)
        $resFake = $mp->testCredentials('TEST-invalid-token', 'TEST-invalid-key');
        $this->assertFalse($resFake['access_token']['ok']);
        $this->assertFalse($resFake['public_key']['ok']);
        $this->assertNotEmpty($resFake['access_token']['message']);
        $this->assertNotEmpty($resFake['public_key']['message']);
    }

    public function testCheckoutControllerResolvesCourseImageAndRendersCleanView(): void
    {
        $session = $this->createMock(SessionManagerInterface::class);
        $session->method('get')->willReturnCallback(function ($k, $def = null) {
            return match ($k) {
                'academy_student_id'   => 10,
                'user_id'              => 10,
                'academy_student_name' => 'Hadel Rachid',
                'academy_student_email'=> 'hadel@teste.com',
                default                => $def,
            };
        });
        $session->method('getCsrfToken')->willReturn('token_csrf_checkout');

        $courseRepo = $this->createMock(CourseRepositoryInterface::class);
        $courseRepo->method('findById')->with(7)->willReturn([
            'id'          => 7,
            'title'       => 'Curso de PHP Completo',
            'slug'        => 'curso-de-php-completo',
            'price'       => 10.00,
            'status'      => 'published',
            'thumbnail'   => 'uploads/academy/course_test.png',
        ]);

        $registry = new PaymentGatewayRegistry();
        $registry->register(new MercadoPagoGateway(settings: $this->settings));

        $controller = new CheckoutController(
            $session,
            $this->orderRepo,
            $registry,
            $courseRepo,
            null,
            null,
            $this->settings
        );

        $request = new Request([], [], ['REQUEST_METHOD' => 'GET']);
        $response = $controller->showCheckout($request, 7);

        $this->assertSame(200, $response->getStatusCode());
        $html = $response->getContent();

        // 1. Resumo da Compra e Capa Ilustrativa
        $this->assertStringContainsString('Resumo da Compra', $html);
        $this->assertStringContainsString('Curso de PHP Completo', $html);
        $this->assertStringContainsString('course_test.png', $html);
        $this->assertStringContainsString('10,00', $html);

        // 2. Notificações Toast do core e zero alert()
        $this->assertStringContainsString('assets/js/core/toast.js', $html);
        $this->assertStringNotContainsString("alert(", $html);
    }

    public function testRegistryPreventsSilentOverwriteUnlessExplicit(): void
    {
        $registry = new PaymentGatewayRegistry();
        $g1 = new MercadoPagoGateway();
        $g2 = new MercadoPagoGateway();

        $registry->register($g1);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage("O gateway de pagamento com identificador 'mercadopago' já está registrado.");

        // Segunda tentativa sem overwrite => exceção
        $registry->register($g2);
    }

    public function testRegistryAllowsOverwriteWhenExplicitlyEnabled(): void
    {
        $registry = new PaymentGatewayRegistry();
        $g1 = new MercadoPagoGateway();
        $g2 = new MercadoPagoGateway();

        $registry->register($g1);
        $registry->register($g2, overwrite: true);

        $this->assertSame($g2, $registry->get('mercadopago'));
    }

    public function testMercadoPagoRequiresCpfAndEmailInProduction(): void
    {
        // Gateway com access_token de produção (sem prefixo TEST-) e sandbox desativado
        $prodSettings = $this->createMock(CommerceSettingsInterface::class);
        $prodSettings->method('get')->willReturnMap([
            ['mercadopago_access_token', '', 'APP_USR-1234567890-PROD-TOKEN'],
            ['mercadopago_public_key', '', 'APP_USR-1234567890-PROD-KEY'],
            ['mercadopago_sandbox', false, false],
        ]);

        $mp = new MercadoPagoGateway('APP_USR-1234567890-PROD-TOKEN', 'APP_USR-1234567890-PROD-KEY', $prodSettings);
        $this->assertFalse($mp->isSandbox());

        // 1. Falha sem CPF informado
        $resultNoCpf = $mp->processPayment(
            ['order_ref' => 'ORD_PROD_1', 'total' => 50.00, 'student_email' => 'cliente@real.com', 'student_name' => 'Cliente Real'],
            ['method' => 'pix']
        );
        $this->assertFalse($resultNoCpf->success);
        $this->assertSame('failed', $resultNoCpf->status);
        $this->assertStringContainsString('CPF do pagador obrigatório', $resultNoCpf->message);

        // 2. Falha sem e-mail válido
        $resultNoEmail = $mp->processPayment(
            ['order_ref' => 'ORD_PROD_2', 'total' => 50.00, 'student_name' => 'Cliente Real'],
            ['method' => 'pix', 'payer_cpf' => '12345678901']
        );
        $this->assertFalse($resultNoEmail->success);
        $this->assertSame('failed', $resultNoEmail->status);
        $this->assertStringContainsString('E-mail válido do pagador é obrigatório', $resultNoEmail->message);
    }

    public function testCheckoutControllerCheckStatusReturnsPendingAndPaidStatus(): void
    {
        $orderId = $this->orderRepo->createOrder([
            'user_id'        => 55,
            'item_type'      => 'course',
            'item_id'        => 1,
            'total'          => 100.0,
            'status'         => 'pending',
            'gateway'        => 'mercadopago',
            'payment_method' => 'pix',
            'order_ref'      => 'ORD_POLL_123',
        ]);

        $session = $this->createMock(SessionManagerInterface::class);
        $courseRepo = $this->createMock(CourseRepositoryInterface::class);
        $registry = new PaymentGatewayRegistry();
        $controller = new CheckoutController($session, $this->orderRepo, $registry, $courseRepo);

        // 1. Consulta pedido pendente
        $request = new Request([], [], [], [], [], []);
        $respPending = $controller->checkStatus($request, 'ORD_POLL_123');
        $this->assertSame(200, $respPending->getStatusCode());
        $dataPending = json_decode((string)$respPending->getContent(), true);
        $this->assertTrue($dataPending['success']);
        $this->assertFalse($dataPending['paid']);
        $this->assertSame('pending', $dataPending['status']);

        // 2. Atualiza status para pago
        $this->orderRepo->updateStatus($orderId, 'paid', 'TX_PIX_777');
        $respPaid = $controller->checkStatus($request, 'ORD_POLL_123');
        $this->assertSame(200, $respPaid->getStatusCode());
        $dataPaid = json_decode((string)$respPaid->getContent(), true);
        $this->assertTrue($dataPaid['success']);
        $this->assertTrue($dataPaid['paid']);
        $this->assertSame('paid', $dataPaid['status']);
        $this->assertStringContainsString('/aluno/painel', $dataPaid['redirect_url']);
    }
}
