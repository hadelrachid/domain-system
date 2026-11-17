<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Core\Contracts\EventDispatcherInterface;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Plugins\academy\Contracts\CourseRepositoryInterface;
use DomainSystem\Plugins\commerce\Contracts\CommerceSettingsInterface;
use DomainSystem\Plugins\commerce\Contracts\OrderRepositoryInterface;
use DomainSystem\Plugins\commerce\Contracts\PaymentGatewayInterface;
use DomainSystem\Plugins\commerce\Contracts\PaymentGatewayRegistryInterface;
use DomainSystem\Plugins\commerce\Controllers\CheckoutController;
use PHPUnit\Framework\TestCase;

final class CheckoutSecurityTest extends TestCase
{
    public function testUnconfiguredGatewayCannotCreatePaidOrder(): void
    {
        $gateway = $this->createMock(PaymentGatewayInterface::class);
        $gateway->method('getId')->willReturn('stripe');
        $gateway->method('isConfigured')->willReturn(false);
        $gateway->expects($this->never())->method('processPayment');

        $registry = $this->createMock(PaymentGatewayRegistryInterface::class);
        $registry->method('get')->with('stripe')->willReturn($gateway);

        $orderRepo = $this->createMock(OrderRepositoryInterface::class);
        $orderRepo->expects($this->never())->method('createOrder');

        $session = $this->createMock(SessionManagerInterface::class);
        $session->method('get')->willReturnCallback(function ($key) {
            return match ($key) {
                'academy_student_id', 'user_id' => 10,
                default => null,
            };
        });
        $session->method('getCsrfToken')->willReturn('valid_token');

        $courseRepo = $this->createMock(CourseRepositoryInterface::class);
        $courseRepo->method('findById')->with(1)->willReturn([
            'id'     => 1,
            'title'  => 'Curso PHP Avançado',
            'price'  => 199.90,
            'status' => 'published',
        ]);

        $settings = $this->createMock(CommerceSettingsInterface::class);
        $settings->method('get')->with('commerce_demo_mode', false)->willReturn(false);

        $controller = new CheckoutController(
            $session,
            $orderRepo,
            $registry,
            $courseRepo,
            null,
            null,
            $settings
        );

        $request = new Request([], [
            'csrf_token'     => 'valid_token',
            'course_id'      => 1,
            'gateway'        => 'stripe',
            'payment_method' => 'credit_card',
        ], ['REQUEST_METHOD' => 'POST']);

        $response = $controller->process($request);

        $this->assertSame(409, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('não está disponível no momento', $data['message']);
    }

    public function testInvalidGatewayReturns422(): void
    {
        $registry = $this->createMock(PaymentGatewayRegistryInterface::class);
        $registry->method('get')->with('invalid_gw')->willReturn(null);

        $orderRepo = $this->createMock(OrderRepositoryInterface::class);
        $orderRepo->expects($this->never())->method('createOrder');

        $session = $this->createMock(SessionManagerInterface::class);
        $session->method('get')->willReturnCallback(fn($k) => match ($k) {
            'academy_student_id' => 5,
            default => null,
        });
        $session->method('getCsrfToken')->willReturn('token');

        $courseRepo = $this->createMock(CourseRepositoryInterface::class);
        $courseRepo->method('findById')->with(1)->willReturn([
            'id'     => 1,
            'title'  => 'Curso Básico',
            'price'  => 50.00,
            'status' => 'published',
        ]);

        $controller = new CheckoutController(
            $session,
            $orderRepo,
            $registry,
            $courseRepo
        );

        $request = new Request([], [
            'csrf_token' => 'token',
            'course_id'  => 1,
            'gateway'    => 'invalid_gw',
        ], ['REQUEST_METHOD' => 'POST']);

        $response = $controller->process($request);
        $this->assertSame(422, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('Meio de pagamento inválido.', $data['message']);
    }

    public function testSellerAwarePurchasableInterfaceAndCustomerIdentityResolution(): void
    {
        $sellerItem = new class implements \DomainSystem\Plugins\commerce\Contracts\PurchasableItemInterface, \DomainSystem\Plugins\commerce\Contracts\SellerAwarePurchasableInterface {
            public function getId(): int { return 99; }
            public function getItemType(): string { return 'dropship_product'; }
            public function getTitle(): string { return 'Smartwatch Gold'; }
            public function getPrice(): float { return 150.0; }
            public function isAvailable(): bool { return true; }
            public function getDescription(): string { return 'Desc'; }
            public function getThumbnailUrl(): ?string { return 'https://img.com/1.jpg'; }
            public function getSellerInfo(): array {
                return ['store_name' => 'Tech Shop BR', 'is_brazil' => true, 'country_code' => 'BR'];
            }
        };

        $resolver = $this->createMock(\DomainSystem\Plugins\commerce\Contracts\PurchasableItemResolverInterface::class);
        $resolver->method('resolve')->willReturn($sellerItem);

        $gateway = $this->createMock(PaymentGatewayInterface::class);
        $gateway->method('getId')->willReturn('mercadopago');
        $gateway->method('isConfigured')->willReturn(true);
        $gateway->method('processPayment')->willReturn(new \DomainSystem\Plugins\commerce\DTO\PaymentResult(
            success: true,
            orderRef: 'ORD_TEST_1',
            status: 'pending',
            paymentMethod: 'pix',
            pixQrCode: 'pix_code_test'
        ));

        $registry = $this->createMock(PaymentGatewayRegistryInterface::class);
        $registry->method('get')->with('mercadopago')->willReturn($gateway);

        $orderRepo = $this->createMock(OrderRepositoryInterface::class);
        $createdPayload = null;
        $orderRepo->method('createOrder')->willReturnCallback(function (array $data) use (&$createdPayload) {
            $createdPayload = $data;
            return 12345;
        });

        $session = $this->createMock(SessionManagerInterface::class);
        $session->method('get')->willReturnCallback(fn($k) => match ($k) {
            'customer_id'   => 888,
            'customer_name' => 'Cliente Dropship',
            'customer_cpf'  => '12345678901',
            default         => null,
        });
        $session->method('getCsrfToken')->willReturn('valid_csrf');

        $controller = new CheckoutController(
            $session,
            $orderRepo,
            $registry,
            null,
            null,
            $resolver
        );

        $request = new Request([], [
            'csrf_token'     => 'valid_csrf',
            'course_id'      => 99,
            'gateway'        => 'mercadopago',
            'payment_method' => 'pix',
            'payer_cpf'      => '12345678901',
            'quantity'       => 2,
        ], ['REQUEST_METHOD' => 'POST']);

        $response = $controller->process($request);
        $this->assertSame(200, $response->getStatusCode());

        $this->assertNotNull($createdPayload);
        $this->assertSame(888, $createdPayload['user_id'], 'user_id deve refletir o customer_id do comprador da loja');
        $this->assertSame('dropship_product', $createdPayload['item_type']);
        $this->assertSame(300.0, $createdPayload['total'], '150.0 * 2 = 300.0');
    }

    public function testStripeSuccessRejectsDivergentAmount(): void
    {
        $orderRepo = $this->createMock(OrderRepositoryInterface::class);
        $orderRepo->method('findByOrderRef')->with('ORD_STRIPE_DIV')->willReturn([
            'id'        => 50,
            'order_ref' => 'ORD_STRIPE_DIV',
            'total'     => 150.00,
            'user_id'   => 12,
            'item_id'   => 99,
            'item_type' => 'dropship_product',
        ]);
        $orderRepo->expects($this->never())->method('updateStatus');

        $stripeGateway = $this->createMock(\DomainSystem\Plugins\commerce\Services\Gateways\StripeGateway::class);
        $stripeGateway->method('getId')->willReturn('stripe');
        $stripeGateway->method('isConfigured')->willReturn(true);
        $stripeGateway->method('verifySession')->with('sess_divergent_123')->willReturn([
            'status'         => 'approved',
            'total'          => 15.00, // Divergente de 150.00
            'transaction_id' => 'tx_stripe_cheap',
        ]);

        $registry = $this->createMock(PaymentGatewayRegistryInterface::class);
        $registry->method('get')->with('stripe')->willReturn($stripeGateway);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->never())->method('dispatch');

        $session = $this->createMock(SessionManagerInterface::class);

        $controller = new CheckoutController(
            $session,
            $orderRepo,
            $registry,
            null,
            $dispatcher
        );

        $request = new Request([], [
            'session_id' => 'sess_divergent_123',
            'order_ref'  => 'ORD_STRIPE_DIV',
        ]);

        $response = $controller->stripeSuccess($request);
        $location = $response->getHeader('Location');
        $this->assertStringContainsString('payment_status=error', (string)$location);
        $this->assertStringContainsString('divergente', (string)$location);
    }

    public function testStripeSuccessApprovesMatchingAmount(): void
    {
        $orderRepo = $this->createMock(OrderRepositoryInterface::class);
        $orderRepo->method('findByOrderRef')->with('ORD_STRIPE_OK')->willReturn([
            'id'        => 51,
            'order_ref' => 'ORD_STRIPE_OK',
            'total'     => 150.00,
            'user_id'   => 12,
            'item_id'   => 99,
            'item_type' => 'dropship_product',
        ]);
        $orderRepo->expects($this->once())->method('updateStatus')->with(51, 'paid', 'tx_stripe_valid');

        $stripeGateway = $this->createMock(\DomainSystem\Plugins\commerce\Services\Gateways\StripeGateway::class);
        $stripeGateway->method('getId')->willReturn('stripe');
        $stripeGateway->method('isConfigured')->willReturn(true);
        $stripeGateway->method('verifySession')->with('sess_ok_123')->willReturn([
            'status'         => 'approved',
            'total'          => 150.00, // Idêntico a 150.00
            'transaction_id' => 'tx_stripe_valid',
        ]);

        $registry = $this->createMock(PaymentGatewayRegistryInterface::class);
        $registry->method('get')->with('stripe')->willReturn($stripeGateway);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->once())->method('dispatch')->with('payment.approved', $this->callback(function ($payload) {
            return ($payload['order_ref'] ?? '') === 'ORD_STRIPE_OK' && ($payload['user_id'] ?? 0) === 12;
        }));

        $session = $this->createMock(SessionManagerInterface::class);

        $controller = new CheckoutController(
            $session,
            $orderRepo,
            $registry,
            null,
            $dispatcher
        );

        $request = new Request([], [
            'session_id' => 'sess_ok_123',
            'order_ref'  => 'ORD_STRIPE_OK',
        ]);

        $response = $controller->stripeSuccess($request);
        $location = $response->getHeader('Location');
        $this->assertStringContainsString('payment_status=approved', (string)$location);
    }

