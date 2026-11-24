<?php

declare(strict_types=1);

namespace Tests\Unit;

use DomainSystem\Plugins\domain_drop\Adapters\DropshipProductPurchasableAdapter;
use DomainSystem\Plugins\domain_drop\DTO\DropshipProductDto;
use DomainSystem\Plugins\domain_drop\DTO\ProductVariantDto;
use PHPUnit\Framework\TestCase;

/**
 * Suíte de Testes Unitários de Variações de Produtos / Matriz de SKUs (SOLID / Clean Architecture).
 */
class DomainDropProductVariantsTest extends TestCase
{
    private DropshipProductDto $productWithVariants;
    private DropshipProductDto $simpleProduct;

    protected function setUp(): void
    {
        if (!is_dir(dirname(__DIR__, 2) . '/src/Plugins/domain_drop')) { $this->markTestSkipped('Plugin agregado externo [domain_drop] não está presente no checkout.'); return; }
        parent::setUp();

        $variantsData = [
            [
                'sku_id'         => 'SKU-CASE-BLK-IP15',
                'sku_attr'       => '14:200000182;5:100014064',
                'options'        => ['Cor' => 'Preto', 'Modelo' => 'iPhone 15'],
                'price_brl'      => 49.90,
                'cost_price_usd' => 5.00,
                'image_url'      => 'https://example.com/case-black.jpg',
                'stock'          => 120,
            ],
            [
                'sku_id'         => 'SKU-CASE-RED-IP15',
                'sku_attr'       => '14:200000183;5:100014064',
                'options'        => ['Cor' => 'Vermelho Vinho', 'Modelo' => 'iPhone 15'],
                'price_brl'      => 54.90,
                'cost_price_usd' => 5.50,
                'image_url'      => 'https://example.com/case-wine-red.jpg',
                'stock'          => 45,
            ],
            [
                'sku_id'         => 'SKU-CASE-BLK-IP14',
                'sku_attr'       => '14:200000182;5:100014063',
                'options'        => ['Cor' => 'Preto', 'Modelo' => 'iPhone 14'],
                'price_brl'      => 45.90,
                'cost_price_usd' => 4.50,
                'image_url'      => 'https://example.com/case-black.jpg',
                'stock'          => 0, // Sem estoque
            ],
        ];

        $this->productWithVariants = new DropshipProductDto(
            aliexpressId: '1005009988776655',
            title: 'Capa Protetora Anti-Impacto Magnética',
            slug: 'capa-protetora-anti-impacto-magnetica-6655',
            costPriceUsd: 5.00,
            salePriceBrl: 49.90,
            mainImageUrl: 'https://example.com/case-main.jpg',
            images: ['https://example.com/case-main.jpg', 'https://example.com/case-black.jpg', 'https://example.com/case-wine-red.jpg'],
            category: 'Acessórios para Celular',
            description: 'Capa premium com proteção militar contra quedas.',
            rating: 4.9,
            ordersCount: 8500,
            attributes: [
                'origem_brasil' => true,
                'variants'      => $variantsData,
            ],
            status: 'published',
            id: 88
        );

        $this->simpleProduct = new DropshipProductDto(
            aliexpressId: '1005001122334455',
            title: 'Alicate Crimpador Universal',
            slug: 'alicate-crimpador-universal-4455',
            costPriceUsd: 12.00,
            salePriceBrl: 89.90,
            mainImageUrl: 'https://example.com/tool-main.jpg',
            category: 'Ferramentas & Utilidades',
            attributes: [
                'origem_brasil' => false,
            ],
            status: 'published',
            id: 12
        );
    }

