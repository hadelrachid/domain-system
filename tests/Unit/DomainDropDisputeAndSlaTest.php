<?php

declare(strict_types=1);

namespace Tests\Unit;

use DateTimeImmutable;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Plugins\domain_drop\Contracts\AliExpressApiClientInterface;
use DomainSystem\Plugins\domain_drop\Contracts\CurrencyConverterInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipCatalogRepositoryInterface;
use DomainSystem\Plugins\domain_drop\Contracts\ProductMiningServiceInterface;
use DomainSystem\Plugins\domain_drop\Controllers\CustomerAccountController;
use DomainSystem\Plugins\domain_drop\Controllers\DropshipAdminController;
use DomainSystem\Plugins\domain_drop\Repositories\PdoDropshipCatalogRepository;
use DomainSystem\Plugins\domain_drop\Repositories\PdoDropshipCustomerRepository;
use DomainSystem\Plugins\domain_drop\Repositories\PdoDropshipDisputeRepository;
use DomainSystem\Plugins\domain_drop\Repositories\PdoDropshipOrderRepository;
use DomainSystem\Plugins\domain_drop\Services\DisputeAttachmentService;
use DomainSystem\Plugins\domain_drop\Services\OrderSlaProgressService;
use DomainSystem\Plugins\domain_drop\Services\SellerMediationService;
use DomainSystem\SystemApps\auth\Contracts\EmailSenderInterface;
use PDO;
use PHPUnit\Framework\TestCase;

class DomainDropDisputeAndSlaTest extends TestCase
{
    private PDO $pdo;
    private string $tempRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $m1 = require dirname(__DIR__, 2) . '/src/Plugins/domain_drop/migrations/2026_11_10_000001_create_domain_drop_tables.php';
        ($m1['up'])($this->pdo);

        $m2 = require dirname(__DIR__, 2) . '/src/Plugins/domain_drop/migrations/2026_11_12_000001_create_customer_disputes_and_mediation.php';
        ($m2['up'])($this->pdo);

