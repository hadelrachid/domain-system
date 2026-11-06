<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Core\Contracts\EventDispatcherInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Plugins\commerce\Contracts\CommerceSettingsInterface;
use DomainSystem\Plugins\commerce\Contracts\PaymentGatewayInterface;
use DomainSystem\Plugins\commerce\Contracts\PaymentGatewayRegistryInterface;
use DomainSystem\Plugins\commerce\Controllers\WebhookController;
use DomainSystem\Plugins\commerce\Repositories\PdoOrderRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class WebhookSecurityAndIdempotencyTest extends TestCase
{
    private PDO $pdo;
    private PdoOrderRepository $orderRepo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->orderRepo = new PdoOrderRepository($this->pdo);
        $this->orderRepo->ensureTable();
    }

    public function testAmountMismatchLeavesOrderPending(): void
    {
        $orderId = $this->orderRepo->createOrder([
            'order_ref'      => 'ORD_MISMATCH_100',
            'user_id'        => 5,
            'item_type'      => 'course',
            'item_id'        => 10,
            'total'          => 100.00,
            'status'         => 'pending',
            'gateway'        => 'mercadopago',
            'payment_method' => 'pix',
        ]);

        $gateway = $this->createMock(PaymentGatewayInterface::class);
        $gateway->method('getId')->willReturn('mercadopago');
        $gateway->method('handleWebhook')->willReturn([
            'order_ref'      => 'ORD_MISMATCH_100',
            'status'         => 'approved',
            'transaction_id' => 'TX_UNDERPAID',
            'total'          => 10.00, // Pagou apenas 10 em vez de 100
            'gateway'        => 'mercadopago',
        ]);

        $registry = $this->createMock(PaymentGatewayRegistryInterface::class);
        $registry->method('get')->with('mercadopago')->willReturn($gateway);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->never())->method('dispatch');

        $controller = new WebhookController($this->orderRepo, $registry, $dispatcher, $this->pdo);

        $request = new Request([], ['order_ref' => 'ORD_MISMATCH_100']);
        $response = $controller->handle($request, 'mercadopago');

        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertSame('amount_mismatch', $body['action'] ?? null);

        // O pedido continua pendente
        $order = $this->orderRepo->findById($orderId);
        $this->assertSame('pending', $order['status']);
    }

    public function testDuplicateWebhookReturnsDuplicateActionAndDoesNotRedispatch(): void
    {
        $orderId = $this->orderRepo->createOrder([
            'order_ref'      => 'ORD_DUP_200',
            'user_id'        => 7,
            'item_type'      => 'course',
            'item_id'        => 12,
            'total'          => 80.00,
            'status'         => 'paid', // Já liquidado anteriormente
            'gateway'        => 'mercadopago',
            'payment_method' => 'pix',
        ]);

        $gateway = $this->createMock(PaymentGatewayInterface::class);
        $gateway->method('getId')->willReturn('mercadopago');
        $gateway->method('handleWebhook')->willReturn([
            'order_ref'      => 'ORD_DUP_200',
            'status'         => 'approved',
            'transaction_id' => 'TX_DUP',
            'total'          => 80.00,
            'gateway'        => 'mercadopago',
        ]);

        $registry = $this->createMock(PaymentGatewayRegistryInterface::class);
        $registry->method('get')->with('mercadopago')->willReturn($gateway);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->never())->method('dispatch');

        $controller = new WebhookController($this->orderRepo, $registry, $dispatcher, $this->pdo);

        $request = new Request([], ['order_ref' => 'ORD_DUP_200']);
        $response = $controller->handle($request, 'mercadopago');

        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertSame('duplicate', $body['action'] ?? null);
    }

    public function testRefundWebhookDispatchesPaymentRefundedAndUpdateOrderStatus(): void
    {
        $orderId = $this->orderRepo->createOrder([
            'order_ref'      => 'ORD_REFUND_300',
            'user_id'        => 9,
            'item_type'      => 'course',
            'item_id'        => 15,
            'total'          => 150.00,
            'status'         => 'paid',
            'gateway'        => 'mercadopago',
            'payment_method' => 'credit_card',
        ]);

        $gateway = $this->createMock(PaymentGatewayInterface::class);
        $gateway->method('getId')->willReturn('mercadopago');
        $gateway->method('handleWebhook')->willReturn([
            'order_ref'      => 'ORD_REFUND_300',
            'status'         => 'refunded',
            'transaction_id' => 'TX_REFUND',
            'gateway'        => 'mercadopago',
        ]);

        $registry = $this->createMock(PaymentGatewayRegistryInterface::class);
        $registry->method('get')->with('mercadopago')->willReturn($gateway);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with('payment.refunded', [
                'user_id'   => 9,
                'course_id' => 15,
                'order_ref' => 'ORD_REFUND_300',
            ]);

        $controller = new WebhookController($this->orderRepo, $registry, $dispatcher, $this->pdo);

        $request = new Request([], ['order_ref' => 'ORD_REFUND_300']);
        $response = $controller->handle($request, 'mercadopago');

        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertSame('refunded', $body['order_status'] ?? null);

        $order = $this->orderRepo->findById($orderId);
        $this->assertSame('refunded', $order['status']);
    }
}