    public function testProductVariantDtoStructureAndFormatting(): void
    {
        $variant = new ProductVariantDto(
            skuId: 'SKU-WATCH-BLK-LEATHER',
            skuAttr: '14:200000182;10:10001234',
            options: ['Cor da Caixa' => 'Preto Espacial', 'Pulseira' => 'Couro Legítimo'],
            priceBrl: 199.90,
            costPriceUsd: 25.00,
            imageUrl: 'https://example.com/watch-leather.jpg',
            stock: 35
        );

        $this->assertSame('SKU-WATCH-BLK-LEATHER', $variant->skuId);
        $this->assertSame('Cor da Caixa: Preto Espacial • Pulseira: Couro Legítimo', $variant->getFormattedOptions());
        $this->assertSame('Preto Espacial / Couro Legítimo', $variant->getShortLabel());
        $this->assertTrue($variant->matchesOptions(['Cor da Caixa' => 'Preto Espacial', 'Pulseira' => 'Couro Legítimo']));
        $this->assertFalse($variant->matchesOptions(['Cor da Caixa' => 'Prata']));
        $this->assertTrue($variant->isAvailable());

        $outOfStockVariant = new ProductVariantDto(
            skuId: 'SKU-WATCH-OOS',
            skuAttr: '',
            options: ['Cor' => 'Dourado'],
            priceBrl: 199.90,
            costPriceUsd: 25.00,
            stock: 0
        );
        $this->assertFalse($outOfStockVariant->isAvailable());
    }

    public function testDropshipProductDtoDetectsVariantsAndExtractsAxes(): void
    {
        $this->assertTrue($this->productWithVariants->hasVariants());
        $this->assertFalse($this->simpleProduct->hasVariants());

        $variants = $this->productWithVariants->getVariants();
        $this->assertCount(3, $variants);
        $this->assertInstanceOf(ProductVariantDto::class, $variants[0]);

        $axes = $this->productWithVariants->getVariationAxes();
        $this->assertArrayHasKey('Cor', $axes);
        $this->assertArrayHasKey('Modelo', $axes);
        $this->assertEquals(['Preto', 'Vermelho Vinho'], $axes['Cor']);
        $this->assertEquals(['iPhone 15', 'iPhone 14'], $axes['Modelo']);

        // Preto está disponível (iPhone 15 tem stock=120)
        $this->assertTrue($this->productWithVariants->isOptionAvailable('Cor', 'Preto'));
        // Vermelho Vinho está disponível (iPhone 15 tem stock=45)
        $this->assertTrue($this->productWithVariants->isOptionAvailable('Cor', 'Vermelho Vinho'));
        // iPhone 15 está disponível (tem stock)
        $this->assertTrue($this->productWithVariants->isOptionAvailable('Modelo', 'iPhone 15'));
        // iPhone 14 só tem SKU Preto com stock=0, então não deve estar disponível
        $this->assertFalse($this->productWithVariants->isOptionAvailable('Modelo', 'iPhone 14'));
        // Opção inexistente
        $this->assertFalse($this->productWithVariants->isOptionAvailable('Cor', 'Azul Neon'));
    }

    public function testDropshipProductDtoFindsVariantBySkuAndOptions(): void
    {
        $skuRed = $this->productWithVariants->findVariantBySku('SKU-CASE-RED-IP15');
        $this->assertNotNull($skuRed);
        $this->assertSame('Vermelho Vinho', $skuRed->options['Cor']);
        $this->assertSame(54.90, $skuRed->priceBrl);

        $skuByOptions = $this->productWithVariants->findVariantByOptions([
            'Cor'    => 'Vermelho Vinho',
            'Modelo' => 'iPhone 15',
        ]);
        $this->assertNotNull($skuByOptions);
        $this->assertSame('SKU-CASE-RED-IP15', $skuByOptions->skuId);
        $this->assertSame('https://example.com/case-wine-red.jpg', $skuByOptions->imageUrl);

        $notFound = $this->productWithVariants->findVariantByOptions([
            'Cor'    => 'Azul Royal',
            'Modelo' => 'iPhone 15',
        ]);
        $this->assertNull($notFound);
    }

