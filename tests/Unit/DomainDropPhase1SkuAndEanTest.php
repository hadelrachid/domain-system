<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Plugins\domain_drop\Contracts\AiProductEnricherInterface;
use DomainSystem\Plugins\domain_drop\Contracts\CurrencyConverterInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipCatalogRepositoryInterface;
use DomainSystem\Plugins\domain_drop\Contracts\ProductLinkScraperInterface;
use DomainSystem\Plugins\domain_drop\DTO\DropshipProductDto;
use DomainSystem\Plugins\domain_drop\DTO\ProductVariantDto;
use DomainSystem\Plugins\domain_drop\Services\AliExpressApiClient;
use DomainSystem\Plugins\domain_drop\Services\ProductMiningService;
use PHPUnit\Framework\TestCase;

final class DomainDropPhase1SkuAndEanTest extends TestCase
{
    public function testAliExpressApiClientPhase1Endpoints(): void
    {
        $client = new class('test_app_key', 'test_app_secret', 'https://api-sg.aliexpress.com/sync') extends AliExpressApiClient {
            /** @var array<string, mixed> */
            public array $capturedParams = [];
            public string $capturedMethod = '';

            public function execute(string $apiMethod, array $params = [], ?string $accessToken = null): array
            {
                $this->capturedMethod = $apiMethod;
                $this->capturedParams = $params;

                return match ($apiMethod) {
                    'aliexpress.solution.sku.attribute.query' => [
                        'result' => [
                            'sku_attribute_list' => [
                                ['name' => 'Color', 'values' => ['Black', 'White']],
                                ['name' => 'Plug Type', 'values' => ['EU Plug', 'US Plug']],
                            ],
                        ],
                    ],
                    'aliexpress.product.eancode.info.query' => [
                        'result' => [
                            'ean_code'   => '7891234567890',
                            'brand_name' => 'Official Brand',
                            'valid'      => true,
                        ],
                    ],
                    'aliexpress.solution.product.inventory.query' => [
                        'result' => [
                            'inventory_list' => [
                                ['warehouse' => 'BR_LOCAL', 'available_stock' => 150],
                            ],
                        ],
                    ],
                    'aliexpress.solution.batch.product.price.update' => [
                        'update_success' => true,
                        'update_successful_list' => ['100500123'],
                    ],
                    default => [],
                };
            }
        };

        // 1. getSkuAttributes
        $skuAttrs = $client->getSkuAttributes('200000801');
        $this->assertSame('aliexpress.solution.sku.attribute.query', $client->capturedMethod);
        $this->assertStringContainsString('200000801', $client->capturedParams['query_sku_attribute_info_request']);
        $this->assertArrayHasKey('sku_attribute_list', $skuAttrs);

        // 2. getProductEanCode
        $eanResult = $client->getProductEanCode('7891234567890');
        $this->assertSame('aliexpress.product.eancode.info.query', $client->capturedMethod);
        $this->assertSame('7891234567890', $client->capturedParams['ean_code']);
        $this->assertSame('7891234567890', $eanResult['ean_code']);

        // 3. queryProductInventory
        $invResult = $client->queryProductInventory('100500123');
        $this->assertSame('aliexpress.solution.product.inventory.query', $client->capturedMethod);
        $this->assertSame('100500123', $client->capturedParams['product_id']);
        $this->assertArrayHasKey('inventory_list', $invResult);

        // 4. updateBatchProductPrices
        $batchResult = $client->updateBatchProductPrices([
            ['product_id' => '100500123', 'sku_id' => 'sku_1', 'price' => '29.90'],
        ]);
        $this->assertSame('aliexpress.solution.batch.product.price.update', $client->capturedMethod);
        $this->assertStringContainsString('sku_1', $client->capturedParams['mutiple_product_update_list']);
        $this->assertTrue($batchResult['update_success']);
    }

