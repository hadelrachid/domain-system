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

    public function testAffiliateWebhookReturns422OnDefinitiveInvalidEmailWithoutRetryLoop(): void
    {
        $this->createAffiliateSupportTables();

        $gateway = $this->createMock(PaymentGatewayInterface::class);
        $gateway->method('getId')->willReturn('hotmart');
        $gateway->method('handleWebhook')->willReturn([
            'order_ref'      => 'HOT_BAD_EMAIL_01',
            'status'         => 'approved',
            'transaction_id' => 'HP_BAD_01',
            'product_id'     => 'PROD_CURSO_01',
            'student_email'  => 'email-malformado-sem-arroba',
            'student_name'   => 'Aluno Inválido',
            'total'          => 297.00,
            'gateway'        => 'hotmart',
        ]);

        $registry = $this->createMock(PaymentGatewayRegistryInterface::class);
        $registry->method('get')->with('hotmart')->willReturn($gateway);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->never())->method('dispatch');

        $controller = new WebhookController($this->orderRepo, $registry, $dispatcher, $this->pdo);
        $response = $controller->handle(new Request([], ['transaction' => 'HP_BAD_01']), 'hotmart');

        $this->assertSame(422, $response->getStatusCode());
        $body = json_decode((string)$response->getContent(), true);
        $this->assertFalse($body['received'] ?? true);
        $this->assertSame('invalid_student_email', $body['error'] ?? null);
        $this->assertNull($this->orderRepo->findByOrderRef('HOT_BAD_EMAIL_01'));
    }

    public function testAffiliateWebhookReturns500WhenUserProvisioningFailsTemporarilyAndIsIdempotentOnRetry(): void
    {
        $this->createAffiliateSupportTables();

        // Simula falha temporária de escrita na tabela users durante a 1ª entrega do webhook
        $this->pdo->exec("
            CREATE TRIGGER trg_fail_user_insert
            BEFORE INSERT ON users
            BEGIN
                SELECT RAISE(ABORT, 'Temporary DB lock during provisioning');
            END;
        ");

        $gateway = $this->createMock(PaymentGatewayInterface::class);
        $gateway->method('getId')->willReturn('hotmart');
        $gateway->method('handleWebhook')->willReturn([
            'order_ref'      => 'HOT_RETRY_001',
            'status'         => 'approved',
            'transaction_id' => 'HP_RETRY_001',
            'product_id'     => 'PROD_CURSO_01',
            'student_email'  => 'retry.aluno@exemplo.com',
            'student_name'   => 'Aluno Retry',
            'total'          => 197.00,
            'gateway'        => 'hotmart',
        ]);

        $registry = $this->createMock(PaymentGatewayRegistryInterface::class);
        $registry->method('get')->with('hotmart')->willReturn($gateway);

        $dispatchCount = 0;
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(function () use (&$dispatchCount) {
            $dispatchCount++;
            return null;
        });

        $controller = new WebhookController($this->orderRepo, $registry, $dispatcher, $this->pdo);

        // 1ª tentativa (falha temporária -> HTTP 500 para o gateway reenviar, zero pedido/matrícula)
        $resFail = $controller->handle(new Request([], ['transaction' => 'HP_RETRY_001']), 'hotmart');
        $this->assertSame(500, $resFail->getStatusCode());
        $bodyFail = json_decode((string)$resFail->getContent(), true);
        $this->assertFalse($bodyFail['received'] ?? true);
        $this->assertSame('user_provisioning_failed', $bodyFail['error'] ?? null);
        $this->assertSame(0, $dispatchCount);
        $this->assertNull($this->orderRepo->findByOrderRef('HOT_RETRY_001'));

        // Falha temporária sanada -> gateway executa retry
        $this->pdo->exec("DROP TRIGGER trg_fail_user_insert");

        $resRetryOk = $controller->handle(new Request([], ['transaction' => 'HP_RETRY_001']), 'hotmart');
        $this->assertSame(200, $resRetryOk->getStatusCode());
        $bodyOk = json_decode((string)$resRetryOk->getContent(), true);
        $this->assertTrue($bodyOk['provisioned'] ?? false);
        $this->assertSame('paid', $bodyOk['order_status'] ?? null);
        $this->assertSame(1, $dispatchCount);

        // Retry duplicado subsequente -> idempotente, sem duplicar usuário, pedido nem matrícula
        $resDup = $controller->handle(new Request([], ['transaction' => 'HP_RETRY_001']), 'hotmart');
        $this->assertSame(200, $resDup->getStatusCode());
        $bodyDup = json_decode((string)$resDup->getContent(), true);
        $this->assertSame('duplicate', $bodyDup['action'] ?? null);
        $this->assertSame(1, $dispatchCount);

        $userCount = (int)$this->pdo->query("SELECT COUNT(*) FROM users WHERE email = 'retry.aluno@exemplo.com'")->fetchColumn();
        $this->assertSame(1, $userCount);
    }

    public function testAffiliateWebhookHandlesConcurrentOrderInsertRaceIdempotently(): void
    {
        $this->createAffiliateSupportTables();

        $gateway = $this->createMock(PaymentGatewayInterface::class);
        $gateway->method('getId')->willReturn('hotmart');
        $gateway->method('handleWebhook')->willReturn([
            'order_ref'      => 'HOT_RACE_001',
            'status'         => 'approved',
            'transaction_id' => 'HP_RACE_001',
            'product_id'     => 'PROD_CURSO_01',
            'student_email'  => 'race@exemplo.com',
            'student_name'   => 'Comprador Concorrente',
            'total'          => 97.00,
            'gateway'        => 'hotmart',
        ]);

        $registry = $this->createMock(PaymentGatewayRegistryInterface::class);
        $registry->method('get')->with('hotmart')->willReturn($gateway);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->never())->method('dispatch');

        // Simula corrida: findByOrderRef retorna null antes de createOrder(), outra thread insere na corrida,
        // createOrder() falha por UNIQUE e findByOrderRef seguinte enxerga o pedido 'paid'.
        $orderRepoMock = $this->createMock(\DomainSystem\Plugins\commerce\Contracts\OrderRepositoryInterface::class);
        $orderRepoMock->method('findByOrderRef')
            ->willReturnOnConsecutiveCalls(
                null,
                null,
                ['id' => 99, 'order_ref' => 'HOT_RACE_001', 'status' => 'paid']
            );
        $orderRepoMock->method('createOrder')
            ->willThrowException(new \PDOException('SQLSTATE[23000]: Integrity constraint violation: UNIQUE constraint failed'));

        $controller = new WebhookController($orderRepoMock, $registry, $dispatcher, $this->pdo);
        $res = $controller->handle(new Request([], ['transaction' => 'HP_RACE_001']), 'hotmart');

        $this->assertSame(200, $res->getStatusCode());
        $body = json_decode((string)$res->getContent(), true);
        $this->assertTrue($body['received'] ?? false);
        $this->assertSame('duplicate', $body['action'] ?? null);
    }

    public function testAffiliateWebhookReturns500WhenDatabaseIsUnavailable(): void
    {
        $gateway = $this->createMock(PaymentGatewayInterface::class);
        $gateway->method('getId')->willReturn('hotmart');
        $gateway->method('handleWebhook')->willReturn([
            'order_ref'      => 'HOT_NODB_001',
            'status'         => 'approved',
            'transaction_id' => 'HP_NODB_001',
            'product_id'     => 'PROD_CURSO_01',
            'student_email'  => 'nodb@exemplo.com',
            'student_name'   => 'Sem Banco',
            'total'          => 97.00,
            'gateway'        => 'hotmart',
        ]);

        $registry = $this->createMock(PaymentGatewayRegistryInterface::class);
        $registry->method('get')->with('hotmart')->willReturn($gateway);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->never())->method('dispatch');

        $controller = new WebhookController($this->orderRepo, $registry, $dispatcher, null);
        $res = $controller->handle(new Request([], ['transaction' => 'HOT_NODB_001']), 'hotmart');

        $this->assertSame(500, $res->getStatusCode());
        $body = json_decode((string)$res->getContent(), true);
        $this->assertFalse($body['received'] ?? true);
        $this->assertSame('database_unavailable', $body['error'] ?? null);
    }

    public function testHomonymAffiliateBuyersDoNotCollideOnUniqueUsername(): void
    {
        $this->createAffiliateSupportTables();

        $gateway = $this->createMock(PaymentGatewayInterface::class);
        $gateway->method('getId')->willReturn('eduzz');
        $gateway->method('handleWebhook')->willReturnOnConsecutiveCalls(
            [
                'order_ref'      => 'EDUZZ_HOM_1',
                'status'         => 'approved',
                'transaction_id' => 'ED_1',
                'product_id'     => 'PROD_CURSO_01',
                'student_email'  => 'maria1@exemplo.com',
                'student_name'   => 'Maria Silva',
                'total'          => 100.00,
                'gateway'        => 'eduzz',
            ],
            [
                'order_ref'      => 'EDUZZ_HOM_2',
                'status'         => 'approved',
                'transaction_id' => 'ED_2',
                'product_id'     => 'PROD_CURSO_01',
                'student_email'  => 'maria2@exemplo.com',
                'student_name'   => 'Maria Silva',
                'total'          => 100.00,
                'gateway'        => 'eduzz',
            ]
        );

        $registry = $this->createMock(PaymentGatewayRegistryInterface::class);
        $registry->method('get')->with('eduzz')->willReturn($gateway);

        $controller = new WebhookController($this->orderRepo, $registry, null, $this->pdo);

        $r1 = $controller->handle(new Request([], []), 'eduzz');
        $r2 = $controller->handle(new Request([], []), 'eduzz');

        $this->assertSame(200, $r1->getStatusCode());
        $this->assertSame(200, $r2->getStatusCode());

        $b1 = json_decode((string)$r1->getContent(), true);
        $b2 = json_decode((string)$r2->getContent(), true);

        $this->assertTrue($b1['provisioned'] ?? false);
        $this->assertTrue($b2['provisioned'] ?? false);
        $this->assertNotSame($b1['user_id'], $b2['user_id']);
    }

    public function testPendingOrderRemainsPendingWhenFulfillmentDispatchThrowsAndSucceedsOnRetry(): void
    {
        $orderId = $this->orderRepo->createOrder([
            'user_id'        => 10,
            'item_type'      => 'course',
            'item_id'        => 4,
            'total'          => 150.00,
            'status'         => 'pending',
            'gateway'        => 'mercadopago',
            'payment_method' => 'pix',
            'order_ref'      => 'ORD_DISPATCH_RETRY_1',
        ]);

        $gateway = $this->createMock(PaymentGatewayInterface::class);
        $gateway->method('getId')->willReturn('mercadopago');
        $gateway->method('handleWebhook')->willReturn([
            'order_ref'      => 'ORD_DISPATCH_RETRY_1',
            'status'         => 'approved',
            'transaction_id' => 'MP_RETRY_TX_1',
            'total'          => 150.00,
            'gateway'        => 'mercadopago',
        ]);

        $registry = $this->createMock(PaymentGatewayRegistryInterface::class);
        $registry->method('get')->with('mercadopago')->willReturn($gateway);

        $attempts = 0;
        $dispatched = 0;
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(function (string $event) use (&$attempts, &$dispatched) {
            if ($event === 'payment.approved') {
                $attempts++;
                if ($attempts === 1) {
                    throw new \RuntimeException('Academy circuit breaker open');
                }
                $dispatched++;
            }
            return null;
        });

        $controller = new WebhookController($this->orderRepo, $registry, $dispatcher, $this->pdo);

        // 1ª tentativa falha no dispatch -> retorna 500 e pedido permanece pending para permitir retry
        $res1 = $controller->handle(new Request([], ['id' => 'MP_RETRY_TX_1']), 'mercadopago');
        $this->assertSame(500, $res1->getStatusCode());
        $body1 = json_decode((string)$res1->getContent(), true);
        $this->assertSame('fulfillment_dispatch_failed', $body1['error'] ?? null);
        $orderAfterFail = $this->orderRepo->findById($orderId);
        $this->assertSame('pending', $orderAfterFail['status'] ?? null);
        $this->assertSame(0, $dispatched);

        // 2ª tentativa (retry) conclui dispatch e promove pedido a paid
        $res2 = $controller->handle(new Request([], ['id' => 'MP_RETRY_TX_1']), 'mercadopago');
        $this->assertSame(200, $res2->getStatusCode());
        $orderAfterOk = $this->orderRepo->findById($orderId);
        $this->assertSame('paid', $orderAfterOk['status'] ?? null);
        $this->assertSame(1, $dispatched);

        // 3ª tentativa é idempotente sem reenviar matrícula
        $res3 = $controller->handle(new Request([], ['id' => 'MP_RETRY_TX_1']), 'mercadopago');
        $this->assertSame(200, $res3->getStatusCode());
        $body3 = json_decode((string)$res3->getContent(), true);
        $this->assertSame('duplicate', $body3['action'] ?? null);
        $this->assertSame(1, $dispatched);
    }

    public function testAffiliateWebhookLeavesOrderPendingWhenDispatchThrowsAndSucceedsOnRetry(): void
    {
        $this->createAffiliateSupportTables();

        $gateway = $this->createMock(PaymentGatewayInterface::class);
        $gateway->method('getId')->willReturn('hotmart');
        $gateway->method('handleWebhook')->willReturn([
            'order_ref'      => 'HOT_DISPATCH_FAIL_1',
            'status'         => 'approved',
            'transaction_id' => 'HP_DF_1',
            'product_id'     => 'PROD_CURSO_01',
            'student_email'  => 'dispatch.retry@exemplo.com',
            'student_name'   => 'Aluno Dispatch Retry',
            'total'          => 197.00,
            'gateway'        => 'hotmart',
        ]);

        $registry = $this->createMock(PaymentGatewayRegistryInterface::class);
        $registry->method('get')->with('hotmart')->willReturn($gateway);

        $attempts = 0;
        $dispatched = 0;
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(function (string $event) use (&$attempts, &$dispatched) {
            if ($event === 'payment.approved') {
                $attempts++;
                if ($attempts === 1) {
                    throw new \RuntimeException('Downstream unavailable');
                }
                $dispatched++;
            }
            return null;
        });

        $controller = new WebhookController($this->orderRepo, $registry, $dispatcher, $this->pdo);

        $res1 = $controller->handle(new Request([], ['transaction' => 'HP_DF_1']), 'hotmart');
        $this->assertSame(500, $res1->getStatusCode());
        $body1 = json_decode((string)$res1->getContent(), true);
        $this->assertSame('fulfillment_dispatch_failed', $body1['error'] ?? null);
        $orderPending = $this->orderRepo->findByOrderRef('HOT_DISPATCH_FAIL_1');
        $this->assertNotNull($orderPending);
        $this->assertSame('pending', $orderPending['status'] ?? null);
        $this->assertSame(0, $dispatched);

        $res2 = $controller->handle(new Request([], ['transaction' => 'HP_DF_1']), 'hotmart');
        $this->assertSame(200, $res2->getStatusCode());
        $orderPaid = $this->orderRepo->findByOrderRef('HOT_DISPATCH_FAIL_1');
        $this->assertSame('paid', $orderPaid['status'] ?? null);
        $this->assertSame(1, $dispatched);
    }

    private function createAffiliateSupportTables(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                username TEXT NOT NULL UNIQUE,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                role TEXT NOT NULL DEFAULT 'user',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS academy_courses (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                external_product_id TEXT NULL,
                external_platform TEXT NULL
            )
        ");

        $this->pdo->exec("
            INSERT OR IGNORE INTO academy_courses (id, title, slug, external_product_id, external_platform)
            VALUES (1, 'Curso Teste Webhook', 'curso-teste-webhook', 'PROD_CURSO_01', 'hotmart')
        ");
        $this->pdo->exec("
            INSERT OR IGNORE INTO academy_courses (id, title, slug, external_product_id, external_platform)
            VALUES (2, 'Curso Teste Eduzz', 'curso-teste-eduzz', 'PROD_CURSO_01', 'eduzz')
        ");
    }
}