    public function testPurchasableAdapterWithoutVariantReturnsBaseProductDetails(): void
    {
        $adapter = new DropshipProductPurchasableAdapter($this->productWithVariants);

        $this->assertSame(88, $adapter->getId());
        $this->assertSame('dropship_product', $adapter->getItemType());
        $this->assertSame('Capa Protetora Anti-Impacto Magnética', $adapter->getTitle());
        $this->assertSame(49.90, $adapter->getPrice());
        $this->assertSame('https://example.com/case-main.jpg', $adapter->getThumbnailUrl());
        $this->assertTrue($adapter->isAvailable());
        $this->assertNull($adapter->getVariant());
    }

    public function testPurchasableAdapterWithWineRedVariantEnrichesTitlePriceAndImage(): void
    {
        // Cliente escolheu a Capa na cor Vermelho Vinho para iPhone 15
        $adapter = new DropshipProductPurchasableAdapter(
            $this->productWithVariants,
            'SKU-CASE-RED-IP15'
        );

        $this->assertSame(88, $adapter->getId());
        $this->assertSame(
            'Capa Protetora Anti-Impacto Magnética — Vermelho Vinho / iPhone 15',
            $adapter->getTitle()
        );
        $this->assertSame(54.90, $adapter->getPrice());
        $this->assertSame('https://example.com/case-wine-red.jpg', $adapter->getThumbnailUrl());
        $this->assertTrue($adapter->isAvailable());
        $this->assertNotNull($adapter->getVariant());
        $this->assertSame('SKU-CASE-RED-IP15', $adapter->getVariant()->skuId);
    }

    public function testPurchasableAdapterWithOutOfStockVariantReportsUnavailable(): void
    {
        // Variante iPhone 14 Preto está com estoque 0
        $adapter = new DropshipProductPurchasableAdapter(
            $this->productWithVariants,
            'SKU-CASE-BLK-IP14'
        );

        $this->assertFalse($adapter->isAvailable());
        $this->assertSame(45.90, $adapter->getPrice());
    }

    public function testSimpleProductWithoutVariantsPreservesFullBackwardCompatibility(): void
    {
        $this->assertEmpty($this->simpleProduct->getVariants());
        $this->assertEmpty($this->simpleProduct->getVariationAxes());
        $this->assertNull($this->simpleProduct->findVariantBySku('QUALQUER_SKU'));

        $adapter = new DropshipProductPurchasableAdapter($this->simpleProduct);
        $this->assertSame('Alicate Crimpador Universal', $adapter->getTitle());
        $this->assertSame(89.90, $adapter->getPrice());
        $this->assertSame('https://example.com/tool-main.jpg', $adapter->getThumbnailUrl());
        $this->assertTrue($adapter->isAvailable());
    }