    public function testProductVariantAndDtoEanSupport(): void
    {
        $variant = new ProductVariantDto(
            skuId: 'sku_blue_eu',
            options: ['Cor' => 'Azul', 'Tipo de Tomada' => 'Plug EU (Padrão BR)'],
            priceBrl: 159.90,
            costPriceUsd: 15.00,
            imageUrl: 'https://example.com/blue.jpg',
            stock: 25,
            eanCode: '7891000000001'
        );

        $this->assertSame('7891000000001', $variant->eanCode);
        $variantArray = $variant->toArray();
        $this->assertSame('7891000000001', $variantArray['ean_code']);

        $restored = ProductVariantDto::fromArray($variantArray);
        $this->assertSame('7891000000001', $restored->eanCode);

        // DropshipProductDto getEanCode prioriza direto ou variante
        $prodDirect = new DropshipProductDto(
            aliexpressId: '10050099',
            title: 'Smart Watch AMOLED',
            slug: 'smart-watch-amoled',
            costPriceUsd: 20.0,
            salePriceBrl: 199.9,
            mainImageUrl: 'https://example.com/main.jpg',
            attributes: ['ean_code' => '7899999999999']
        );
        $this->assertSame('7899999999999', $prodDirect->getEanCode());

        $prodFromVariant = new DropshipProductDto(
            aliexpressId: '10050099',
            title: 'Smart Watch AMOLED',
            slug: 'smart-watch-amoled',
            costPriceUsd: 20.0,
            salePriceBrl: 199.9,
            mainImageUrl: 'https://example.com/main.jpg',
            attributes: ['variants' => [$variant]]
        );
        $this->assertSame('7891000000001', $prodFromVariant->getEanCode());
    }

    public function testProductMiningServiceTranslatesAttributesAndExtractsEan(): void
    {
        $repo = $this->createMock(DropshipCatalogRepositoryInterface::class);
        $api = $this->createMock(AliExpressApiClient::class);
        $conv = $this->createMock(CurrencyConverterInterface::class);
        $conv->method('calculateSalePrice')->willReturn(149.90);
        $conv->method('calculateSalePriceFromBrl')->willReturn(149.90);

        $enricher = $this->createMock(AiProductEnricherInterface::class);
        $enricher->method('polishTitle')->willReturn('Smartwatch Pro X90 Bivolt');
        $enricher->method('generateSalesCopy')->willReturn('Descrição rica de alta conversão');

        $scraper = $this->createMock(ProductLinkScraperInterface::class);

        $mining = new ProductMiningService(
            repository: $repo,
            apiClient: $api,
            currencyConverter: $conv,
            aiEnricher: $enricher,
            linkScraper: $scraper
        );

        $rawItem = [
            'item_id' => '1005008888',
            'title'   => 'Raw Smartwatch',
            'ean_code' => '7898888888888',
            'product_main_image_url' => 'https://example.com/thumb.jpg',
            'ae_item_sku_info_dtos' => [
                'ae_item_sku_info_d_t_o' => [
                    [
                        'sku_id' => 'sku_volt_bivolt',
                        'offer_sale_price' => 12.50,
                        'ean_code' => '7897777777777',
                        'ae_sku_property_dtos' => [
                            ['sku_property_name' => 'plug type', 'property_value_definition_name' => 'EU plug'],
                            ['sku_property_name' => 'voltage', 'property_value_definition_name' => '110V/220V Bivolt'],
                            ['sku_property_name' => 'color', 'property_value_definition_name' => 'Space Black'],
                        ],
                    ],
                ],
            ],
        ];

        // Usa reflexão para testar o método protegido/privado de transformação
        $ref = new \ReflectionClass($mining);
        $method = $ref->getMethod('transformRawItemToDto');
        $method->setAccessible(true);

        /** @var DropshipProductDto $dto */
        $dto = $method->invoke($mining, $rawItem, 'Eletrônicos');

        $this->assertTrue($dto->hasVariants());
        $axes = $dto->getVariationAxes();
        $this->assertArrayHasKey('Tipo de Tomada', $axes);
        $this->assertArrayHasKey('Voltagem', $axes);
        $this->assertArrayHasKey('Cor', $axes);

        $variants = $dto->getVariants();
        $this->assertCount(1, $variants);
        $this->assertSame('EU plug', $variants[0]->options['Tipo de Tomada']);
        $this->assertSame('110V/220V Bivolt', $variants[0]->options['Voltagem']);
        $this->assertSame('Space Black', $variants[0]->options['Cor']);
        $this->assertSame('7897777777777', $variants[0]->eanCode);
        $this->assertSame('7898888888888', $dto->getEanCode());
    }
}
