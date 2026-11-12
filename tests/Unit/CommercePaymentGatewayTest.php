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
        $this->assertTrue($checkoutData['demo_mode'] ?? false);
        $this->assertTrue($checkoutData['simulated'] ?? false);
        $this->assertStringContainsString('DEMONSTRAÇÃO', (string)($checkoutData['warning'] ?? ''));
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

    public function testMercadoPagoDemoPixIsExplicitlyMarkedAsSimulated(): void
    {
        $mp = new MercadoPagoGateway(settings: $this->settings);

        $result = $mp->processPayment(
            ['order_ref' => 'ORD_DEMO_PIX_01', 'total' => 75.00, 'student_name' => 'Teste Demo'],
            ['method' => 'pix']
        );

        $this->assertTrue($result->success);
        $this->assertSame('pending', $result->status);
        $this->assertSame('pix', $result->paymentMethod);
        $this->assertStringStartsWith('MP_DEMO_PIX_', (string)$result->transactionId);
        $this->assertStringContainsString('MODO DEMONSTRAÇÃO (SIMULADO)', $result->message);
        $this->assertStringContainsString('DEMO-PIX-', (string)$result->pixCopiaECola);
        $this->assertTrue((bool)($result->rawPayload['demo_mode'] ?? false));
        $this->assertTrue((bool)($result->rawPayload['simulated'] ?? false));
    }

    public function testMercadoPagoGeneratePixResultWithoutKeyInProductionReturnsFailure(): void
    {
        $prodSettings = $this->createMock(CommerceSettingsInterface::class);
        $prodSettings->method('get')->willReturnMap([
            ['mercadopago_pix_key', null, ''],
            ['pix_key', null, ''],
            ['commerce_demo_mode', false, false],
        ]);

        // Cenário 1: settings de produção explícito (demo_mode = false) e sem chave Pix
        $mpProd = new MercadoPagoGateway(settings: $prodSettings);
        $resProd = $mpProd->generatePixResult('ORD_PROD_NOKEY_1', 120.00, 'Cliente Real');

        $this->assertFalse($resProd->success);
        $this->assertSame('failed', $resProd->status);
        $this->assertNull($resProd->pixCopiaECola);
        $this->assertStringContainsString('Pix indisponível', $resProd->message);

        // Cenário 2: gateway sem settings (ambientes sem flag de demo explícita) também não inventa MD5
        $mpNoSettings = new MercadoPagoGateway('APP_USR-PROD-TOKEN', 'APP_USR-PROD-KEY');
        $resNoSettings = $mpNoSettings->generatePixResult('ORD_PROD_NOKEY_2', 120.00, 'Cliente Real');

        $this->assertFalse($resNoSettings->success);
        $this->assertSame('failed', $resNoSettings->status);
        $this->assertNull($resNoSettings->pixCopiaECola);
    }

    public function testMercadoPagoProductionPixReturnsFailureWhenApiOmitsQrCodeOrThrows(): void
    {
        $prodSettings = $this->createMock(CommerceSettingsInterface::class);
        $prodSettings->method('get')->willReturnMap([
            ['mercadopago_access_token', null, 'APP_USR-1234567890-PROD-TOKEN'],
            ['mercadopago_public_key', null, 'APP_USR-1234567890-PROD-KEY'],
            ['mercadopago_sandbox', false, false],
            ['commerce_demo_mode', false, false],
        ]);

        // 1. API responde sem point_of_interaction.transaction_data.qr_code
        $mpMissingQr = new class('APP_USR-1234567890-PROD-TOKEN', 'APP_USR-1234567890-PROD-KEY', $prodSettings) extends MercadoPagoGateway {
            protected function sendHttpRequest(string $method, string $url, array $data = []): array
            {
                return [
                    'id'      => 998877,
                    'status'  => 'rejected',
                    'message' => 'Collector not enabled for Pix',
                ];
            }
        };

        $resMissingQr = $mpMissingQr->processPayment(
            ['order_ref' => 'ORD_LIVE_FAIL_1', 'total' => 149.90, 'student_email' => 'cliente@real.com', 'student_name' => 'Cliente Real'],
            ['method' => 'pix', 'payer_cpf' => '12345678901']
        );

        $this->assertFalse($resMissingQr->success);
        $this->assertSame('failed', $resMissingQr->status);
        $this->assertNull($resMissingQr->pixCopiaECola);
        $this->assertStringContainsString('Collector not enabled for Pix', $resMissingQr->message);

        // 2. API lança exceção de comunicação
        $mpThrows = new class('APP_USR-1234567890-PROD-TOKEN', 'APP_USR-1234567890-PROD-KEY', $prodSettings) extends MercadoPagoGateway {
            protected function sendHttpRequest(string $method, string $url, array $data = []): array
            {
                throw new \RuntimeException('Falha de conexão com a API do Mercado Pago (HTTP 503).');
            }
        };

        $resThrows = $mpThrows->processPayment(
            ['order_ref' => 'ORD_LIVE_FAIL_2', 'total' => 149.90, 'student_email' => 'cliente@real.com', 'student_name' => 'Cliente Real'],
            ['method' => 'pix', 'payer_cpf' => '12345678901']
        );

        $this->assertFalse($resThrows->success);
        $this->assertSame('failed', $resThrows->status);
        $this->assertNull($resThrows->pixCopiaECola);
        $this->assertStringContainsString('HTTP 503', $resThrows->message);
    }

    public function testMercadoPagoProductionPixSuccessReturnsOfficialQrCode(): void
    {
        $prodSettings = $this->createMock(CommerceSettingsInterface::class);
        $prodSettings->method('get')->willReturnMap([
            ['mercadopago_access_token', null, 'APP_USR-1234567890-PROD-TOKEN'],
            ['mercadopago_public_key', null, 'APP_USR-1234567890-PROD-KEY'],
            ['mercadopago_sandbox', false, false],
            ['commerce_demo_mode', false, false],
        ]);

        $mpLive = new class('APP_USR-1234567890-PROD-TOKEN', 'APP_USR-1234567890-PROD-KEY', $prodSettings) extends MercadoPagoGateway {
            protected function sendHttpRequest(string $method, string $url, array $data = []): array
            {
                return [
                    'id'                   => 5544332211,
                    'status'               => 'pending',
                    'point_of_interaction' => [
                        'transaction_data' => [
                            'qr_code'        => '00020101021226800014BR.GOV.BCB.PIX_OFICIAL_MP',
                            'qr_code_base64' => 'iVBORw0KGgoAAAANSUhEUg==',
                        ],
                    ],
                ];
            }
        };

        $res = $mpLive->processPayment(
            ['order_ref' => 'ORD_LIVE_OK_1', 'total' => 89.90, 'student_email' => 'cliente@real.com', 'student_name' => 'Cliente Real'],
            ['method' => 'pix', 'payer_cpf' => '12345678901']
        );

        $this->assertTrue($res->success);
        $this->assertSame('pending', $res->status);
        $this->assertSame('5544332211', $res->transactionId);
        $this->assertSame('00020101021226800014BR.GOV.BCB.PIX_OFICIAL_MP', $res->pixCopiaECola);
        $this->assertSame('data:image/png;base64,iVBORw0KGgoAAAANSUhEUg==', $res->pixQrCode);
    }

    public function testMercadoPagoOrderWebhookUsesOrderInfoForTotalAndStatus(): void
    {
        $prodSettings = $this->createMock(CommerceSettingsInterface::class);
        $prodSettings->method('get')->willReturnMap([
            ['mercadopago_access_token', null, 'APP_USR-1234567890-PROD-TOKEN'],
            ['mercadopago_public_key', null, 'APP_USR-1234567890-PROD-KEY'],
            ['mercadopago_sandbox', false, false],
            ['commerce_demo_mode', false, false],
        ]);

        $mpOrder = new class('APP_USR-1234567890-PROD-TOKEN', 'APP_USR-1234567890-PROD-KEY', $prodSettings) extends MercadoPagoGateway {
            protected function sendHttpRequest(string $method, string $url, array $data = []): array
            {
                return [
                    'id'                 => 'ORD_MP_888',
                    'external_reference' => 'ORD_LOCAL_777',
                    'status'             => 'paid',
                    'total_amount'       => 197.50,
                ];
            }
        };

        $req = new Request(
            [],
            [],
            ['REQUEST_METHOD' => 'POST'],
            [],
            [],
            ['type' => 'order', 'data' => ['id' => 'ORD_MP_888']]
        );
        $parsed = $mpOrder->handleWebhook($req);

        $this->assertIsArray($parsed);
        $this->assertSame('ORD_LOCAL_777', $parsed['order_ref']);
        $this->assertSame('approved', $parsed['status']);
        $this->assertSame('ORD_MP_888', $parsed['transaction_id']);
        $this->assertEqualsWithDelta(197.50, $parsed['total'], 0.001);
        $this->assertSame('mercadopago', $parsed['gateway']);
    }

    public function testMercadoPagoCreditCardWithoutTokenRedirectsToCheckoutPro(): void
    {
        $prodSettings = $this->createMock(CommerceSettingsInterface::class);
        $prodSettings->method('get')->willReturnMap([
            ['mercadopago_access_token', null, 'APP_USR-PROD-TOKEN'],
            ['mercadopago_public_key', null, 'APP_USR-PROD-KEY'],
            ['mercadopago_sandbox', false, false],
            ['commerce_demo_mode', false, false],
        ]);

        $calledUrls = [];
        $mpCard = new class('APP_USR-PROD-TOKEN', 'APP_USR-PROD-KEY', $prodSettings, $calledUrls) extends MercadoPagoGateway {
            private array $urls;
            public function __construct(?string $t, ?string $p, ?CommerceSettingsInterface $s, array &$urls)
            {
                parent::__construct($t, $p, $s);
                $this->urls = &$urls;
            }
            protected function sendHttpRequest(string $method, string $url, array $data = []): array
            {
                $this->urls[] = $url;
                return [
                    'id'         => 'PREF_CC_123',
                    'init_point' => 'https://www.mercadopago.com.br/checkout/v1/redirect?pref_id=PREF_CC_123',
                ];
            }
        };

        $res = $mpCard->processPayment(
            ['order_ref' => 'ORD_CC_1', 'total' => 149.00, 'student_email' => 'buyer@real.com', 'student_name' => 'Buyer'],
            ['method' => 'credit_card']
        );

        $this->assertTrue($res->success);
        $this->assertSame('pending', $res->status);
        $this->assertStringContainsString('/checkout/preferences', $calledUrls[0] ?? '');
        $this->assertStringContainsString('PREF_CC_123', (string)$res->redirectUrl);
    }

    public function testMercadoPagoDemoModeBlockedWhenAppEnvIsProduction(): void
    {
        $prevEnv = getenv('APP_ENV');
        putenv('APP_ENV=production');
        try {
            $demoSettings = $this->createMock(CommerceSettingsInterface::class);
            $demoSettings->method('get')->willReturnMap([
                ['commerce_demo_mode', false, true],
                ['mercadopago_pix_key', null, ''],
                ['pix_key', null, ''],
            ]);

            $mp = new MercadoPagoGateway(settings: $demoSettings);
            $res = $mp->processPayment(
                ['order_ref' => 'ORD_ENV_PROD', 'total' => 50.00],
                ['method' => 'pix']
            );

            $this->assertFalse($res->success);
            $this->assertSame('failed', $res->status);
        } finally {
            putenv($prevEnv === false ? 'APP_ENV' : ('APP_ENV=' . $prevEnv));
        }
    }
}