    public function testLingeriePushUpProductHasSizesAndColorAttributes(): void
    {
        $repoMock = $this->createMock(\DomainSystem\Plugins\domain_drop\Contracts\DropshipCatalogRepositoryInterface::class);
        $apiClientMock = $this->createMock(\DomainSystem\Plugins\domain_drop\Contracts\AliExpressApiClientInterface::class);
        $currencyMock = $this->createMock(\DomainSystem\Plugins\domain_drop\Contracts\CurrencyConverterInterface::class);
        $aiMock = $this->createMock(\DomainSystem\Plugins\domain_drop\Contracts\AiProductEnricherInterface::class);
        $scraperMock = $this->createMock(\DomainSystem\Plugins\domain_drop\Contracts\ProductLinkScraperInterface::class);

        $currencyMock->method('calculateSalePrice')->willReturnCallback(fn(float $usd) => round($usd * 5.25 * 1.5, 2));
        $aiMock->method('polishTitle')->willReturnCallback(fn(string $t) => $t);
        $aiMock->method('generateSalesCopy')->willReturn('Copy de venda');

        $miningService = new \DomainSystem\Plugins\domain_drop\Services\ProductMiningService(
            repository: $repoMock,
            apiClient: $apiClientMock,
            currencyConverter: $currencyMock,
            aiEnricher: $aiMock,
            linkScraper: $scraperMock
        );

        $curated = $miningService->findCuratedProductById('1005006982221921');
        $this->assertNotNull($curated, 'Produto 1005006982221921 deve estar presente no catálogo curado');
        $this->assertSame('Conjunto De Lingerie Feminina Renda Bordado Push Up Sutiã Calcinha', $curated['product_title']);
        $this->assertSame('Moda Íntima, Lingeries & Pijamas', $curated['category_name']);

        $axes = $curated['variation_axes'];
        $this->assertArrayHasKey('Cor', $axes);
        $this->assertArrayHasKey('Tamanho', $axes);
        $this->assertContains('Preto', $axes['Cor']);
        $this->assertGreaterThanOrEqual(2, count($axes['Cor']));
        $this->assertEquals(['S (BR P)', 'M (BR M)', 'L (BR G)', 'XL (BR GG)'], $axes['Tamanho']);

        $variants = $curated['variants'];
        $this->assertCount(12, $variants);
        foreach ($variants as $v) {
            $this->assertGreaterThan(0, $v['stock']);
            $this->assertNotEmpty($v['image_url']);
            $this->assertArrayHasKey('Tamanho', $v['options']);
            $this->assertArrayHasKey('Cor', $v['options']);
        }
    }

