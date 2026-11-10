<?php

declare(strict_types=1);

namespace Tests\Unit;

use DomainSystem\Plugins\domain_drop\Contracts\AliExpressApiClientInterface;
use DomainSystem\Plugins\domain_drop\DTO\DropshipProductDto;
use DomainSystem\Plugins\domain_drop\Repositories\PdoDropshipCatalogRepository;
use DomainSystem\Plugins\domain_drop\Services\AiProductEnricherService;
use DomainSystem\Plugins\domain_drop\Services\AliExpressApiClient;
use DomainSystem\Plugins\domain_drop\Services\CurrencyConverterService;
use DomainSystem\Plugins\domain_drop\Services\ProductMiningService;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Suíte de Testes Unitários do Hub DomainDrop (SOLID / Clean Architecture).
 */
class DomainDropHubTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Executa a migração do DomainDrop em memória SQLite
        $migration = require dirname(__DIR__, 2) . '/src/Plugins/domain_drop/migrations/2026_11_10_000001_create_domain_drop_tables.php';
        if (is_array($migration) && isset($migration['up'])) {
            ($migration['up'])($this->pdo);
        }
    }

    public function testAliExpressSignatureGenerationFollowsOfficialProtocol(): void
    {
        $client = new AliExpressApiClient('test_app_key', 'test_secret');

        $params = [
            'app_key'     => 'test_app_key',
            'timestamp'   => '1700000000000',
            'sign_method' => 'sha256',
            'method'      => 'aliexpress.ds.product.get',
            'product_id'  => '1005001234567890',
        ];

        $signature = $client->generateSignature('aliexpress.ds.product.get', $params);

        // Verifica ordenação lexicográfica e concatenação
        ksort($params);
        $expectedQuery = '';
        foreach ($params as $k => $v) {
            $expectedQuery .= $k . $v;
        }
        $expectedSignature = strtoupper(hash_hmac('sha256', $expectedQuery, 'test_secret'));

        $this->assertSame($expectedSignature, $signature);
        $this->assertMatchesRegularExpression('/^[A-F0-9]{64}$/', $signature);
    }

    public function testCurrencyConverterCalculatesMarkupAndEnforcesMinimumMargin(): void
    {
        $tmpCache = sys_get_temp_dir() . '/test_exchange_rate_' . uniqid() . '.json';
        file_put_contents($tmpCache, json_encode(['USD' => 6.00]));

        $converter = new CurrencyConverterService($tmpCache, 3600);

        // Taxa de câmbio
        $rate = $converter->getExchangeRate('USD', 'BRL');
        $this->assertEqualsWithDelta(6.00, $rate, 0.01);

        // Custo $10.00 USD -> BRL R$ 60,00
        $costBrl = 10.00 * $rate;
        $this->assertEqualsWithDelta(60.00, $costBrl, 0.01);

        // Preço de venda com 100% markup -> R$ 120,00 (lucro R$ 60,00 >= R$ 30,00 min)
        $salePrice = $converter->calculateSalePrice(10.00, 100.0, 30.0);
        $this->assertEqualsWithDelta(120.00, $salePrice, 0.01);

        // Preço de venda para produto barato ($1.00 USD = R$ 6.00 BRL)
        // Com markup 50% daria R$ 9,00 (lucro R$ 3,00), mas minMargin é R$ 25,00 -> venda deve ser no mínimo R$ 31,00
        $cheapSalePrice = $converter->calculateSalePrice(1.00, 50.0, 25.0);
        $this->assertEqualsWithDelta(31.00, $cheapSalePrice, 0.01);

        @unlink($tmpCache);
    }

    public function testAiProductEnricherCleansSpamAndFormatsCopy(): void
    {
        $enricher = new AiProductEnricherService();

        $spamTitle = "2026 Hot Sale Dropship 100% New Free Shipping Best Quality Earphone TWS Wireless Bluetooth";
        $cleaned = $enricher->polishTitle($spamTitle);

        $this->assertStringNotContainsStringIgnoringCase('Hot Sale', $cleaned);
        $this->assertStringNotContainsStringIgnoringCase('Dropship', $cleaned);
        $this->assertStringNotContainsStringIgnoringCase('Free Shipping', $cleaned);
        $this->assertStringContainsString('Earphone', $cleaned);

        $copy = $enricher->generateSalesCopy("Fones sem fio ergonômicos com bateria de longa duração.", ['Bluetooth' => '5.3']);
        $this->assertStringContainsString('Qualidade Comprovada', $copy);
        $this->assertStringContainsString('Envio com Rastreio', $copy);
        $this->assertStringContainsString('Especificações Técnicas', $copy);
    }

    public function testPdoDropshipCatalogRepositoryCrudOperations(): void
    {
        $repo = new PdoDropshipCatalogRepository($this->pdo);

        $product = new DropshipProductDto(
            aliexpressId: '100500999888777',
            title: 'Smartwatch Ultra Pro Max 2026',
            slug: 'smartwatch-ultra-pro-max-2026',
            costPriceUsd: 15.50,
            salePriceBrl: 189.90,
            mainImageUrl: 'https://ae01.alicdn.com/kf/test_smartwatch.jpg',
            images: ['https://ae01.alicdn.com/kf/img1.jpg'],
            category: 'Eletrônicos',
            description: 'Relógio inteligente de alta precisão',
            rating: 4.8,
            ordersCount: 1500,
            attributes: ['cor' => 'preto'],
            status: 'published'
        );

        $savedId = $repo->save($product);
        $this->assertGreaterThan(0, $savedId);

        // Busca por ID
        $found = $repo->findById($savedId);
        $this->assertNotNull($found);
        $this->assertSame('Smartwatch Ultra Pro Max 2026', $found->title);
        $this->assertEqualsWithDelta(189.90, $found->salePriceBrl, 0.01);
        $this->assertSame(4.8, $found->rating);

        // Busca por AliExpress ID
        $foundByAe = $repo->findByAliExpressId('100500999888777');
        $this->assertNotNull($foundByAe);
        $this->assertSame($savedId, $foundByAe->id);

        // Listagem & Contagem
        $list = $repo->list(['category' => 'Eletrônicos']);
        $this->assertCount(1, $list);
        $this->assertSame(1, $repo->count(['category' => 'Eletrônicos']));

        $categories = $repo->getCategories();
        $this->assertContains('Eletrônicos', $categories);

        // Deletar
        $this->assertTrue($repo->delete($savedId));
        $this->assertNull($repo->findById($savedId));
        $this->assertSame(0, $repo->count());
    }

    public function testProductMiningServiceWithMockApiClient(): void
    {
        $mockApi = $this->createMock(AliExpressApiClientInterface::class);
        $mockApi->method('searchProducts')->willReturn([
            [
                'product_id' => '100500444555666',
                'product_title' => 'Hot Sale New Portable Mini Vacuum Cleaner Cordless',
                'target_sale_price' => '12.00',
                'product_main_image_url' => 'https://ae01.alicdn.com/kf/cleaner.jpg',
                'evaluate_rate' => '4.9',
                'volume' => 3200,
            ]
        ]);

        $mockApi->method('getProductDetails')->willReturn([
            'result' => [
                'product_id' => '100500444555666',
                'product_title' => 'Hot Sale New Portable Mini Vacuum Cleaner Cordless',
                'target_sale_price' => '12.00',
                'product_main_image_url' => 'https://ae01.alicdn.com/kf/cleaner.jpg',
                'evaluate_rate' => '4.9',
                'volume' => 3200,
            ]
        ]);

        $tmpCache = sys_get_temp_dir() . '/test_exchange_' . uniqid() . '.json';
        file_put_contents($tmpCache, json_encode(['USD' => 5.50]));
        $converter = new CurrencyConverterService($tmpCache);
        $enricher = new AiProductEnricherService();
        $repo = new PdoDropshipCatalogRepository($this->pdo);

        $miningService = new ProductMiningService(
            $mockApi,
            $converter,
            $enricher,
            $repo
        );

        // 1. Mineração em lote
        $mined = $miningService->mineTrendingProducts('Casa Inteligente', 1, true);
        $this->assertCount(1, $mined);

        $product = $mined[0];
        $this->assertSame('100500444555666', $product->aliexpressId);
        $this->assertStringNotContainsStringIgnoringCase('Hot Sale', $product->title);
        $this->assertEqualsWithDelta(12.00, $product->costPriceUsd, 0.01);
        $this->assertNotNull($product->id);

        // 2. Importação via URL completa
        $imported = $miningService->importSingleProduct('https://pt.aliexpress.com/item/100500444555666.html?spm=a2g0o.home');
        $this->assertSame($product->id, $imported->id);

        @unlink($tmpCache);
    }

    public function testProductMiningServiceParsesOfficialIopMultimediaGalleryAndSkus(): void
    {
        $mockApi = $this->createMock(AliExpressApiClientInterface::class);
        $mockApi->method('getProductDetails')->willReturn([
            'result' => [
                'ae_item_base_info_dto' => [
                    'product_id' => '1005013345247302',
                    'subject' => 'Pulseira Homem de Ferro para Apple Watch 49mm 45mm Silicone Ultra',
                    'detail' => '<p>Descrição rica com detalhes do produto</p>',
                    'avg_evaluation_rating' => '4.9',
                ],
                'ae_multimedia_info_dto' => [
                    'image_urls' => 'https://ae-pic-a1.aliexpress-media.com/kf/S034dd953aa8d4e5c9744115de0efc991x.jpg;https://ae-pic-a1.aliexpress-media.com/kf/Sf1fdef1c56604b1bbd4ba590e1f0bd72g.jpg;https://ae-pic-a1.aliexpress-media.com/kf/Sdffac8fe8ce64e7594b0017a48d2dcf6C.jpg',
                ],
                'ae_item_sku_info_dtos' => [
                    'ae_item_sku_info_d_t_o' => [
                        [
                            'sku_price' => '5.50',
                            'sku_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S6d426a8dcf3b480bb7d1e83ab6666db10/208x824.png',
                        ]
                    ]
                ],
                'target_sale_price' => '5.50',
            ]
        ]);

        $tmpCache = sys_get_temp_dir() . '/test_exchange_' . uniqid() . '.json';
        file_put_contents($tmpCache, json_encode(['USD' => 5.25]));
        $converter = new CurrencyConverterService($tmpCache);
        $enricher = new AiProductEnricherService();
        $repo = new PdoDropshipCatalogRepository($this->pdo);

        $miningService = new ProductMiningService($mockApi, $converter, $enricher, $repo);
        $dto = $miningService->importSingleProduct('1005013345247302');

        $this->assertSame('1005013345247302', $dto->aliexpressId);
        $this->assertStringContainsString('Pulseira Homem De Ferro', $dto->title);
        $this->assertSame('https://ae-pic-a1.aliexpress-media.com/kf/S034dd953aa8d4e5c9744115de0efc991x.jpg', $dto->mainImageUrl);
        // Galeria deve conter as 3 imagens do multimedia + 1 do SKU
        $this->assertCount(4, $dto->images);
        $this->assertContains('https://ae-pic-a1.aliexpress-media.com/kf/Sf1fdef1c56604b1bbd4ba590e1f0bd72g.jpg', $dto->images);
        $this->assertContains('https://ae-pic-a1.aliexpress-media.com/kf/S6d426a8dcf3b480bb7d1e83ab6666db10/208x824.png', $dto->images);

        @unlink($tmpCache);
    }

    public function testDropshipProductPurchasableAdapterIntegratesWithCommerce(): void
    {
        $product = new DropshipProductDto(
            aliexpressId: '100500123987',
            title: 'Lâmpada Inteligente RGB Wi-Fi',
            slug: 'lampada-inteligente-rgb-wifi',
            costPriceUsd: 5.00,
            salePriceBrl: 49.90,
            mainImageUrl: 'https://ae01.alicdn.com/lampada.jpg',
            images: [],
            category: 'Casa Inteligente',
            description: 'Controle de iluminação por voz e app.',
            rating: 4.9,
            ordersCount: 850,
            attributes: [],
            status: 'published',
            id: 42
        );

        $adapter = new \DomainSystem\Plugins\domain_drop\Adapters\DropshipProductPurchasableAdapter($product);

        $this->assertInstanceOf(\DomainSystem\Plugins\commerce\Contracts\PurchasableItemInterface::class, $adapter);
        $this->assertSame(42, $adapter->getId());
        $this->assertSame('dropship_product', $adapter->getItemType());
        $this->assertSame('Lâmpada Inteligente RGB Wi-Fi', $adapter->getTitle());
        $this->assertEqualsWithDelta(49.90, $adapter->getPrice(), 0.01);
        $this->assertTrue($adapter->isAvailable());
        $this->assertSame('https://ae01.alicdn.com/lampada.jpg', $adapter->getThumbnailUrl());
    }

    public function testPdoDropshipOrderRepositoryLifecycleAndFulfillment(): void
    {
        $repo = new \DomainSystem\Plugins\domain_drop\Repositories\PdoDropshipOrderRepository($this->pdo);

        $orderData = [
            'order_ref'          => 'ORD_DROP_TEST_2026',
            'customer_name'      => 'João da Silva',
            'customer_email'     => 'joao@exemplo.com',
            'customer_document'  => '12345678901',
            'shipping_address'    => ['cidade' => 'São Paulo', 'uf' => 'SP', 'cep' => '01001-000'],
            'status'             => 'paid',
            'total_paid_brl'     => 149.90,
        ];

        $orderId = $repo->create($orderData);
        $this->assertGreaterThan(0, $orderId);

        // Busca por referência
        $found = $repo->findByOrderRef('ORD_DROP_TEST_2026');
        $this->assertNotNull($found);
        $this->assertSame('João da Silva', $found['customer_name']);
        $this->assertSame('paid', $found['status']);
        $this->assertEqualsWithDelta(149.90, (float)$found['total_paid_brl'], 0.01);

        // Atualização de Fulfillment (AliExpress Order ID e Código de Rastreamento)
        $updated = $repo->updateFulfillment('ORD_DROP_TEST_2026', 'shipped', 'AE_ORDER_88776655', 'NL123456789BR');
        $this->assertTrue($updated);

        $shipped = $repo->findByOrderRef('ORD_DROP_TEST_2026');
        $this->assertNotNull($shipped);
        $this->assertSame('shipped', $shipped['status']);
        $this->assertSame('AE_ORDER_88776655', $shipped['aliexpress_order_id']);
        $this->assertSame('NL123456789BR', $shipped['tracking_code']);

        // Listagem com filtro
        $list = $repo->list(['status' => 'shipped']);
        $this->assertCount(1, $list);
        $this->assertSame('ORD_DROP_TEST_2026', $list[0]['order_ref']);
    }

    public function testDropshipShowcaseBlockAndProviderForFlexTheme(): void
    {
        $repo = new PdoDropshipCatalogRepository($this->pdo);

        // Salva um produto para a vitrine
        $product = new DropshipProductDto(
            aliexpressId: '100500777111',
            title: 'Caixa de Som Portátil Bluetooth Pro',
            slug: 'caixa-som-bluetooth-pro',
            costPriceUsd: 8.00,
            salePriceBrl: 89.90,
            mainImageUrl: 'https://ae01.alicdn.com/caixa.jpg',
            images: [],
            category: 'Eletrônicos',
            description: 'Som estéreo potente e à prova d água.',
            rating: 4.9,
            ordersCount: 4200,
            attributes: [],
            status: 'published'
        );
        $savedId = $repo->save($product);

        $registry = new \DomainSystem\Core\Theme\Parts\Blocks\BlockRegistry();
        $provider = new \DomainSystem\Plugins\domain_drop\Blocks\DropshipBlockProvider($repo);
        $provider->registerBlocks($registry);

        $this->assertTrue($registry->has('dropship_showcase'));
        $block = $registry->get('dropship_showcase');
        $this->assertNotNull($block);
        $this->assertSame('dropship_showcase', $block->getType());
        $this->assertSame('ecommerce', $block->getCategory());

        // Renderização com produtos
        $html = $block->render([
            'title'       => 'Ofertas Relâmpago Dropship',
            'columns'     => 4,
            'limit'       => 8,
            'button_text' => 'Quero Comprar',
        ]);

        $this->assertStringContainsString('Ofertas Relâmpago Dropship', $html);
        $this->assertStringContainsString('Caixa de Som Portátil Bluetooth Pro', $html);
        $this->assertStringContainsString('R$ 89,90', $html);
        $this->assertStringContainsString('checkout/produto/' . $savedId, $html);
        $this->assertStringContainsString('Quero Comprar', $html);
    }

    public function testDropshipFrontControllerStorefrontAndSingleProduct(): void
    {
        $repo = new PdoDropshipCatalogRepository($this->pdo);

        $product = new DropshipProductDto(
            aliexpressId: '100500888333',
            title: 'Mochila Impermeável Antifurto USB',
            slug: 'mochila-impermeavel-antifurto-usb',
            costPriceUsd: 14.00,
            salePriceBrl: 149.90,
            mainImageUrl: 'https://ae01.alicdn.com/mochila.jpg',
            images: ['https://ae01.alicdn.com/mochila.jpg', 'https://ae01.alicdn.com/mochila_side.jpg'],
            category: 'Acessórios',
            description: '<p>Mochila reforçada com entrada USB externa.</p>',
            rating: 4.8,
            ordersCount: 2300,
            attributes: [],
            status: 'published'
        );
        $savedId = $repo->save($product);

        $controller = new \DomainSystem\Plugins\domain_drop\Controllers\DropshipFrontController($repo);

        // 1. Vitrine da Loja (/loja)
        $requestStore = new \DomainSystem\Core\Http\Request(['cat' => 'Acessórios']);
        $responseStore = $controller->storefront($requestStore);

        $this->assertSame(200, $responseStore->getStatusCode());
        $bodyStore = $responseStore->getContent();
        $this->assertStringContainsString('Loja Oficial', $bodyStore);
        $this->assertStringContainsString('Mochila Impermeável Antifurto USB', $bodyStore);
        $this->assertStringContainsString('Acessórios', $bodyStore);
        $this->assertStringContainsString('R$ 149,90', $bodyStore);

        // 2. Página Dedicada do Produto (/produto/mochila-impermeavel-antifurto-usb)
        $requestProd = new \DomainSystem\Core\Http\Request();
        $responseProd = $controller->singleProduct($requestProd, 'mochila-impermeavel-antifurto-usb');

        $this->assertSame(200, $responseProd->getStatusCode());
        $bodyProd = $responseProd->getContent();
        $this->assertStringContainsString('Mochila Impermeável Antifurto USB', $bodyProd);
        $this->assertStringContainsString('Comprar Agora', $bodyProd);
        $this->assertStringContainsString('checkout/produto/' . $savedId, $bodyProd);
        $this->assertStringContainsString('Envio Internacional com Rastreio', $bodyProd);
    }

    public function testDropshipAdminControllerIndexRendersCatalogAndProductsCleanly(): void
    {
        $repo = new PdoDropshipCatalogRepository($this->pdo);

        // Adiciona um produto no repositório
        $product = new DropshipProductDto(
            aliexpressId: '10050011223344',
            title: 'Smartwatch Pro 2026 Amoled',
            slug: 'smartwatch-pro-2026-amoled',
            costPriceUsd: 20.00,
            salePriceBrl: 199.90,
            mainImageUrl: 'https://ae01.alicdn.com/watch.jpg',
            images: [],
            category: 'Eletrônicos',
            description: 'Relógio topo de linha.',
            rating: 4.9,
            ordersCount: 3000,
            attributes: [],
            status: 'published'
        );
        $repo->save($product);

        $mockApi = $this->createMock(AliExpressApiClientInterface::class);
        $tmpCache = sys_get_temp_dir() . '/test_admin_cache_' . uniqid() . '.json';
        file_put_contents($tmpCache, json_encode(['USD' => 5.50]));
        $converter = new CurrencyConverterService($tmpCache);
        $enricher = new AiProductEnricherService();
        $miningService = new ProductMiningService($mockApi, $converter, $enricher, $repo);

        $session = $this->createMock(\DomainSystem\Core\Contracts\SessionManagerInterface::class);
        $session->method('getCsrfToken')->willReturn('csrf_token_test');
        $session->method('getFlash')->willReturn(['type' => 'success', 'msg' => 'Bem-vindo ao DomainDrop!']);

        $tmpDir = sys_get_temp_dir() . '/ds_test_admin_' . uniqid();
        mkdir($tmpDir . '/config/plugins', 0777, true);

        $controller = new \DomainSystem\Plugins\domain_drop\Controllers\DropshipAdminController(
            catalogRepo: $repo,
            miningService: $miningService,
            currencyConverter: $converter,
            session: $session,
            orderRepo: new \DomainSystem\Plugins\domain_drop\Repositories\PdoDropshipOrderRepository($this->pdo),
            basePath: $tmpDir
        );

        $request = new \DomainSystem\Core\Http\Request();
        $response = $controller->index($request);

        $this->assertSame(200, $response->getStatusCode());
        $html = $response->getContent();
        $this->assertStringContainsString('DomainDrop Hub', $html);
        $this->assertStringContainsString('Smartwatch Pro 2026 Amoled', $html);
        $this->assertStringContainsString('R$ 199,90', $html);
        $this->assertStringContainsString('10050011223344', $html);

        // Limpeza
        @unlink($tmpCache);
        @unlink($tmpDir . '/config/plugins/domain_drop.json');
        @rmdir($tmpDir . '/config/plugins');
        @rmdir($tmpDir . '/config');
        @rmdir($tmpDir);
    }

    public function testDropshipAdminControllerMiningActionPopulatesCatalog(): void
    {
        $repo = new PdoDropshipCatalogRepository($this->pdo);
        $this->assertSame(0, $repo->count());

        $mockApi = $this->createMock(AliExpressApiClientInterface::class);
        $mockApi->method('searchProducts')->willReturn([]); // Força uso do fallback curado

        $tmpCache = sys_get_temp_dir() . '/test_admin_mine_' . uniqid() . '.json';
        file_put_contents($tmpCache, json_encode(['USD' => 5.50]));
        $converter = new CurrencyConverterService($tmpCache);
        $enricher = new AiProductEnricherService();
        $miningService = new ProductMiningService($mockApi, $converter, $enricher, $repo);

        $session = $this->createMock(\DomainSystem\Core\Contracts\SessionManagerInterface::class);
        $session->method('getCsrfToken')->willReturn('valid_csrf');

        $tmpDir = sys_get_temp_dir() . '/ds_test_mine_' . uniqid();
        mkdir($tmpDir . '/config/plugins', 0777, true);

        $controller = new \DomainSystem\Plugins\domain_drop\Controllers\DropshipAdminController(
            catalogRepo: $repo,
            miningService: $miningService,
            currencyConverter: $converter,
            session: $session,
            orderRepo: null,
            basePath: $tmpDir
        );

        $request = new \DomainSystem\Core\Http\Request([], [
            'csrf_token' => 'valid_csrf',
            'category'   => 'Eletrônicos',
            'limit'      => '5',
        ]);

        $response = $controller->mine($request);
        $this->assertSame(302, $response->getStatusCode());

        // Verifica que os produtos foram minerados e persistidos no banco
        $this->assertGreaterThan(0, $repo->count());

        @unlink($tmpCache);
        @rmdir($tmpDir . '/config/plugins');
        @rmdir($tmpDir . '/config');
        @rmdir($tmpDir);
    }

    public function testDropshipAdminControllerSettingsAndOrders(): void
    {
        $repo = new PdoDropshipCatalogRepository($this->pdo);
        $orderRepo = new \DomainSystem\Plugins\domain_drop\Repositories\PdoDropshipOrderRepository($this->pdo);

        $mockApi = $this->createMock(AliExpressApiClientInterface::class);
        $tmpCache = sys_get_temp_dir() . '/test_admin_settings_' . uniqid() . '.json';
        file_put_contents($tmpCache, json_encode(['USD' => 5.50]));
        $converter = new CurrencyConverterService($tmpCache);
        $enricher = new AiProductEnricherService();
        $miningService = new ProductMiningService($mockApi, $converter, $enricher, $repo);

        $session = $this->createMock(\DomainSystem\Core\Contracts\SessionManagerInterface::class);
        $session->method('getCsrfToken')->willReturn('valid_csrf');

        $tmpDir = sys_get_temp_dir() . '/ds_test_settings_' . uniqid();
        mkdir($tmpDir . '/config/plugins', 0777, true);

        $controller = new \DomainSystem\Plugins\domain_drop\Controllers\DropshipAdminController(
            catalogRepo: $repo,
            miningService: $miningService,
            currencyConverter: $converter,
            session: $session,
            orderRepo: $orderRepo,
            basePath: $tmpDir
        );

        // 1. Settings view
        $settingsResp = $controller->settings(new \DomainSystem\Core\Http\Request());
        $this->assertSame(200, $settingsResp->getStatusCode());
        $this->assertStringContainsString('Configurações da API', $settingsResp->getContent());

        // 2. Save settings
        $saveReq = new \DomainSystem\Core\Http\Request([], [
            'csrf_token'             => 'valid_csrf',
            'app_key'                => '558628_test',
            'app_secret'             => 'secret_12345',
            'access_token'           => 'token_oauth_xyz',
            'default_markup_percent' => '120.0',
            'min_profit_margin_brl'  => '45.0',
        ]);
        $saveResp = $controller->saveSettings($saveReq);
        $this->assertSame(302, $saveResp->getStatusCode());

        $savedConfig = json_decode((string)file_get_contents($tmpDir . '/config/plugins/domain_drop.json'), true);
        $this->assertSame('558628_test', $savedConfig['app_key']);
        $this->assertSame('secret_12345', $savedConfig['app_secret']);
        $this->assertSame('token_oauth_xyz', $savedConfig['access_token']);
        $this->assertEqualsWithDelta(120.0, (float)$savedConfig['default_markup_percent'], 0.01);
        $this->assertEqualsWithDelta(45.0, (float)$savedConfig['min_profit_margin_brl'], 0.01);

        // 3. Orders view
        $ordersResp = $controller->orders(new \DomainSystem\Core\Http\Request());
        $this->assertSame(200, $ordersResp->getStatusCode());
        $this->assertStringContainsString('Pedidos Dropshipping', $ordersResp->getContent());

        @unlink($tmpCache);
        @unlink($tmpDir . '/config/plugins/domain_drop.json');
        @rmdir($tmpDir . '/config/plugins');
        @rmdir($tmpDir . '/config');
        @rmdir($tmpDir);
    }

    public function testDropshipAdminControllerUpdatePriceAndRepositoryPriceUpdate(): void
    {
        $repo = new PdoDropshipCatalogRepository($this->pdo);

        $product = new DropshipProductDto(
            aliexpressId: '100500888999111',
            title: 'Alicate Desencapador de Fios Automático',
            slug: 'alicate-desencapador-de-fios-automatico',
            costPriceUsd: 12.00,
            salePriceBrl: 134.15,
            mainImageUrl: 'https://ae01.alicdn.com/kf/desencapador.jpg',
            images: ['https://ae01.alicdn.com/kf/desencapador.jpg'],
            category: 'Ferramentas',
            description: 'Alicate multifuncional para desencapar fios',
            rating: 4.9,
            ordersCount: 850,
            attributes: [],
            status: 'published'
        );
        $savedId = $repo->save($product);

        // 1. Testa método do repositório diretamente
        $this->assertTrue($repo->updatePrice($savedId, 79.90, 8.50));
        $updated = $repo->findById($savedId);
        $this->assertNotNull($updated);
        $this->assertEqualsWithDelta(79.90, $updated->salePriceBrl, 0.01);
        $this->assertEqualsWithDelta(8.50, $updated->costPriceUsd, 0.01);

        // 2. Testa endpoint do Controller via requisição AJAX/JSON
        $mockApi = $this->createMock(AliExpressApiClientInterface::class);
        $tmpCache = sys_get_temp_dir() . '/test_admin_price_' . uniqid() . '.json';
        file_put_contents($tmpCache, json_encode(['USD' => 5.50]));
        $converter = new CurrencyConverterService($tmpCache);
        $enricher = new AiProductEnricherService();
        $miningService = new ProductMiningService($mockApi, $converter, $enricher, $repo);

        $session = $this->createMock(\DomainSystem\Core\Contracts\SessionManagerInterface::class);
        $session->method('getCsrfToken')->willReturn('csrf_token_valid');

        $tmpDir = sys_get_temp_dir() . '/ds_test_price_' . uniqid();
        mkdir($tmpDir . '/config/plugins', 0777, true);

        $controller = new \DomainSystem\Plugins\domain_drop\Controllers\DropshipAdminController(
            catalogRepo: $repo,
            miningService: $miningService,
            currencyConverter: $converter,
            session: $session,
            orderRepo: new \DomainSystem\Plugins\domain_drop\Repositories\PdoDropshipOrderRepository($this->pdo),
            basePath: $tmpDir
        );

        // Teste com Custo informado em BRL (R$ 46,78) e Venda em BRL (R$ 79,90)
        $req = new \DomainSystem\Core\Http\Request(
            query: [],
            request: [
                'csrf_token'      => 'csrf_token_valid',
                'cost_price_brl'  => '46.78',
                'sale_price_brl'  => '79.90',
                'ajax'            => '1',
            ],
            server: [
                'HTTP_ACCEPT' => 'application/json',
            ]
        );

        $resp = $controller->updatePrice($req, (string)$savedId);
        $this->assertSame(200, $resp->getStatusCode());

        $payload = json_decode((string)$resp->getContent(), true);
        $this->assertTrue($payload['success']);
        $this->assertEqualsWithDelta(79.90, (float)$payload['sale_price_brl'], 0.01);
        $this->assertEqualsWithDelta(46.78, (float)$payload['cost_price_brl'], 0.1);
        $this->assertGreaterThan(0, (float)$payload['profit_brl']);
        $this->assertGreaterThan(0, (float)$payload['margin_percent']);

        // Verifica no banco
        $reloaded = $repo->findById($savedId);
        $this->assertEqualsWithDelta(79.90, $reloaded->salePriceBrl, 0.01);

        @unlink($tmpCache);
        @unlink($tmpDir . '/config/plugins/domain_drop.json');
        @rmdir($tmpDir . '/config/plugins');
        @rmdir($tmpDir . '/config');
        @rmdir($tmpDir);
    }
}
