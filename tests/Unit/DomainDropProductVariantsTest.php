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
}