    public function testTransformRawItemExtractsSinglePropertyAssocSkusForTools(): void
    {
        $apiMock = $this->createMock(\DomainSystem\Plugins\domain_drop\Contracts\AliExpressApiClientInterface::class);
        $currencyMock = $this->createMock(\DomainSystem\Plugins\domain_drop\Contracts\CurrencyConverterInterface::class);
        $currencyMock->method('calculateSalePrice')->willReturn(95.00);
        $currencyMock->method('calculateSalePriceFromBrl')->willReturn(95.00);
        $aiMock = $this->createMock(\DomainSystem\Plugins\domain_drop\Contracts\AiProductEnricherInterface::class);
        $aiMock->method('polishTitle')->willReturnCallback(fn(string $t): string => $t);
        $aiMock->method('generateSalesCopy')->willReturn('Descrição teste');
        $repoMock = $this->createMock(\DomainSystem\Plugins\domain_drop\Contracts\DropshipCatalogRepositoryInterface::class);

        $service = new \DomainSystem\Plugins\domain_drop\Services\ProductMiningService(
            $apiMock,
            $currencyMock,
            $aiMock,
            $repoMock
        );

        $rawItem = [
            'product_id'             => '10050999990001',
            'product_title'          => 'Alicate Desencapador de Fios Profissional',
            'category_name'          => 'Ferramentas & Utilidades',
            'target_sale_price'      => '8.50',
            'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/tool-main.jpg',
            'ae_item_sku_info_dtos'  => [
                'ae_item_sku_info_d_t_o' => [
                    [
                        'sku_id'                => 'SKU-YLW',
                        'sku_price'             => '8.50',
                        'sku_available_stock'   => 30,
                        'sku_image_url'         => 'https://ae-pic-a1.aliexpress-media.com/kf/tool-yellow.jpg',
                        'ae_sku_property_dtos'  => [
                            // Single associative property (not a list!) as returned by TOP/IOP XML->JSON
                            'ae_sku_property_d_t_o' => [
                                'sku_property_name'              => 'Color',
                                'property_value_definition_name' => 'Amarelo',
                            ],
                        ],
                    ],
                    [
                        'sku_id'                => 'SKU-RED',
                        'sku_price'             => '8.50',
                        'sku_available_stock'   => 25,
                        'sku_image_url'         => 'https://ae-pic-a1.aliexpress-media.com/kf/tool-red.jpg',
                        'ae_sku_property_dtos'  => [
                            'ae_sku_property_d_t_o' => [
                                'sku_property_name'              => 'Color',
                                'property_value_definition_name' => 'Vermelho',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $method = new \ReflectionMethod(\DomainSystem\Plugins\domain_drop\Services\ProductMiningService::class, 'transformRawItemToDto');
        $method->setAccessible(true);
        /** @var DropshipProductDto $dto */
        $dto = $method->invoke($service, $rawItem);

        $axes = $dto->getVariationAxes();
        $this->assertArrayHasKey('Cor', $axes);
        $this->assertSame(['Amarelo', 'Vermelho'], $axes['Cor']);
        $this->assertCount(2, $dto->getVariants());
        $this->assertSame('Amarelo', $dto->getVariants()[0]->options['Cor']);
        $this->assertSame('Vermelho', $dto->getVariants()[1]->options['Cor']);
    }

    public function testCuratedToolsIncludeColorVariantsForWireStripper(): void
    {
        $service = new \DomainSystem\Plugins\domain_drop\Services\ProductMiningService(
            $this->createMock(\DomainSystem\Plugins\domain_drop\Contracts\AliExpressApiClientInterface::class),
            $this->createMock(\DomainSystem\Plugins\domain_drop\Contracts\CurrencyConverterInterface::class),
            $this->createMock(\DomainSystem\Plugins\domain_drop\Contracts\AiProductEnricherInterface::class),
            $this->createMock(\DomainSystem\Plugins\domain_drop\Contracts\DropshipCatalogRepositoryInterface::class)
        );
        $method = new \ReflectionMethod(\DomainSystem\Plugins\domain_drop\Services\ProductMiningService::class, 'getCuratedTrendingFallback');
        $method->setAccessible(true);
        $items = $method->invoke($service, 'Ferramentas & Utilidades', 10);

        $alicate = null;
        foreach ($items as $item) {
            if (($item['product_id'] ?? '') === '1005010179002980') {
                $alicate = $item;
                break;
            }
        }

        $this->assertNotNull($alicate);
        $this->assertArrayHasKey('variation_axes', $alicate);
        $this->assertSame(['Amarelo', 'Vermelho'], $alicate['variation_axes']['Cor']);
        $this->assertCount(2, $alicate['variants']);
    }

    public function testScraperExtractsSkuPropertyListColorsAndSizes(): void
    {
        $scraper = new \DomainSystem\Plugins\domain_drop\Services\AliExpressLinkScraperService(
            new \DomainSystem\Plugins\domain_drop\Services\CategoryTaxonomyService()
        );

        $html = '<script>window.runParams = {"skuPropertyList":[{"skuPropertyId":14,"skuPropertyName":"Color","skuPropertyValues":[{"propertyValueId":1,"propertyValueDisplayName":"Amarelo","skuPropertyImagePath":"//ae01.alicdn.com/kf/yellow_50x50.jpg"},{"propertyValueId":2,"propertyValueDisplayName":"Vermelho","skuPropertyImagePath":"//ae01.alicdn.com/kf/red_50x50.jpg"}]},{"skuPropertyId":5,"skuPropertyName":"Size","skuPropertyValues":[{"propertyValueId":10,"propertyValueDisplayName":"M"},{"propertyValueId":11,"propertyValueDisplayName":"G"}]}]};</script>';

        $method = new \ReflectionMethod(\DomainSystem\Plugins\domain_drop\Services\AliExpressLinkScraperService::class, 'extractSkuVariantsFromSource');
        $method->setAccessible(true);
        $result = $method->invoke($scraper, $html, '1005010179002980', 10.0, 'https://ae01.alicdn.com/kf/main.jpg');

        $this->assertSame(['Amarelo', 'Vermelho'], $result['variation_axes']['Cor']);
        $this->assertSame(['M', 'G'], $result['variation_axes']['Tamanho']);
        $this->assertCount(4, $result['variants']);
        $this->assertSame('https://ae01.alicdn.com/kf/yellow', $result['variants'][0]['image_url']);
    }
}
