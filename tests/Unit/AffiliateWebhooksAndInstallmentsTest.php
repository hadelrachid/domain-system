<?php

declare(strict_types=1);

namespace Tests\Unit;

use DomainSystem\Core\Contracts\EventDispatcherInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Plugins\academy\Repositories\PdoCourseRepository;
use DomainSystem\Plugins\academy\Services\CourseCatalogRenderer;
use DomainSystem\Plugins\commerce\Contracts\CommerceSettingsInterface;
use DomainSystem\Plugins\commerce\Controllers\WebhookController;
use DomainSystem\Plugins\commerce\Repositories\PdoOrderRepository;
use DomainSystem\Plugins\commerce\Services\Gateways\ClickBankGateway;
use DomainSystem\Plugins\commerce\Services\Gateways\EduzzGateway;
use DomainSystem\Plugins\commerce\Services\Gateways\HotmartGateway;
use DomainSystem\Plugins\commerce\Services\Gateways\MonetizzeGateway;
use DomainSystem\Plugins\commerce\Services\PaymentGatewayRegistry;
use PDO;
use PHPUnit\Framework\TestCase;

class AffiliateWebhooksAndInstallmentsTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Cria tabela de usuários para os testes de provisionamento
        $this->pdo->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                role TEXT NOT NULL DEFAULT 'user',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Cria tabela de cursos
        $this->pdo->exec("
            CREATE TABLE academy_courses (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                thumbnail TEXT NULL,
                description TEXT NULL,
                status TEXT NOT NULL DEFAULT 'draft',
                link TEXT NULL,
                price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                is_free INTEGER NOT NULL DEFAULT 1,
                max_installments INTEGER NOT NULL DEFAULT 12,
                external_platform TEXT NULL,
                external_product_id TEXT NULL,
                external_checkout_url TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Cria tabela de pedidos do commerce
        $orderRepo = new PdoOrderRepository($this->pdo);
        $orderRepo->ensureTable();
    }

    public function testCourseRepositoryPersistsInstallmentsAndAffiliatePlatform(): void
    {
        $repo = new PdoCourseRepository($this->pdo, false);

        $courseId = $repo->create([
            'title'                 => 'Formação Especialista PHP',
            'slug'                  => 'formacao-especialista-php',
            'description'           => 'Curso avançado',
            'status'                => 'published',
            'price'                 => 297.00,
            'is_free'               => 0,
            'max_installments'      => 12,
            'external_platform'     => 'hotmart',
            'external_product_id'   => 'HOT-998877',
            'external_checkout_url' => 'https://pay.hotmart.com/HOT998877',
        ]);

        $this->assertGreaterThan(0, $courseId);

        $course = $repo->findById($courseId);
        $this->assertNotNull($course);
        $this->assertSame('Formação Especialista PHP', $course['title']);
        $this->assertEquals(297.00, (float)$course['price']);
        $this->assertSame(0, (int)$course['is_free']);
        $this->assertSame(12, (int)$course['max_installments']);
        $this->assertSame('hotmart', $course['external_platform']);
        $this->assertSame('HOT-998877', $course['external_product_id']);
        $this->assertSame('https://pay.hotmart.com/HOT998877', $course['external_checkout_url']);

        // Busca por external_product_id
        $foundByProd = $repo->findByExternalProductId('HOT-998877');
        $this->assertNotNull($foundByProd);
        $this->assertSame($courseId, (int)$foundByProd['id']);

        // Atualização
        $updated = $repo->update($courseId, [
            'title'                 => 'Formação Especialista PHP v2',
            'slug'                  => 'formacao-especialista-php-v2',
            'description'           => 'Atualizado',
            'status'                => 'published',
            'price'                 => 397.00,
            'is_free'               => 0,
            'max_installments'      => 10,
            'external_platform'     => 'eduzz',
            'external_product_id'   => 'EDZ-5544',
            'external_checkout_url' => 'https://sun.eduzz.com/5544',
        ]);
        $this->assertTrue($updated);

        $fresh = $repo->findById($courseId);
        $this->assertSame(10, (int)$fresh['max_installments']);
        $this->assertEquals(397.00, (float)$fresh['price']);
        $this->assertSame('eduzz', $fresh['external_platform']);
    }

    public function testCourseCatalogRendererFormatsInstallmentsAndNormalizesSubdirectoryUrls(): void
    {
        $repo = new PdoCourseRepository($this->pdo, false);

        // Curso 1: Pago com 12x
        $repo->create([
            'title'            => 'Curso de Python Pro',
            'slug'             => 'curso-de-python-pro',
            'description'      => 'Aprenda Python do zero',
            'status'           => 'published',
            'price'            => 197.00,
            'is_free'          => 0,
            'max_installments' => 12,
            'link'             => null, // link padrão deve usar BASE_URL sem 404
        ]);

        // Curso 2: Gratuito
        $repo->create([
            'title'            => 'Tutorial Git Básico',
            'slug'             => 'tutorial-git-basico',
            'description'      => 'Comandos essenciais',
            'status'           => 'published',
            'price'            => 0.00,
            'is_free'          => 1,
            'max_installments' => 1,
            'link'             => '/tutoriais/git', // link relativo
        ]);

        $renderer = new CourseCatalogRenderer($repo);
        $html = $renderer->render(['limit' => 10]);

        // Deve conter a exibição das parcelas: 197 / 12 = 16.42
        $this->assertStringContainsString('R$ 197,00', $html);
        $this->assertStringContainsString('12x de R$ 16,42', $html);

        // Deve conter o selo GRATUITO para o curso 2
        $this->assertStringContainsString('GRATUITO', $html);

        // Se BASE_URL estiver definido, deve estar prefixado no link
        if (defined('BASE_URL') && BASE_URL !== '') {
            $this->assertStringContainsString(rtrim(BASE_URL, '/') . '/cursos/curso-de-python-pro', $html);
        } else {
            $this->assertStringContainsString('/cursos/curso-de-python-pro', $html);
        }
    }

    public function testHotmartGatewayWebhookParsingAndVerification(): void
    {
        $settingsMock = $this->createMock(CommerceSettingsInterface::class);
        $settingsMock->method('get')->with('hotmart_hottok')->willReturn('SECRET_HOTTOK_123');

        $gateway = new HotmartGateway(settings: $settingsMock);
        $this->assertSame('hotmart', $gateway->getId());
        $this->assertTrue($gateway->isConfigured());

        // Teste de rejeição por token inválido
        $invalidReq = new Request([], $invalidPayload = ['hottok' => 'WRONG_TOKEN'], [], [], [], $invalidPayload);
        $this->assertNull($gateway->handleWebhook($invalidReq));

        // Teste de sucesso com payload Hotmart v2 (PURCHASE_APPROVED)
        $validPayload = [
            'event'  => 'PURCHASE_APPROVED',
            'hottok' => 'SECRET_HOTTOK_123',
            'data'   => [
                'buyer'    => [
                    'email' => 'aluno@hotmart.com',
                    'name'  => 'Carlos Hotmart',
                ],
                'product'  => [
                    'id' => 'HOT_PROD_8899',
                ],
                'purchase' => [
                    'transaction' => 'HP1234567890',
                    'price'       => ['value' => 197.00],
                ],
            ],
        ];

        $validReq = new Request([], $validPayload, [], [], [], $validPayload);
        $result = $gateway->handleWebhook($validReq);

        $this->assertNotNull($result);
        $this->assertSame('HOTMART_HP1234567890', $result['order_ref']);
        $this->assertSame('approved', $result['status']);
        $this->assertSame('HP1234567890', $result['transaction_id']);
        $this->assertSame('aluno@hotmart.com', $result['student_email']);
        $this->assertSame('Carlos Hotmart', $result['student_name']);
        $this->assertSame('HOT_PROD_8899', $result['product_id']);
        $this->assertEquals(197.00, $result['total']);
    }

    public function testEduzzGatewayWebhookParsingAndVerification(): void
    {
        $settingsMock = $this->createMock(CommerceSettingsInterface::class);
        $settingsMock->method('get')->with('eduzz_token')->willReturn('EDUZZ_API_KEY_999');

        $gateway = new EduzzGateway(settings: $settingsMock);
        $this->assertSame('eduzz', $gateway->getId());
        $this->assertTrue($gateway->isConfigured());

        $payload = [
            'api_key'     => 'EDUZZ_API_KEY_999',
            'trans_status'=> 3, // Paga
            'trans_id'    => 'EDZ_TX_5566',
            'cus_email'   => 'maria@eduzz.com',
            'cus_name'    => 'Maria Silva',
            'pro_id'      => 'PROD_EDZ_10',
            'val_liquid'  => 297.00,
        ];

        $req = new Request([], $payload, [], [], [], $payload);
        $result = $gateway->handleWebhook($req);

        $this->assertNotNull($result);
        $this->assertSame('EDUZZ_EDZ_TX_5566', $result['order_ref']);
        $this->assertSame('approved', $result['status']);
        $this->assertSame('EDZ_TX_5566', $result['transaction_id']);
        $this->assertSame('maria@eduzz.com', $result['student_email']);
        $this->assertSame('Maria Silva', $result['student_name']);
        $this->assertSame('PROD_EDZ_10', $result['product_id']);
        $this->assertEquals(297.00, $result['total']);
    }

    public function testMonetizzeGatewayWebhookParsingAndVerification(): void
    {
        $settingsMock = $this->createMock(CommerceSettingsInterface::class);
        $settingsMock->method('get')->with('monetizze_chave_unica')->willReturn('MONETIZZE_KEY_XYZ');

        $gateway = new MonetizzeGateway(settings: $settingsMock);
        $this->assertSame('monetizze', $gateway->getId());
        $this->assertTrue($gateway->isConfigured());

        $payload = [
            'chave_unica' => 'MONETIZZE_KEY_XYZ',
            'tipoEvento'  => 'Finalizada',
            'comprador'   => [
                'email' => 'roberto@monetizze.com',
                'nome'  => 'Roberto Souza',
            ],
            'produto'     => [
                'codigo' => 'PROD_MNZ_77',
            ],
            'transacao'   => [
                'codigo' => 'TX_MNZ_999',
                'valor'  => 150.00,
            ],
        ];

        $req = new Request([], $payload, [], [], [], $payload);
        $result = $gateway->handleWebhook($req);

        $this->assertNotNull($result);
        $this->assertSame('MONETIZZE_TX_MNZ_999', $result['order_ref']);
        $this->assertSame('approved', $result['status']);
        $this->assertSame('roberto@monetizze.com', $result['student_email']);
        $this->assertSame('PROD_MNZ_77', $result['product_id']);
        $this->assertEquals(150.00, $result['total']);
    }

    public function testClickBankGatewayWebhookParsingAndVerification(): void
    {
        $settingsMock = $this->createMock(CommerceSettingsInterface::class);
        $settingsMock->method('get')->with('clickbank_secret')->willReturn('CB_SECRET_KEY');

        $gateway = new ClickBankGateway(settings: $settingsMock);
        $this->assertSame('clickbank', $gateway->getId());
        $this->assertTrue($gateway->isConfigured());

        $payload = [
            'secret'           => 'CB_SECRET_KEY',
            'transactionType'  => 'SALE',
            'receipt'          => 'CBK_RC_12345',
            'customer'         => [
                'billing' => [
                    'email'    => 'john@clickbank.com',
                    'fullName' => 'John Doe',
                ],
            ],
            'lineItems'        => [
                [
                    'itemNo' => 'CB_ITEM_1',
                ],
            ],
            'totalOrderAmount' => 97.00,
        ];

        $req = new Request([], $payload, [], [], [], $payload);
        $result = $gateway->handleWebhook($req);

        $this->assertNotNull($result);
        $this->assertSame('CLICKBANK_CBK_RC_12345', $result['order_ref']);
        $this->assertSame('approved', $result['status']);
        $this->assertSame('john@clickbank.com', $result['student_email']);
        $this->assertSame('John Doe', $result['student_name']);
        $this->assertSame('CB_ITEM_1', $result['product_id']);
        $this->assertEquals(97.00, $result['total']);
    }

    public function testWebhookControllerAutoProvisionsStudentAndFulfillsCourse(): void
    {
        // 1. Cadastra curso vinculado à Hotmart
        $courseRepo = new PdoCourseRepository($this->pdo, false);
        $courseId = $courseRepo->create([
            'title'               => 'Curso de Inteligência Artificial',
            'slug'                => 'curso-de-ia',
            'description'         => 'Do básico ao avançado',
            'status'              => 'published',
            'price'               => 497.00,
            'is_free'             => 0,
            'max_installments'    => 12,
            'external_platform'   => 'hotmart',
            'external_product_id' => 'HOT-IA-100',
        ]);

        $orderRepo = new PdoOrderRepository($this->pdo);

        $gatewayRegistry = new PaymentGatewayRegistry();
        $settingsMock = $this->createMock(CommerceSettingsInterface::class);
        $settingsMock->method('get')->with('hotmart_hottok')->willReturn('SECRET_TEST_HOTTOK');
        $gatewayRegistry->register(new HotmartGateway(settings: $settingsMock));

        // Event Dispatcher fake para capturar o evento disparado
        $capturedEvents = [];
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(function ($eventName, $payload) use (&$capturedEvents) {
            $capturedEvents[$eventName] = $payload;
            return null;
        });

        $controller = new WebhookController(
            $orderRepo,
            $gatewayRegistry,
            $dispatcher,
            $this->pdo
        );

        // Simula webhook da Hotmart de aluno que NÃO existe previamente no banco
        $payload = [
            'event'  => 'PURCHASE_APPROVED',
            'hottok' => 'SECRET_TEST_HOTTOK',
            'data'   => [
                'buyer'    => [
                    'email' => 'novato@alunohotmart.com',
                    'name'  => 'Novo Aluno Hotmart',
                ],
                'product'  => [
                    'id' => 'HOT-IA-100',
                ],
                'purchase' => [
                    'transaction' => 'HP_AUTO_999',
                    'price'       => ['value' => 497.00],
                ],
            ],
        ];

        $request = new Request([], $payload, [], [], [], $payload);
        $response = $controller->handle($request, 'hotmart');

        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);

        $this->assertTrue($body['received']);
        $this->assertSame('paid', $body['order_status']);
        $this->assertTrue($body['provisioned']);
        $this->assertGreaterThan(0, $body['user_id']);
        $this->assertSame($courseId, $body['course_id']);

        // Verifica se o usuário foi criado na tabela users
        $stmtUser = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmtUser->execute(['novato@alunohotmart.com']);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($user);
        $this->assertSame('Novo Aluno Hotmart', $user['name']);

        // Verifica se o pedido foi gravado como pago
        $order = $orderRepo->findByOrderRef('HOTMART_HP_AUTO_999');
        $this->assertNotNull($order);
        $this->assertSame('paid', $order['status']);
        $this->assertSame('hotmart', $order['gateway']);
        $this->assertEquals(497.00, (float)$order['total']);

        // Verifica se o evento 'payment.approved' foi disparado com os dados exatos para matricular o aluno
        $this->assertArrayHasKey('payment.approved', $capturedEvents);
        $this->assertSame((int)$user['id'], $capturedEvents['payment.approved']['user_id']);
        $this->assertSame($courseId, $capturedEvents['payment.approved']['course_id']);
        $this->assertSame('HOTMART_HP_AUTO_999', $capturedEvents['payment.approved']['order_ref']);
    }

    public function testWebhookWithUnknownProductDoesNotCreateOrderOrEnrollment(): void
    {
        $orderRepo = new PdoOrderRepository($this->pdo);
        $gatewayRegistry = new PaymentGatewayRegistry();
        $settings = $this->createMock(CommerceSettingsInterface::class);
        $settings->method('get')->willReturnCallback(fn($k) => match ($k) {
            'hotmart_hottok' => 'SECRET_TEST_HOTTOK',
            default => null,
        });
        $gatewayRegistry->register(new HotmartGateway(settings: $settings));

        $capturedEvents = [];
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(function ($event, $data) use (&$capturedEvents) {
            $capturedEvents[$event] = $data;
            return null;
        });

        $controller = new WebhookController(
            $orderRepo,
            $gatewayRegistry,
            $dispatcher,
            $this->pdo
        );

        $payload = [
            'event'  => 'PURCHASE_APPROVED',
            'hottok' => 'SECRET_TEST_HOTTOK',
            'data'   => [
                'buyer'    => [
                    'email' => 'estranho@qualquer.com',
                    'name'  => 'Comprador Desconhecido',
                ],
                'product'  => ['id' => 'PRODUTO_INEXISTENTE_NO_SISTEMA'],
                'purchase' => [
                    'transaction'         => 'HOTMART_ORPHAN_001',
                    'order_date'          => 1600000000000,
                    'price'               => ['value' => 150.00],
                    'recurrent'           => false,
                    'payment'             => ['type' => 'CREDIT_CARD', 'installments_number' => 1],
                ],
            ],
        ];

        $request = new Request([], $payload, [], [], [], $payload);
        $response = $controller->handle($request, 'hotmart');

        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);

        $this->assertTrue($body['received']);
        $this->assertSame('orphan', $body['action'] ?? null);

        // Não dispara evento de aprovação de matrícula nem cria pedido
        $this->assertArrayNotHasKey('payment.approved', $capturedEvents);
        $this->assertNull($orderRepo->findByOrderRef('HOTMART_ORPHAN_001'));
    }
}