        $this->tempRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dd_dispute_test_' . bin2hex(random_bytes(4));
        @mkdir($this->tempRoot, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->tempRoot);
        parent::tearDown();
    }

    public function testOrderSlaProgressCountdownAndStates(): void
    {
        $sla = new OrderSlaProgressService();
        $order = [
            'order_ref'              => 'ORD_BR_001',
            'created_at'             => '2026-11-02 10:00:00', // Segunda-feira
            'seller_origin'          => 'BR',
            'expected_delivery_days' => 7,
            'status'                 => 'shipped',
        ];

        // Segunda-feira (Dia 0): barra cheia (100%, 7 dias restantes)
        $day0 = $sla->calculate($order, [], new DateTimeImmutable('2026-11-02 18:00:00'));
        $this->assertSame('in_transit_countdown', $day0['state']);
        $this->assertSame(7, $day0['days_remaining']);
        $this->assertSame(100, $day0['percent_remaining']);

        // Terça-feira (Dia 1): diminui para 6 dias (~86%)
        $day1 = $sla->calculate($order, [], new DateTimeImmutable('2026-11-03 12:00:00'));
        $this->assertSame(6, $day1['days_remaining']);
        $this->assertSame(86, $day1['percent_remaining']);

        // Quarta-feira (Dia 2): diminui para 5 dias (~71%)
        $day2 = $sla->calculate($order, [], new DateTimeImmutable('2026-11-04 12:00:00'));
        $this->assertSame(5, $day2['days_remaining']);
        $this->assertSame(71, $day2['percent_remaining']);

        // Entregue pelo SDK dentro dos 7 dias e sem reclamações -> Tudo Ok!
        $sdkOk = $sla->calculate($order, [], new DateTimeImmutable('2026-11-05 15:00:00'), [
            'delivered'  => true,
            'last_event' => 'Objeto entregue ao destinatário',
        ]);
        $this->assertSame('ok_delivered', $sdkOk['state']);
        $this->assertStringContainsString('Tudo Ok', $sdkOk['label']);

        // Passou dos 7 dias (atraso) e cliente NÃO abriu reclamação -> Aguardar cliente
        $delayed = $sla->calculate($order, [], new DateTimeImmutable('2026-11-11 10:00:00'));
        $this->assertSame('delayed_awaiting_customer', $delayed['state']);
        $this->assertStringContainsString('Aguardando', $delayed['label']);

        // Cliente abriu reclamação -> Estado complaint_open
        $withComplaint = $sla->calculate($order, [['id' => 1, 'mediation_status' => 'open']], new DateTimeImmutable('2026-11-04 12:00:00'));
        $this->assertSame('complaint_open', $withComplaint['state']);
    }

    public function testCustomerRegisterLoginComplaintWithPdfAttachmentAndAccessControl(): void
    {
        $customerRepo = new PdoDropshipCustomerRepository($this->pdo);
        $orderRepo = new PdoDropshipOrderRepository($this->pdo);
        $disputeRepo = new PdoDropshipDisputeRepository($this->pdo);
        $session = $this->createInMemorySession();
        $attachments = new DisputeAttachmentService($this->tempRoot, false);

        $controller = new CustomerAccountController(
            $customerRepo,
            $orderRepo,
            $disputeRepo,
            $session,
            $attachments,
            new OrderSlaProgressService()
        );

        // 1. Registro de cliente na vitrine (isolado de admin)
        $regRequest = new Request(request: [
            'csrf_token'       => $session->getCsrfToken(),
            'name'             => 'Maria Oliveira',
            'email'            => 'maria@cliente.com',
            'phone'            => '11999998888',
            'password'         => 'segredo123',
            'password_confirm' => 'segredo123',
        ]);
        $regRes = $controller->register($regRequest);
        $this->assertSame(302, $regRes->getStatusCode());
        $this->assertGreaterThan(0, (int)$session->get('customer_id'));
        $this->assertSame('maria@cliente.com', $session->get('customer_email'));
        $this->assertNull($session->get('user_id'));
        $this->assertNull($session->get('user_role'));

        // 2. Pedido criado para Maria
        $orderRepo->create([
            'order_ref'              => 'ORD_CN_777',
            'customer_name'          => 'Maria Oliveira',
            'customer_email'         => 'maria@cliente.com',
            'aliexpress_order_id'    => '81999888777666',
            'tracking_code'          => 'NM123456789BR',
            'status'                 => 'shipped',
            'total_paid_brl'         => 189.90,
            'seller_origin'          => 'CN',
            'expected_delivery_days' => 25,
        ]);

        // 3. Rejeita upload malicioso (.php disfarçado)
        $badTmp = $this->tempRoot . DIRECTORY_SEPARATOR . 'evil.php';
        file_put_contents($badTmp, '<?php echo "hack"; ?>');
        $badComplaintReq = new Request(
            request: [
                'csrf_token'  => $session->getCsrfToken(),
                'order_ref'   => 'ORD_CN_777',
                'reason'      => 'produto_danificado',
                'description' => 'Produto chegou com vidro quebrado na embalagem.',
            ],
            files: [
                'evidences' => [
                    'name'     => ['shell.php'],
                    'tmp_name' => [$badTmp],
                    'error'    => [UPLOAD_ERR_OK],
                    'size'     => [filesize($badTmp)],
                ],
            ]
        );
        $controller->submitComplaint($badComplaintReq);
        $this->assertCount(0, $disputeRepo->findByOrderRef('ORD_CN_777'));

        // 4. Aceita PDF válido e grava reclamação vinculada ao pedido
        $pdfTmp = $this->tempRoot . DIRECTORY_SEPARATOR . 'laudo.pdf';
        file_put_contents($pdfTmp, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF");
        $goodComplaintReq = new Request(
            request: [
                'csrf_token'  => $session->getCsrfToken(),
                'order_ref'   => 'ORD_CN_777',
                'reason'      => 'produto_danificado',
                'description' => 'Produto chegou quebrado e vidro quebrado, solicito reembolso.',
            ],
            files: [
                'evidences' => [
                    'name'     => ['foto-dano.pdf'],
                    'tmp_name' => [$pdfTmp],
                    'error'    => [UPLOAD_ERR_OK],
                    'size'     => [filesize($pdfTmp)],
                ],
            ]
        );
        $resComplaint = $controller->submitComplaint($goodComplaintReq);
        $this->assertSame(302, $resComplaint->getStatusCode());

        $savedDisputes = $disputeRepo->findByOrderRef('ORD_CN_777');
        $this->assertCount(1, $savedDisputes);
        $this->assertCount(1, $savedDisputes[0]['attachments']);
        $storedFilename = $savedDisputes[0]['attachments'][0]['filename'];
        $disputeId = (string)$savedDisputes[0]['id'];

        // 5. Dono da conta consegue baixar/visualizar o anexo PDF; terceiro recebe 403
        $ownerDownload = $controller->serveAttachment(new Request(), $disputeId, $storedFilename);
        $this->assertSame(200, $ownerDownload->getStatusCode());
        $this->assertStringStartsWith('%PDF-1.4', $ownerDownload->getContent());

        $controller->logout(new Request());
        $anonDownload = $controller->serveAttachment(new Request(), $disputeId, $storedFilename);
        $this->assertSame(403, $anonDownload->getStatusCode());
    }

    public function testSellerMediationTranslatesForChineseSellerAndKeepsPortugueseForBrazil(): void
    {
        $orderRepo = new PdoDropshipOrderRepository($this->pdo);
        $disputeRepo = new PdoDropshipDisputeRepository($this->pdo);

        $sentEmails = [];
        $mailer = new class ($sentEmails) implements EmailSenderInterface {
            public function __construct(private array &$log)
            {
            }

            public function send(string $to, string $subject, string $message): void
            {
                $this->log[] = compact('to', 'subject', 'message');
            }
        };

        $mediationService = new SellerMediationService($disputeRepo, $mailer);

        // Caso 1: Fornecedor Chinês -> Traduz para zh-CN
        $orderRepo->create([
            'order_ref'     => 'ORD_CN_900',
            'customer_name' => 'Carlos Souza',
            'customer_email'=> 'carlos@teste.com',
            'aliexpress_order_id' => '8100001234',
            'tracking_code' => 'LP99887766CN',
            'status'        => 'delivered',
            'total_paid_brl'=> 150.0,
            'seller_origin' => 'CN',
            'seller_email'  => 'supplier@aliexpress-cn.example.com',
        ]);
        $cnDisputeId = $disputeRepo->create([
            'order_ref'      => 'ORD_CN_900',
            'customer_email' => 'carlos@teste.com',
            'reason'         => 'produto_danificado',
            'description'    => 'O produto chegou quebrado na caixa e solicito reembolso.',
            'seller_origin'  => 'CN',
            'seller_email'   => 'supplier@aliexpress-cn.example.com',
            'attachments_json' => [
                ['filename' => str_repeat('a', 32) . '.pdf', 'original_name' => 'avaria.pdf', 'mime' => 'application/pdf', 'size' => 1024],
            ],
        ]);

        $cnOrder = $orderRepo->findByOrderRef('ORD_CN_900');
        $cnDispute = $disputeRepo->findById($cnDisputeId);
        $resCn = $mediationService->sendMediation($cnOrder, $cnDispute, 'https://rachidd.com');

        $this->assertTrue($resCn['success']);
        $this->assertSame('zh-CN', $resCn['seller_language']);
        $this->assertTrue($resCn['email_dispatched']);
        $this->assertStringContainsString('尊敬的供应商您好', $resCn['translated_message']);
        $this->assertStringContainsString('到货时已破损', $resCn['translated_message']);
        $this->assertStringContainsString('申请退款', $resCn['translated_message']);
        $this->assertStringContainsString('avaria.pdf', $resCn['translated_message']);

        // Caso 2: Fornecedor Brasileiro -> Mantém em Português (pt-BR)
        $orderRepo->create([
            'order_ref'     => 'ORD_BR_901',
            'customer_name' => 'Ana Paula',
            'customer_email'=> 'ana@teste.com',
            'aliexpress_order_id' => '8100009999',
            'tracking_code' => 'BR123456789BR',
            'status'        => 'delivered',
            'total_paid_brl'=> 99.0,
            'seller_origin' => 'BR',
            'seller_email'  => 'sac@fornecedorbr.example.com',
        ]);
        $brDisputeId = $disputeRepo->create([
            'order_ref'      => 'ORD_BR_901',
            'customer_email' => 'ana@teste.com',
            'reason'         => 'item_errado',
            'description'    => 'Enviaram a cor errada do produto.',
            'seller_origin'  => 'BR',
            'seller_email'   => 'sac@fornecedorbr.example.com',
        ]);

        $brOrder = $orderRepo->findByOrderRef('ORD_BR_901');
        $brDispute = $disputeRepo->findById($brDisputeId);
        $resBr = $mediationService->sendMediation($brOrder, $brDispute, 'https://rachidd.com');

        $this->assertSame('pt-BR', $resBr['seller_language']);
        $this->assertStringContainsString('Prezado Fornecedor (Brasil)', $resBr['translated_message']);
        $this->assertStringNotContainsString('尊敬的供应商', $resBr['translated_message']);
        $this->assertCount(2, $sentEmails);

        $updatedCn = $disputeRepo->findById($cnDisputeId);
        $this->assertSame('sent', $updatedCn['mediation_status']);
        $this->assertSame('zh-CN', $updatedCn['seller_language']);
    }

    public function testAdminSdkTrackingCheckAndSingleButtonMediationEndpoint(): void
    {
        $customerRepo = new PdoDropshipCustomerRepository($this->pdo);
        $orderRepo = new PdoDropshipOrderRepository($this->pdo);
        $disputeRepo = new PdoDropshipDisputeRepository($this->pdo);
        $session = $this->createInMemorySession();

        $orderRepo->create([
            'order_ref'              => 'ORD_ADM_100',
            'customer_name'          => 'João Admin Test',
            'customer_email'         => 'joao@teste.com',
            'aliexpress_order_id'    => '8188881111',
            'tracking_code'          => 'BR555666777BR',
            'status'                 => 'shipped',
            'total_paid_brl'         => 220.0,
            'seller_origin'          => 'BR',
            'expected_delivery_days' => 7,
        ]);

        $orderRepo->create([
            'order_ref'              => 'ORD_ADM_200',
            'customer_name'          => 'Luiza Chin Test',
            'customer_email'         => 'luiza@teste.com',
            'aliexpress_order_id'    => '8188882222',
            'tracking_code'          => 'CN555666777CN',
            'status'                 => 'shipped',
            'total_paid_brl'         => 180.0,
            'seller_origin'          => 'CN',
            'expected_delivery_days' => 25,
        ]);

        $disputeId = $disputeRepo->create([
            'order_ref'      => 'ORD_ADM_200',
            'customer_email' => 'luiza@teste.com',
            'reason'         => 'produto_danificado',
            'description'    => 'Produto chegou danificado e não funciona.',
            'seller_origin'  => 'CN',
        ]);

        $mockApiClient = $this->createMock(AliExpressApiClientInterface::class);
        $mockApiClient->method('queryTrackingInfo')->willReturn([
            'delivered' => true,
            'details'   => [
                ['event_desc' => 'Objeto entregue ao destinatário'],
            ],
        ]);

        $adminController = new DropshipAdminController(
            catalogRepo: $this->createMock(DropshipCatalogRepositoryInterface::class),
            miningService: $this->createMock(ProductMiningServiceInterface::class),
            currencyConverter: $this->createMock(CurrencyConverterInterface::class),
            session: $session,
            orderRepo: $orderRepo,
            mediaSync: null,
            basePath: $this->tempRoot,
            customerRepo: $customerRepo,
            disputeRepo: $disputeRepo,
            slaService: new OrderSlaProgressService(),
            mediationService: new SellerMediationService($disputeRepo),
            apiClient: $mockApiClient
        );

        // 1. Checagem SDK marca pedido nacional como entregue ("Tudo Ok!")
        $sdkReq = new Request(
            request: [
                'csrf_token' => $session->getCsrfToken(),
                'order_ref'  => 'ORD_ADM_100',
            ],
            server: ['HTTP_ACCEPT' => 'application/json']
        );
        $sdkRes = $adminController->checkOrderSdk($sdkReq, '1');
        $this->assertSame(200, $sdkRes->getStatusCode());
        $sdkJson = json_decode($sdkRes->getContent(), true);
        $this->assertTrue($sdkJson['delivered']);

        $refreshedBr = $orderRepo->findByOrderRef('ORD_ADM_100');
        $this->assertSame('delivered', $refreshedBr['status']);
        $this->assertNotEmpty($refreshedBr['sdk_delivered_at']);

        // 2. Botão único de intermediação envia em Chinês (zh-CN) para pedido da China
        $medReq = new Request(
            request: [
                'csrf_token' => $session->getCsrfToken(),
                'order_ref'  => 'ORD_ADM_200',
                'dispute_id' => (string)$disputeId,
            ],
            server: ['HTTP_ACCEPT' => 'application/json']
        );
        $medRes = $adminController->mediateDispute($medReq, '2');
        $this->assertSame(200, $medRes->getStatusCode());
        $medJson = json_decode($medRes->getContent(), true);
        $this->assertTrue($medJson['success']);
        $this->assertSame('zh-CN', $medJson['seller_language']);
        $this->assertStringContainsString('商品损坏', $medJson['translated_message']);
    }

    public function testFooterGlobalTrendsCategoryFiltersDoNotMixProducts(): void
    {
        $catalogRepo = new PdoDropshipCatalogRepository($this->pdo);
        $now = date('Y-m-d H:i:s');

        $seed = [
            ['1001', 'Smartwatch Ultra 2 GPS', 'smartwatch-ultra-2', 'Eletrônicos & Gadget'],
            ['1002', 'Pulseira Homem De Ferro Para Apple Watch', 'pulseira-apple-watch', 'Eletrônicos & Gadget'],
            ['1003', 'Fone De Ouvido Lenovo Tws Pro Anc', 'fone-lenovo-tws-pro', 'Eletrônicos & Gadget'],
            ['1004', 'Fita Led Cob Tuya Wifi Casa Inteligente', 'fita-led-cob', 'Casa Inteligente & Organização'],
            ['1005', 'Suporte Automotivo Magnético 360 Para Celular', 'suporte-celular-mag', 'Acessórios para Veículos'],
            ['1006', 'Precisão Chave De Fenda Para Reparo Do Telefone', 'chave-reparo-telefone', 'Eletrônicos & Gadget'],
        ];

        $stmt = $this->pdo->prepare("
            INSERT INTO domain_drop_catalog (
                aliexpress_id, title, slug, cost_price_usd, sale_price_brl,
                main_image_url, category, status, created_at, updated_at
            ) VALUES (?, ?, ?, 10.00, 99.90, 'https://example.com/a.jpg', ?, 'published', ?, ?)
        ");
        foreach ($seed as $item) {
            $stmt->execute([$item[0], $item[1], $item[2], $item[3], $now, $now]);
        }

        $smartwatches = array_map(static fn ($d): string => $d->slug, $catalogRepo->list(['status' => 'published', 'category' => 'Smartwatches'], 20));
        $this->assertEqualsCanonicalizing(['smartwatch-ultra-2', 'pulseira-apple-watch'], $smartwatches);

        $audio = array_map(static fn ($d): string => $d->slug, $catalogRepo->list(['status' => 'published', 'category' => 'Audio'], 20));
        $this->assertSame(['fone-lenovo-tws-pro'], $audio);

        $casa = array_map(static fn ($d): string => $d->slug, $catalogRepo->list(['status' => 'published', 'category' => 'Casa'], 20));
        $this->assertSame(['fita-led-cob'], $casa);

        $celular = array_map(static fn ($d): string => $d->slug, $catalogRepo->list(['status' => 'published', 'category' => 'Celular'], 20));
        $this->assertEqualsCanonicalizing(['suporte-celular-mag', 'chave-reparo-telefone'], $celular);
    }

    public function testShowcaseBlockAllowedPartTypesAndFlexBuilderThemeIsolation(): void
    {
        $catalogRepo = $this->createMock(DropshipCatalogRepositoryInterface::class);
        $block = new \DomainSystem\Plugins\domain_drop\Blocks\DropshipShowcaseBlock($catalogRepo);

        $allowed = $block->getAllowedPartTypes();
        $this->assertContains(\DomainSystem\Core\Theme\Parts\Contracts\ThemePartInterface::TYPE_SECTION, $allowed);
        $this->assertContains(\DomainSystem\Core\Theme\Parts\Contracts\ThemePartInterface::TYPE_FOOTER, $allowed);

        $discovery = new \DomainSystem\Plugins\flextheme\Services\ThemeDiscoveryService(dirname(__DIR__, 2));
        $this->assertFalse($discovery->supportsFlexBuilder('rachidd_store'));
        $this->assertTrue($discovery->supportsFlexBuilder('default'));
    }

    public function testAdminCustomersListAndIntegratedTrackingAndInquiriesInCustomerArea(): void
    {
        $customerRepo = new PdoDropshipCustomerRepository($this->pdo);
        $orderRepo = new PdoDropshipOrderRepository($this->pdo);
        $disputeRepo = new PdoDropshipDisputeRepository($this->pdo);
        $session = $this->createInMemorySession();

        $cid = $customerRepo->create([
            'name'          => 'Marina Compradora',
            'email'         => 'marina@teste.com',
            'cpf'           => '111.222.333-44',
            'phone'         => '(11) 98888-7777',
            'password_hash' => password_hash('segredo123', PASSWORD_DEFAULT),
            'status'        => 'active',
        ]);

        $orderRepo->create([
            'order_ref'           => 'ORD_MARINA_01',
            'customer_name'       => 'Marina Compradora',
            'customer_email'      => 'marina@teste.com',
            'aliexpress_order_id' => '8199990001',
            'tracking_code'       => 'NM999888777BR',
            'status'              => 'shipped',
            'total_paid_brl'      => 189.90,
            'seller_origin'       => 'BR',
        ]);

        // 1. Verifica métricas e listagem administrativa de clientes
        $metrics = $customerRepo->getCustomerMetrics();
        $this->assertSame(1, $metrics['total_customers']);
        $this->assertSame(1, $metrics['active_customers']);
        $this->assertSame(1, $metrics['linked_orders']);

        $adminController = new DropshipAdminController(
            catalogRepo: $this->createMock(DropshipCatalogRepositoryInterface::class),
            miningService: $this->createMock(ProductMiningServiceInterface::class),
            currencyConverter: $this->createMock(CurrencyConverterInterface::class),
            session: $session,
            orderRepo: $orderRepo,
            mediaSync: null,
            basePath: $this->tempRoot,
            customerRepo: $customerRepo,
            disputeRepo: $disputeRepo,
            slaService: new OrderSlaProgressService()
        );

        $adminListResp = $adminController->customers(new Request(['q' => 'marina'], [], ['REQUEST_METHOD' => 'GET']));
        $this->assertSame(200, $adminListResp->getStatusCode());
        $this->assertStringContainsString('Marina Compradora', $adminListResp->getContent());
        $this->assertStringContainsString('111.222.333-44', $adminListResp->getContent());

        // Suspende e reativa conta
        $adminController->updateCustomerStatus(new Request([], [
            'csrf_token' => $session->getCsrfToken(),
            'status'     => 'suspended',
        ], ['REQUEST_METHOD' => 'POST']), (string)$cid);
        $this->assertSame('suspended', $customerRepo->findById($cid)['status']);

        $adminController->updateCustomerStatus(new Request([], [
            'csrf_token' => $session->getCsrfToken(),
            'status'     => 'active',
        ], ['REQUEST_METHOD' => 'POST']), (string)$cid);
        $this->assertSame('active', $customerRepo->findById($cid)['status']);

        // 2. Verifica rastreio integrado e envio de Dúvida/Observação na área do cliente
        $session->set('customer_id', $cid);
        $session->set('customer_name', 'Marina Compradora');
        $session->set('customer_email', 'marina@teste.com');

        $catalogMock = $this->createMock(DropshipCatalogRepositoryInterface::class);
        $sampleProduct = new \DomainSystem\Plugins\domain_drop\DTO\DropshipProductDto(
            aliexpressId: '100500998877',
            title: 'Smartwatch Ultra Titanium 49mm',
            slug: 'smartwatch-ultra-titanium-49mm',
            costPriceUsd: 15.0,
            salePriceBrl: 199.90,
            mainImageUrl: 'https://example.com/watch.jpg',
            attributes: ['origem_brasil' => true, 'seller_name' => 'TechBR Oficial'],
            id: 42
        );
        $catalogMock->method('list')->willReturn([$sampleProduct]);
        $catalogMock->method('findById')->with(42)->willReturn($sampleProduct);

        $accountController = new CustomerAccountController(
            customerRepo: $customerRepo,
            orderRepo: $orderRepo,
            disputeRepo: $disputeRepo,
            session: $session,
            attachmentService: new DisputeAttachmentService($this->tempRoot),
            slaService: new OrderSlaProgressService(),
            catalogRepo: $catalogMock
        );

        $accResp = $accountController->account(new Request(['rastrear' => 'ORD_MARINA_01'], [], ['REQUEST_METHOD' => 'GET']));
        $this->assertSame(200, $accResp->getStatusCode());
        $this->assertStringContainsString('Acompanhamento de Entrega', $accResp->getContent());
        $this->assertStringContainsString('NM999888777BR', $accResp->getContent());
        $this->assertStringContainsString('Smartwatch Ultra Titanium 49mm', $accResp->getContent());

        // Caso A: Chamado Geral sem vínculo
        $inqResp = $accountController->submitComplaint(new Request([], [
            'csrf_token'  => $session->getCsrfToken(),
            'order_ref'   => 'GERAL',
            'reason'      => 'duvida_entrega',
            'description' => 'Gostaria de saber se posso retirar nos Correios ou receber na portaria.',
        ], ['REQUEST_METHOD' => 'POST']));
        $this->assertSame(302, $inqResp->getStatusCode());

        // Caso B: Reclamação / Observação vinculando opcionalmente Produto da Loja (#42) e Código Manual
        $linkedResp = $accountController->submitComplaint(new Request([], [
            'csrf_token'         => $session->getCsrfToken(),
            'order_ref'          => 'GERAL',
            'manual_order_ref'   => 'ORD-MANUAL-777',
            'catalog_product_id' => '42',
            'reason'             => 'produto_danificado',
            'description'        => 'Comprei este relógio ontem e quero confirmar a cor da pulseira.',
        ], ['REQUEST_METHOD' => 'POST']));
        $this->assertSame(302, $linkedResp->getStatusCode());

        $disputes = $disputeRepo->findByCustomerEmail('marina@teste.com');
        $this->assertCount(2, $disputes);
        $this->assertSame('ORD-MANUAL-777', $disputes[0]['order_ref']);
        $this->assertStringContainsString('Produto: #42 - Smartwatch Ultra Titanium 49mm', $disputes[0]['description']);
        $this->assertStringContainsString('TechBR Oficial', (string)$disputes[0]['seller_name']);
        $this->assertSame('GERAL', $disputes[1]['order_ref']);
    }

    private function createInMemorySession(): SessionManagerInterface
    {
        return new class implements SessionManagerInterface {
            private array $store = [];
            private ?array $flash = null;
            private string $csrf = 'test_csrf_token_domain_drop';

            public function start(): void
            {
            }

            public function set(string $key, $value): void
            {
                $this->store[$key] = $value;
            }

            public function get(string $key, $default = null)
            {
                return $this->store[$key] ?? $default;
            }

            public function has(string $key): bool
            {
                return array_key_exists($key, $this->store);
            }

            public function remove(string $key): void
            {
                unset($this->store[$key]);
            }

            public function destroy(): void
            {
                $this->store = [];
            }

            public function regenerate(): void
            {
            }

            public function setFlash(string $type, string $message): void
            {
                $this->flash = ['type' => $type, 'msg' => $message];
            }

            public function getFlash(): ?array
            {
                $f = $this->flash;
                $this->flash = null;
                return $f;
            }

            public function getCsrfToken(): string
            {
                return $this->csrf;
            }

            public function validateCsrfToken(?string $token): bool
            {
                return $token !== null && hash_equals($this->csrf, $token);
            }
        };
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = @scandir($dir);
        if (!is_array($items)) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
