<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Plugins\commerce\Contracts\CommerceSettingsInterface;
use DomainSystem\Plugins\commerce\Contracts\OrderRepositoryInterface;
use DomainSystem\Plugins\commerce\Contracts\PaymentGatewayRegistryInterface;
use DomainSystem\Plugins\commerce\Controllers\OrdersAdminController;
use DomainSystem\Plugins\commerce\Repositories\PdoOrderRepository;
use DomainSystem\Plugins\commerce\Services\Gateways\MercadoPagoGateway;
use DomainSystem\Plugins\commerce\Services\Gateways\PayPalGateway;
use DomainSystem\Plugins\commerce\Services\Gateways\StripeGateway;
use DomainSystem\Plugins\commerce\Services\PaymentGatewayRegistry;
use PDO;
use PHPUnit\Framework\TestCase;

final class OrdersAdminControllerTest extends TestCase
{
    private PDO $pdo;
    private PdoOrderRepository $orderRepo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Cria tabelas de teste
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(120) NOT NULL,
                email VARCHAR(180) NOT NULL,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(32) NOT NULL DEFAULT 'user'
            );
        ");

        $this->orderRepo = new PdoOrderRepository($this->pdo, true);
    }

    public function testDashboardRendersPolishedKpiAndGatewayCards(): void
    {
        // 1. Cadastra usuário e pedido de teste
        $this->pdo->exec("
            INSERT INTO users (id, name, email, password, role)
            VALUES (1, 'Mônica Medeiros', 'monica@teste.com', 'hash', 'user');
        ");

        $orderId = $this->orderRepo->createOrder([
            'user_id'        => 1,
            'item_type'      => 'course',
            'item_id'        => 10,
            'total'          => 150.00,
            'status'         => 'paid',
            'gateway'        => 'mercadopago',
            'payment_method' => 'pix',
            'order_ref'      => 'ORD_TEST_9999',
            'transaction_id' => 'TX_PIX_123',
        ]);

        $this->assertGreaterThan(0, $orderId);

        // 2. Mocks de Sessão e Settings
        $session = $this->createMock(SessionManagerInterface::class);
        $session->method('getCsrfToken')->willReturn('csrf_token_abc');
        $session->method('getFlash')->willReturn(null);

        $settings = $this->createMock(CommerceSettingsInterface::class);
        $settings->method('all')->willReturn([
            'mercadopago_enabled'      => true,
            'mercadopago_access_token' => 'APP_USR-test-token',
            'mercadopago_public_key'   => 'APP_USR-pub-key',
            'mercadopago_sandbox'      => false,
            'paypal_enabled'           => false,
            'stripe_enabled'           => true,
            'stripe_secret_key'        => 'sk_test_123',
        ]);
        $settings->method('get')->willReturnCallback(function ($key, $def = null) {
            $data = [
                'mercadopago_enabled'      => true,
                'mercadopago_access_token' => 'APP_USR-test-token',
                'mercadopago_public_key'   => 'APP_USR-pub-key',
                'mercadopago_sandbox'      => false,
                'paypal_enabled'           => false,
                'stripe_enabled'           => true,
                'stripe_secret_key'        => 'sk_test_123',
            ];
            return $data[$key] ?? $def;
        });

        // 3. Registry de Gateways
        $registry = new PaymentGatewayRegistry();
        $registry->register(new MercadoPagoGateway(settings: $settings));
        $registry->register(new PayPalGateway(settings: $settings));
        $registry->register(new StripeGateway(settings: $settings));

        $controller = new OrdersAdminController($session, $this->orderRepo, $registry, $settings);

        $req = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/commerce']);
        $response = $controller->index($req);

        $this->assertSame(200, $response->getStatusCode());
        $html = $response->getContent();

        // 4. Validações da Nova Interface Polida ("Banho de Loja")
        // Header
        $this->assertStringContainsString('DomainCommerce', $html);
        $this->assertStringContainsString('v2.6.0', $html);

        // KPIs
        $this->assertStringContainsString('Faturamento Aprovado', $html);
        $this->assertStringContainsString('R$ 150,00', $html);
        $this->assertStringContainsString('Pedidos Concluídos', $html);
        $this->assertStringContainsString('Conectores Ativos', $html);

        // Gateways Formatados e Status com No-Wrap
        $this->assertStringContainsString('Mercado Pago', $html);
        $this->assertStringContainsString('Ativo (Produção)', $html);
        $this->assertStringContainsString('⚡ Pix Instantâneo', $html);

        $this->assertStringContainsString('PayPal', $html);
        $this->assertStringContainsString('Desativado', $html);

        $this->assertStringContainsString('Stripe', $html);
        $this->assertStringContainsString('Ativo (Live)', $html);

        // Pedido com Nome do Aluno e Status em Português
        $this->assertStringContainsString('ORD_TEST_9999', $html);
        $this->assertStringContainsString('Mônica Medeiros', $html);
        $this->assertStringContainsString('monica@teste.com', $html);
        $this->assertStringContainsString('Pago / Aprovado', $html);
        $this->assertStringContainsString('Pix Instantâneo', $html);

        // Combobox de Meses presente
        $this->assertStringContainsString('id="dsPeriodSelect"', $html);
        $this->assertStringContainsString('Todo o Histórico (Geral)', $html);

        // Gráficos de Inteligência Financeira
        $this->assertStringContainsString('id="dsSalesChart"', $html);
        $this->assertStringContainsString('id="dsPaymentMixChart"', $html);
        $this->assertStringContainsString('Evolução de Faturamento', $html);
        $this->assertStringContainsString('Mix de Pagamentos', $html);
    }

    public function testMonthlyPeriodFilterUpdatesMetricsAndOrdersAccurately(): void
    {
        // 1. Cadastra usuário
        $this->pdo->exec("
            INSERT INTO users (id, name, email, password, role)
            VALUES (2, 'Aluno Teste', 'aluno@teste.com', 'hash', 'user');
        ");

        // 2. Insere pedido de Outubro/2026
        $this->pdo->exec("
            INSERT INTO commerce_orders (user_id, item_type, item_id, total, status, gateway, payment_method, order_ref, created_at)
            VALUES (2, 'course', 1, 200.00, 'paid', 'mercadopago', 'pix', 'ORD_OCT_1', '2026-10-15 14:00:00');
        ");

        // 3. Insere pedido de Novembro/2026
        $this->pdo->exec("
            INSERT INTO commerce_orders (user_id, item_type, item_id, total, status, gateway, payment_method, order_ref, created_at)
            VALUES (2, 'course', 1, 300.00, 'paid', 'mercadopago', 'pix', 'ORD_NOV_1', '2026-11-05 10:00:00');
        ");

        $session = $this->createMock(SessionManagerInterface::class);
        $session->method('getCsrfToken')->willReturn('token_123');
        $session->method('getFlash')->willReturn(null);

        $settings = $this->createMock(CommerceSettingsInterface::class);
        $settings->method('all')->willReturn(['mercadopago_enabled' => true]);

        $registry = new PaymentGatewayRegistry();
        $controller = new OrdersAdminController($session, $this->orderRepo, $registry, $settings);

        // A. Filtro para Outubro/2026 (?period=2026-10)
        $reqOct = new Request(['period' => '2026-10'], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/commerce?period=2026-10']);
        $respOct = $controller->index($reqOct);
        $htmlOct = $respOct->getContent();

        $this->assertStringContainsString('ORD_OCT_1', $htmlOct);
        $this->assertStringNotContainsString('ORD_NOV_1', $htmlOct);
        $this->assertStringContainsString('R$ 200,00', $htmlOct);
        $this->assertStringContainsString('value="2026-10" selected', $htmlOct);

        // B. Filtro para Novembro/2026 (?period=2026-11)
        $reqNov = new Request(['period' => '2026-11'], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/commerce?period=2026-11']);
        $respNov = $controller->index($reqNov);
        $htmlNov = $respNov->getContent();

        $this->assertStringContainsString('ORD_NOV_1', $htmlNov);
        $this->assertStringNotContainsString('ORD_OCT_1', $htmlNov);
        $this->assertStringContainsString('R$ 300,00', $htmlNov);
        $this->assertStringContainsString('value="2026-11" selected', $htmlNov);

        // C. Sem filtro (Geral / Todo o Histórico)
        $reqAll = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/commerce']);
        $respAll = $controller->index($reqAll);
        $htmlAll = $respAll->getContent();

        $this->assertStringContainsString('ORD_OCT_1', $htmlAll);
        $this->assertStringContainsString('ORD_NOV_1', $htmlAll);
        $this->assertStringContainsString('R$ 500,00', $htmlAll); // 200 + 300
    }

    public function testExportGeneratesValidExcelCompatibleCsv(): void
    {
        $this->pdo->exec("
            INSERT INTO users (id, name, email, password, role)
            VALUES (3, 'Carlos Contador', 'carlos@contabil.com', 'hash', 'user');
        ");

        $this->pdo->exec("
            INSERT INTO commerce_orders (user_id, item_type, item_id, total, status, gateway, payment_method, order_ref, created_at)
            VALUES (3, 'course', 5, 450.50, 'paid', 'mercadopago', 'pix', 'ORD_FISCAL_2026', '2026-11-05 18:00:00');
        ");

        $session = $this->createMock(SessionManagerInterface::class);
        $settings = $this->createMock(CommerceSettingsInterface::class);
        $registry = new PaymentGatewayRegistry();
        $controller = new OrdersAdminController($session, $this->orderRepo, $registry, $settings);

        // 1. Export Geral
        $reqExport = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/commerce/export']);
        $respExport = $controller->export($reqExport);

        $this->assertSame(200, $respExport->getStatusCode());
        $this->assertStringContainsString('text/csv', (string)$respExport->getHeader('Content-Type'));
        $this->assertStringContainsString('attachment; filename="balanco-geral-vendas-', (string)$respExport->getHeader('Content-Disposition'));

        $csv = $respExport->getContent();
        // UTF-8 BOM
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        // Cabeçalhos em português com delimitador ;
        $this->assertStringContainsString('"ID Pedido"', $csv);
        $this->assertStringContainsString('Referência', $csv);
        $this->assertStringContainsString('Nome do Aluno', $csv);
        $this->assertStringContainsString('Carlos Contador', $csv);
        $this->assertStringContainsString('carlos@contabil.com', $csv);
        $this->assertStringContainsString('450,50', $csv);
        $this->assertStringContainsString('Pix Instantâneo', $csv);
        $this->assertStringContainsString('Pago / Aprovado', $csv);

        // 2. Export Anual (?year=2026)
        $reqYear = new Request(['year' => '2026'], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/commerce/export?year=2026']);
        $respYear = $controller->export($reqYear);
        $this->assertStringContainsString('filename="balanco-anual-vendas-2026.csv"', (string)$respYear->getHeader('Content-Disposition'));

        // 3. Export Mensal (?period=2026-11)
        $reqMonth = new Request(['period' => '2026-11'], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/commerce/export?period=2026-11']);
        $respMonth = $controller->export($reqMonth);
        $this->assertStringContainsString('filename="balanco-vendas-2026-11.csv"', (string)$respMonth->getHeader('Content-Disposition'));
    }
}