    public function testPayPalSuccessRejectsDivergentAmount(): void
    {
        $orderRepo = $this->createMock(OrderRepositoryInterface::class);
        $orderRepo->method('findByOrderRef')->with('ORD_PP_DIV')->willReturn([
            'id'        => 60,
            'order_ref' => 'ORD_PP_DIV',
            'total'     => 200.00,
            'user_id'   => 15,
            'item_id'   => 10,
            'item_type' => 'course',
        ]);
        $orderRepo->expects($this->never())->method('updateStatus');

        $payPalGateway = $this->createMock(\DomainSystem\Plugins\commerce\Services\Gateways\PayPalGateway::class);
        $payPalGateway->method('getId')->willReturn('paypal');
        $payPalGateway->method('isConfigured')->willReturn(true);
        $payPalGateway->method('captureOrder')->with('token_pp_div')->willReturn([
            'status'         => 'approved',
            'total'          => 20.00, // Divergente de 200.00
            'transaction_id' => 'tx_pp_divergent',
        ]);

        $registry = $this->createMock(PaymentGatewayRegistryInterface::class);
        $registry->method('get')->with('paypal')->willReturn($payPalGateway);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->never())->method('dispatch');

        $session = $this->createMock(SessionManagerInterface::class);

        $controller = new CheckoutController(
            $session,
            $orderRepo,
            $registry,
            null,
            $dispatcher
        );

        $request = new Request([], [
            'token'     => 'token_pp_div',
            'order_ref' => 'ORD_PP_DIV',
        ]);

        $response = $controller->paypalSuccess($request);
        $location = $response->getHeader('Location');
        $this->assertStringContainsString('payment_status=error', (string)$location);
        $this->assertStringContainsString('divergente', (string)$location);
    }
}
