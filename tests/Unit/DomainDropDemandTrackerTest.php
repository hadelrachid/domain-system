<?php

declare(strict_types=1);

namespace Tests\Unit;

use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Plugins\domain_drop\DTO\DemandEvent;
use DomainSystem\Plugins\domain_drop\Repositories\PdoDemandMetricsRepository;
use DomainSystem\Plugins\domain_drop\Services\SessionGuardedDemandTracker;
use PDO;
use PHPUnit\Framework\TestCase;

final class DomainDropDemandTrackerTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function testDemandMetricsRepositoryIncrementsAndAggregatesMonthly(): void
    {
        $repo = new PdoDemandMetricsRepository($this->pdo);

        // 1. Incrementa clique de detalhes
        $repo->incrementMetric('ALI-101', 'Carregador Turbo GaN 65W', 'eletronicos', 'view_details');
        
        // 2. Incrementa clique de comprar (fundo de funil)
        $repo->incrementMetric('ALI-101', 'Carregador Turbo GaN 65W', 'eletronicos', 'buy_click');
        $repo->incrementMetric('ALI-101', 'Carregador Turbo GaN 65W', 'eletronicos', 'buy_click');

        // 3. Incrementa outro produto em categoria diferente
        $repo->incrementMetric('ALI-202', 'Kit 12 Meias Algodão', 'moda', 'view_details');

        $period = date('Y-m');
        $rankings = $repo->getMonthlyRankings($period, 10);

        $this->assertCount(2, $rankings);
        $top = $rankings[0];
        $this->assertSame('ALI-101', $top['product_id']);
        $this->assertSame('Carregador Turbo GaN 65W', $top['product_title']);
        $this->assertSame(1, (int)$top['detail_clicks']);
        $this->assertSame(2, (int)$top['buy_clicks']);
        // Score ponderado esperado: (1 * 1) + (2 * 4) = 9
        $this->assertSame(9, (int)$top['demand_score']);

        // 4. Perfil de categorias
        $profile = $repo->getAudienceCategoryProfile($period);
        $this->assertArrayHasKey('eletronicos', $profile);
        $this->assertArrayHasKey('moda', $profile);
        $this->assertSame(9, $profile['eletronicos']['score']);
        $this->assertSame(1, $profile['moda']['score']);

        // 5. Live pulse
        $pulse = $repo->getLivePulseMetrics();
        $this->assertSame(2, $pulse['active_products_count']);
        $this->assertSame(2, $pulse['total_detail_clicks']);
        $this->assertSame(2, $pulse['total_buy_clicks']);
        $this->assertSame(10, $pulse['total_demand_score']);
    }

    public function testSessionGuardedDemandTrackerPreventsDuplicateSpamInSameSession(): void
    {
        $repo = new PdoDemandMetricsRepository($this->pdo);
        
        $sessionStore = [];
        $sessionMock = $this->createMock(SessionManagerInterface::class);
        $sessionMock->method('get')->willReturnCallback(function (string $key, mixed $default = null) use (&$sessionStore) {
            return $sessionStore[$key] ?? $default;
        });
        $sessionMock->method('set')->willReturnCallback(function (string $key, mixed $value) use (&$sessionStore) {
            $sessionStore[$key] = $value;
        });

        $tracker = new SessionGuardedDemandTracker($repo, $sessionMock);

        $event1 = new DemandEvent('ALI-303', 'Fone Sem Fio Pro', 'audio', 'view_details');
        
        // Primeiro clique: aceito
        $this->assertTrue($tracker->recordInteraction($event1));

        // Segundo clique imediato na mesma sessão: bloqueado pelo anti-ruído
        $this->assertFalse($tracker->recordInteraction($event1));

        // Clique em 'buy_click' para o mesmo produto: aceito (pois é ação diferente)
        $eventBuy = new DemandEvent('ALI-303', 'Fone Sem Fio Pro', 'audio', 'buy_click');
        $this->assertTrue($tracker->recordInteraction($eventBuy));

        // Conversão paga: NUNCA deve ser bloqueada
        $eventConv = new DemandEvent('ALI-303', 'Fone Sem Fio Pro', 'audio', 'conversion');
        $this->assertTrue($tracker->recordInteraction($eventConv));
        $this->assertTrue($tracker->recordInteraction($eventConv));

        $period = date('Y-m');
        $rankings = $repo->getMonthlyRankings($period, 1);
        $this->assertSame(1, (int)$rankings[0]['detail_clicks']);
        $this->assertSame(1, (int)$rankings[0]['buy_clicks']);
        $this->assertSame(2, (int)$rankings[0]['conversions']);
        // Score: (1 * 1) + (1 * 4) + (2 * 10) = 25
        $this->assertSame(25, (int)$rankings[0]['demand_score']);
    }

    public function testDropshipFrontControllerDispatchesDemandTrackEndpoint(): void
    {
        $repo = new PdoDemandMetricsRepository($this->pdo);
        $tracker = new SessionGuardedDemandTracker($repo);

        $catalogRepo = $this->createMock(\DomainSystem\Plugins\domain_drop\Contracts\DropshipCatalogRepositoryInterface::class);
        $frontController = new \DomainSystem\Plugins\domain_drop\Controllers\DropshipFrontController(
            catalogRepo: $catalogRepo,
            demandTracker: $tracker
        );

        $request = new \DomainSystem\Core\Http\Request([], [], [], [], [], [
            'product_id' => 'ALI-777',
            'title'      => 'Kit Meias Cano Alto',
            'category'   => 'moda',
            'action'     => 'buy_click',
        ]);

        $response = $frontController->trackDemand($request);
        $this->assertSame(200, $response->getStatusCode());
        
        $body = json_decode((string)$response->getContent(), true);
        $this->assertTrue($body['success']);
        $this->assertTrue($body['tracked']);

        $rankings = $repo->getMonthlyRankings(date('Y-m'), 1);
        $this->assertCount(1, $rankings);
        $this->assertSame('ALI-777', $rankings[0]['product_id']);
        $this->assertSame(1, (int)$rankings[0]['buy_clicks']);
        $this->assertSame(4, (int)$rankings[0]['demand_score']);
    }

    public function testDropshipAdminControllerRendersDemandLedgerAndPulse(): void
    {
        $repo = new PdoDemandMetricsRepository($this->pdo);
        $event = new DemandEvent(
            productId: 'ALI-999',
            productTitle: 'Smartwatch AMOLED',
            categorySlug: 'gadgets',
            actionType: 'buy_click',
            customerId: 42,
            customerName: 'João Silva',
            customerEmail: 'joao@exemplo.com',
            userType: 'cliente_logado'
        );
        $repo->incrementMetric('ALI-999', 'Smartwatch AMOLED', 'gadgets', 'buy_click', $event);

        $sessionMock = $this->createMock(SessionManagerInterface::class);
        $sessionMock->method('getCsrfToken')->willReturn('csrf_test_ledger');

        $catalogRepo = $this->createMock(\DomainSystem\Plugins\domain_drop\Contracts\DropshipCatalogRepositoryInterface::class);
        $miningService = $this->createMock(\DomainSystem\Plugins\domain_drop\Contracts\ProductMiningServiceInterface::class);
        $converter = $this->createMock(\DomainSystem\Plugins\domain_drop\Contracts\CurrencyConverterInterface::class);

        $adminController = new \DomainSystem\Plugins\domain_drop\Controllers\DropshipAdminController(
            catalogRepo: $catalogRepo,
            miningService: $miningService,
            currencyConverter: $converter,
            session: $sessionMock,
            metricsRepo: $repo
        );

        $request = new \DomainSystem\Core\Http\Request(['period' => date('Y-m')]);
        $response = $adminController->demandLedger($request);

        $this->assertSame(200, $response->getStatusCode());
        $html = (string)$response->getContent();

        $this->assertStringContainsString('Inteligência de Demanda &amp; Livro de Presença', $html);
        $this->assertStringContainsString('Smartwatch AMOLED', $html);
        $this->assertStringContainsString('Gráfico de Colunas: Produtos Mais Quentes', $html);
        $this->assertStringContainsString('Perfil dos Compradores', $html);
        $this->assertStringContainsString('gadgets', $html);
        $this->assertStringContainsString('Trilha de Interações Recentes', $html);
        $this->assertStringContainsString('name="period"', $html);
        $this->assertStringContainsString('Ano Vigente', $html);
        $this->assertStringContainsString('Ano Anterior', $html);
        $this->assertStringContainsString('Cliente Cadastrado', $html);
        $this->assertStringContainsString('João Silva', $html);
        $this->assertStringContainsString('joao@exemplo.com', $html);
        $this->assertStringContainsString('E-mail Marketing', $html);
        $this->assertStringContainsString('Lead Potencial', $html);
        $this->assertStringContainsString('Apenas Leads com E-mail', $html);
    }

    public function testDemandMetricsRepositoryLogsCustomerActivityAndDistinguishesUserTypes(): void
    {
        $repo = new PdoDemandMetricsRepository($this->pdo);

        // 1. Visitante Externo / Anônimo
        $eventAnon = new DemandEvent(
            productId: 'ALI-101',
            productTitle: 'Carregador Turbo',
            categorySlug: 'eletronicos',
            actionType: 'detail_click',
            customerId: null,
            customerName: null,
            customerEmail: null,
            userType: 'externo'
        );
        $repo->incrementMetric('ALI-101', 'Carregador Turbo', 'eletronicos', 'detail_click', $eventAnon);

        // 2. Cliente Logado
        $eventLogged = new DemandEvent(
            productId: 'ALI-101',
            productTitle: 'Carregador Turbo',
            categorySlug: 'eletronicos',
            actionType: 'buy_click',
            customerId: 10,
            customerName: 'Maria Oliveira',
            customerEmail: 'maria@loja.com',
            userType: 'cliente_logado'
        );
        $repo->incrementMetric('ALI-101', 'Carregador Turbo', 'eletronicos', 'buy_click', $eventLogged);

        $logs = $repo->getRecentActivityLogs(10);
        $this->assertCount(2, $logs);

        // Ordem decrescente de inserção (o mais recente primeiro)
        $this->assertSame('cliente_logado', $logs[0]['user_type']);
        $this->assertSame('buy_click', $logs[0]['action_type']);
        $this->assertSame('Maria Oliveira', $logs[0]['customer_name']);
        $this->assertSame('maria@loja.com', $logs[0]['customer_email']);

        $this->assertSame('externo', $logs[1]['user_type']);
        $this->assertSame('detail_click', $logs[1]['action_type']);
        $this->assertNull($logs[1]['customer_email']);
    }

    public function testDropshipFrontControllerIdentifiesLoggedInUserFromSession(): void
    {
        $repo = new PdoDemandMetricsRepository($this->pdo);
        $tracker = new SessionGuardedDemandTracker($repo);

        $sessionData = [
            'customer_id'    => 88,
            'customer_name'  => 'Carlos Santos',
            'customer_email' => 'carlos@empresa.com',
        ];

        $sessionMock = $this->createMock(SessionManagerInterface::class);
        $sessionMock->method('get')->willReturnCallback(function (string $key, mixed $default = null) use (&$sessionData) {
            return $sessionData[$key] ?? $default;
        });

        $catalogRepo = $this->createMock(\DomainSystem\Plugins\domain_drop\Contracts\DropshipCatalogRepositoryInterface::class);
        $frontController = new \DomainSystem\Plugins\domain_drop\Controllers\DropshipFrontController(
            catalogRepo: $catalogRepo,
            demandTracker: $tracker,
            session: $sessionMock
        );

        $request = new \DomainSystem\Core\Http\Request([], [], [], [], [], [
            'product_id' => 'ALI-888',
            'title'      => 'Teclado Mecânico RGB',
            'category'   => 'informatica',
            'action'     => 'detail_click',
        ]);

        $response = $frontController->trackDemand($request);
        $this->assertSame(200, $response->getStatusCode());

        $body = json_decode((string)$response->getContent(), true);
        $this->assertTrue($body['success']);
        $this->assertSame('cliente_logado', $body['user_type']);
        $this->assertSame('carlos@empresa.com', $body['identity']['email']);
        $this->assertSame('Carlos Santos', $body['identity']['name']);

        $recent = $repo->getRecentActivityLogs(1);
        $this->assertCount(1, $recent);
        $this->assertSame('cliente_logado', $recent[0]['user_type']);
        $this->assertSame('Carlos Santos', $recent[0]['customer_name']);
        $this->assertSame('carlos@empresa.com', $recent[0]['customer_email']);
    }

    public function testSingleProductEndpointAutomaticallyTracksViewDetailsForLoggedInCustomer(): void
    {
        $repo = new PdoDemandMetricsRepository($this->pdo);
        $tracker = new SessionGuardedDemandTracker($repo);

        $sessionData = [
            'customer_id'    => 1,
            'customer_name'  => 'MAURA PEREIRA DE AZEVEDO',
            'customer_email' => 'maurapereiradeazevedo@gmail.com',
        ];

        $sessionMock = $this->createMock(SessionManagerInterface::class);
        $sessionMock->method('get')->willReturnCallback(function (string $key, mixed $default = null) use (&$sessionData) {
            return $sessionData[$key] ?? $default;
        });

        $productDto = new \DomainSystem\Plugins\domain_drop\DTO\DropshipProductDto(
            aliexpressId: 'ALI-MAURA-999',
            title: 'Kit de Ferramentas Profissionais 25 em 1',
            slug: 'kit-ferramentas-25-em-1',
            costPriceUsd: 5.0,
            salePriceBrl: 49.90,
            mainImageUrl: 'https://example.com/img.jpg',
            category: 'ferramentas',
            id: 5432
        );

        $catalogRepo = $this->createMock(\DomainSystem\Plugins\domain_drop\Contracts\DropshipCatalogRepositoryInterface::class);
        $catalogRepo->method('findBySlug')->with('kit-ferramentas-25-em-1')->willReturn($productDto);

        $frontController = new \DomainSystem\Plugins\domain_drop\Controllers\DropshipFrontController(
            catalogRepo: $catalogRepo,
            demandTracker: $tracker,
            session: $sessionMock
        );

        $request = new \DomainSystem\Core\Http\Request();
        $response = $frontController->singleProduct($request, 'kit-ferramentas-25-em-1');

        $this->assertSame(200, $response->getStatusCode());

        // Verifica que o rastro foi gravado com a Maura logada!
        $recent = $repo->getRecentActivityLogs(1);
        $this->assertCount(1, $recent);
        $this->assertSame('ALI-MAURA-999', $recent[0]['product_id']);
        $this->assertSame('Kit de Ferramentas Profissionais 25 em 1', $recent[0]['product_title']);
        $this->assertSame('ferramentas', $recent[0]['category_slug']);
        $this->assertSame('view_details', $recent[0]['action_type']);
        $this->assertSame('cliente_logado', $recent[0]['user_type']);
        $this->assertSame('MAURA PEREIRA DE AZEVEDO', $recent[0]['customer_name']);
        $this->assertSame('maurapereiradeazevedo@gmail.com', $recent[0]['customer_email']);

        // Verifica que a métrica mensal foi incrementada
        $rankings = $repo->getMonthlyRankings(date('Y-m'), 1);
        $this->assertCount(1, $rankings);
        $this->assertSame(1, (int)$rankings[0]['detail_clicks']);
        $this->assertSame(1, (int)$rankings[0]['demand_score']);
    }

    public function testDailyAutomatedRotationPrioritizesHighDemandProducts(): void
    {
        $repo = new PdoDemandMetricsRepository($this->pdo);
        $repo->incrementMetric('ALI-CAMP-1', 'Carregador Turbo Top', 'eletronicos', 'buy_click');
        $repo->incrementMetric('ALI-CAMP-1', 'Carregador Turbo Top', 'eletronicos', 'conversion');

        $catalogRepo = $this->createMock(\DomainSystem\Plugins\domain_drop\Contracts\DropshipCatalogRepositoryInterface::class);
        $catalogRepo->method('save')->willReturn(10);

        $apiClient = $this->createMock(\DomainSystem\Plugins\domain_drop\Contracts\AliExpressApiClientInterface::class);
        $apiClient->method('searchProducts')->willReturn([]);

        $converter = $this->createMock(\DomainSystem\Plugins\domain_drop\Contracts\CurrencyConverterInterface::class);
        $converter->method('calculateSalePrice')->willReturn(89.90);

        $ai = $this->createMock(\DomainSystem\Plugins\domain_drop\Contracts\AiProductEnricherInterface::class);
        $ai->method('polishTitle')->willReturn('Carregador Turbo Top 65W');
        $ai->method('generateSalesCopy')->willReturn('Descrição persuasiva do carregador');

        $miningService = new \DomainSystem\Plugins\domain_drop\Services\ProductMiningService(
            apiClient: $apiClient,
            currencyConverter: $converter,
            aiEnricher: $ai,
            repository: $catalogRepo,
            demandMetricsRepo: $repo
        );

        $result = $miningService->runDailyAutomatedRotation(itemsPerCategory: 1);

        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $result['total_mined']);
        $this->assertArrayHasKey('priority_preserved', $result);
        $this->assertContains('Carregador Turbo Top', $result['priority_preserved']);
    }

    public function testFileDemandLogStorageAppendsAndReadsJsonLogsInMonthlyFolders(): void
    {
        $tempDir = sys_get_temp_dir() . '/test_demand_logs_' . uniqid();
        $storage = new \DomainSystem\Plugins\domain_drop\Services\FileDemandLogStorage($tempDir);

        $eventMaura = new DemandEvent(
            productId: 'ALI-MAURA-01',
            productTitle: 'Mini Teclado Sem Fio',
            categorySlug: 'informatica',
            actionType: 'detail_click',
            customerId: 1,
            customerName: 'MAURA PEREIRA DE AZEVEDO',
            customerEmail: 'maurapereiradeazevedo@gmail.com',
            userType: 'cliente_logado'
        );

        $eventAnon = new DemandEvent(
            productId: 'ALI-ANON-02',
            productTitle: 'Mouse Ergonômico',
            categorySlug: 'informatica',
            actionType: 'buy_click',
            customerId: null,
            customerName: null,
            customerEmail: null,
            userType: 'externo'
        );

        $storage->appendInteraction($eventMaura);
        $storage->appendInteraction($eventAnon);

        $filePath = $storage->getFilePathForPeriod();
        $this->assertFileExists($filePath);

        // Verifica padrão de pasta json_[nome_do_mes]_[ano]
        $this->assertMatchesRegularExpression('#/json_[a-z]+_\d{4}/interactions\.json$#', str_replace('\\', '/', $filePath));

        // Leitura em ordem cronológica decrescente
        $recent = $storage->getRecentInteractions(limit: 10);
        $this->assertCount(2, $recent);
        $this->assertSame('ALI-ANON-02', $recent[0]['product_id']);
        $this->assertSame('externo', $recent[0]['user_type']);

        $this->assertSame('ALI-MAURA-01', $recent[1]['product_id']);
        $this->assertSame('cliente_logado', $recent[1]['user_type']);
        $this->assertSame('maurapereiradeazevedo@gmail.com', $recent[1]['customer_email']);

        // Extração de leads únicos para e-mail marketing
        $leads = $storage->getLeadEmails();
        $this->assertCount(1, $leads);
        $this->assertSame('maurapereiradeazevedo@gmail.com', $leads[0]);

        // Limpeza do temp
        $this->deleteDir($tempDir);
    }

    public function testFileDemandLogStoragePurgesFoldersOlderThanTwoYears(): void
    {
        $tempDir = sys_get_temp_dir() . '/test_demand_logs_purge_' . uniqid();
        $storage = new \DomainSystem\Plugins\domain_drop\Services\FileDemandLogStorage($tempDir);

        $currentYear = (int)date('Y');
        $oldYear = $currentYear - 3; // Mais velho que 2 anos (deve ser expurgado)
        $validYear = $currentYear - 1; // 1 ano atrás (deve ser preservado)

        $oldFolder = "{$tempDir}/{$oldYear}/json_dezembro_{$oldYear}";
        $validFolder = "{$tempDir}/{$validYear}/json_dezembro_{$validYear}";

        @mkdir($oldFolder, 0755, true);
        @mkdir($validFolder, 0755, true);

        file_put_contents("{$oldFolder}/interactions.json", "{\"test\":1}\n");
        file_put_contents("{$validFolder}/interactions.json", "{\"test\":2}\n");

        $this->assertDirectoryExists($oldFolder);
        $this->assertDirectoryExists($validFolder);

        // Expurga arquivos com mais de 2 anos
        $purged = $storage->purgeExpiredLogs(2);
        $this->assertSame(1, $purged);

        // A pasta antiga sumiu!
        $this->assertDirectoryDoesNotExist("{$tempDir}/{$oldYear}");
        // A pasta recente foi preservada!
        $this->assertDirectoryExists($validFolder);

        $this->deleteDir($tempDir);
    }

    public function testPromoteSessionInteractionsInRepository(): void
    {
        $repo = new PdoDemandMetricsRepository($this->pdo);

        $eventAnon = new DemandEvent(
            productId: 'PROD-777',
            productTitle: 'Camisa Polo Premium',
            categorySlug: 'moda',
            actionType: 'buy_click',
            sessionId: 'sess_anon_99',
            customerId: null,
            customerName: null,
            customerEmail: null,
            userType: 'externo'
        );
        $repo->incrementMetric('PROD-777', 'Camisa Polo Premium', 'moda', 'buy_click', $eventAnon);

        $logsBefore = $repo->getRecentActivityLogs(10);
        $this->assertNotEmpty($logsBefore);
        $this->assertSame('externo', $logsBefore[0]['user_type']);
        $this->assertNull($logsBefore[0]['customer_id']);

        // Promove a sessão quando o cliente loga/cadastra
        $promotedCount = $repo->promoteSessionInteractions(
            'sess_anon_99',
            42,
            'Mônica Medeiros',
            'monica@teste.com'
        );

        $this->assertSame(1, $promotedCount);

        $logsAfter = $repo->getRecentActivityLogs(10);
        $this->assertSame('cliente_logado', $logsAfter[0]['user_type']);
        $this->assertSame(42, (int)$logsAfter[0]['customer_id']);
        $this->assertSame('Mônica Medeiros', $logsAfter[0]['customer_name']);
        $this->assertSame('monica@teste.com', $logsAfter[0]['customer_email']);
    }

    public function testPromoteSessionInteractionsInFileStorage(): void
    {
        $tempDir = sys_get_temp_dir() . '/test_demand_promote_' . uniqid();
        $storage = new \DomainSystem\Plugins\domain_drop\Services\FileDemandLogStorage($tempDir);

        $event1 = new DemandEvent(
            productId: 'PROD-888',
            productTitle: 'Relógio Smartwatch',
            categorySlug: 'tecnologia',
            actionType: 'view_details',
            sessionId: 'sess_promo_1',
            customerId: null,
            customerName: null,
            customerEmail: null,
            userType: 'externo'
        );
        $event2 = new DemandEvent(
            productId: 'PROD-888',
            productTitle: 'Relógio Smartwatch',
            categorySlug: 'tecnologia',
            actionType: 'buy_click',
            sessionId: 'sess_promo_1',
            customerId: null,
            customerName: null,
            customerEmail: null,
            userType: 'externo'
        );
        $eventOther = new DemandEvent(
            productId: 'PROD-999',
            productTitle: 'Outro Produto',
            categorySlug: 'casa',
            actionType: 'view_details',
            sessionId: 'sess_other',
            customerId: null,
            customerName: null,
            customerEmail: null,
            userType: 'externo'
        );

        $storage->appendInteraction($event1);
        $storage->appendInteraction($event2);
        $storage->appendInteraction($eventOther);

        // Promove apenas a sessão 'sess_promo_1'
        $promoted = $storage->promoteSessionInteractions(
            'sess_promo_1',
            105,
            'Comprador Promovido',
            'comprador@loja.com'
        );

        $this->assertSame(2, $promoted);

        $recent = $storage->getRecentInteractions(null, 10);
        $this->assertCount(3, $recent);

        // Verifica os registros da sessão promovida
        $promotedRecords = array_filter($recent, fn($r) => ($r['session_id'] ?? '') === 'sess_promo_1');
        $this->assertCount(2, $promotedRecords);
        foreach ($promotedRecords as $pr) {
            $this->assertSame('cliente_logado', $pr['user_type']);
            $this->assertSame(105, $pr['customer_id']);
            $this->assertSame('Comprador Promovido', $pr['customer_name']);
            $this->assertSame('comprador@loja.com', $pr['customer_email']);
        }

        // Sessão de outro usuário deve continuar inalterada
        $otherRecords = array_filter($recent, fn($r) => ($r['session_id'] ?? '') === 'sess_other');
        $this->assertCount(1, $otherRecords);
        $otherRec = reset($otherRecords);
        $this->assertSame('externo', $otherRec['user_type']);

        $this->deleteDir($tempDir);
    }

    public function testSessionGuardedDemandTrackerCoordinatesPromotion(): void
    {
        $repo = new PdoDemandMetricsRepository($this->pdo);
        $tempDir = sys_get_temp_dir() . '/test_tracker_promo_' . uniqid();
        $storage = new \DomainSystem\Plugins\domain_drop\Services\FileDemandLogStorage($tempDir);

        $tracker = new SessionGuardedDemandTracker(
            repository: $repo,
            logStorage: $storage
        );

        $event = new DemandEvent(
            productId: 'PROD-555',
            productTitle: 'Tênis Running',
            categorySlug: 'esportes',
            actionType: 'buy_click',
            sessionId: 'sess_coord_1',
            userType: 'externo'
        );
        $tracker->recordInteraction($event);

        $totalPromoted = $tracker->promoteSession(
            'sess_coord_1',
            7,
            'Corredor Silva',
            'corredor@atleta.com'
        );

        // Deve promover 1 no storage + 1 no repositório = 2
        $this->assertSame(2, $totalPromoted);

        $logs = $repo->getRecentActivityLogs(1);
        $this->assertSame('cliente_logado', $logs[0]['user_type']);
        $this->assertSame('corredor@atleta.com', $logs[0]['customer_email']);

        $this->deleteDir($tempDir);
    }

    private function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) return;
        $items = scandir($dir);
        if ($items === false) return;
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $p = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($p)) {
                $this->deleteDir($p);
            } else {
                @unlink($p);
            }
        }
        @rmdir($dir);
    }
}
