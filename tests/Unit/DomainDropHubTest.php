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
}
