<?php

declare(strict_types=1);

namespace Tests\Unit;

use DomainSystem\Plugins\domain_drop\Adapters\DropshipProductPurchasableAdapter;
use DomainSystem\Plugins\domain_drop\DTO\DropshipProductDto;
use PHPUnit\Framework\TestCase;

/**
 * Testes Unitários de Extração de Peso e Cálculo Dinâmico de Frete Expresso (Sedex / Correios).
 */
final class DomainDropShippingWeightTest extends TestCase
{
    public function testLightProductUnder300gChargesStandardExpressPrice(): void
    {
        $dto = new DropshipProductDto(
            aliexpressId: '1005001234567890',
            title: 'Conjunto De Lingerie Erótica Feminina Renda',
            slug: 'conjunto-lingerie-erotica-renda',
            costPriceUsd: 6.50,
            salePriceBrl: 69.90,
            mainImageUrl: 'https://example.com/lingerie.jpg',
            category: 'Moda Íntima, Lingeries & Pijamas',
            description: '<p>Lingerie confortável em renda francesa de alta qualidade.</p>',
            attributes: ['peso' => '150g']
        );

        $this->assertSame(150, $dto->getEstimatedWeightInGrams());
        $this->assertSame('150g', $dto->getFormattedWeight(1));
        // Até 300g: R$ 19,90
        $this->assertSame(19.90, $dto->calculateExpressShippingPrice(1));
    }

    public function testProductWeightExtractedFromSellerDescription(): void
    {
        $dto = new DropshipProductDto(
            aliexpressId: '1005009876543210',
            title: 'Moletom Unissex Flanelado Streetwear',
            slug: 'moletom-unissex-flanelado-streetwear',
            costPriceUsd: 15.00,
            salePriceBrl: 149.90,
            mainImageUrl: 'https://example.com/moletom.jpg',
            category: 'Roupas & Moda',
            description: '<div><h3>Ficha Técnica</h3><p>Material: Algodão Premium<br>Peso do pacote: 650g</p></div>',
            attributes: []
        );

        $this->assertSame(650, $dto->getEstimatedWeightInGrams());
        $this->assertSame('650g', $dto->getFormattedWeight(1));
        // De 301g até 1.000g: R$ 29,90
        $this->assertSame(29.90, $dto->calculateExpressShippingPrice(1));
    }

    public function testProductWeightInKilogramsExtractedFromSellerDescription(): void
    {
        $dto = new DropshipProductDto(
            aliexpressId: '1005007788990011',
            title: 'Mini Projetor Portátil Led Smart Cinema',
            slug: 'mini-projetor-portatil-led-smart-cinema',
            costPriceUsd: 40.00,
            salePriceBrl: 399.90,
            mainImageUrl: 'https://example.com/projetor.jpg',
            category: 'Eletrônicos & Casa Inteligente',
            description: '<div><p>Resolução nativa 1080p, conexões HDMI e Wi-Fi.<br>Weight: 1.4 kg com acessórios e fonte inclusa.</p></div>',
            attributes: []
        );

        $this->assertSame(1400, $dto->getEstimatedWeightInGrams());
        $this->assertSame('1,4 kg', $dto->getFormattedWeight(1));
        // De 1.001g até 2.000g: R$ 39,90
        $this->assertSame(39.90, $dto->calculateExpressShippingPrice(1));
    }

    public function testHeavyWeightAbove2KgAppliesCorreiosAdditionalKgRate(): void
    {
        $dto = new DropshipProductDto(
            aliexpressId: '1005005544332211',
            title: 'Kit Maleta de Ferramentas Automotiva 150 Peças',
            slug: 'kit-maleta-ferramentas-150-pecas',
            costPriceUsd: 50.00,
            salePriceBrl: 490.00,
            mainImageUrl: 'https://example.com/maleta.jpg',
            category: 'Ferramentas & Acessórios',
            description: '<p>Jogo de soquetes, catracas e bits em cromo vanádio.</p>',
            attributes: ['package_weight' => '3.5kg']
        );

        $this->assertSame(3500, $dto->getEstimatedWeightInGrams());
        $this->assertSame('3,5 kg', $dto->getFormattedWeight(1));
        // Acima de 2.000g: R$ 39,90 + ceil((3500-2000)/1000) * 10 = 39,90 + 20 = R$ 59,90
        $this->assertSame(59.90, $dto->calculateExpressShippingPrice(1));
    }

    public function testPurchasableAdapterAndQuantityScaleWeightAndPrice(): void
    {
        $dto = new DropshipProductDto(
            aliexpressId: '1005006996593220',
            title: 'Conjunto De Lingerie Erótica Feminina Sutiã Renda',
            slug: 'conjunto-lingerie-erotica-renda-preto',
            costPriceUsd: 7.20,
            salePriceBrl: 69.90,
            mainImageUrl: 'https://example.com/lingerie-preto.jpg',
            category: 'Moda Íntima, Lingeries & Pijamas',
            description: '<p>Sem aros, alças reguláveis.</p>',
            attributes: [] // Sem peso explícito -> fallback padrão leve de 200g
        );

        $adapter = new DropshipProductPurchasableAdapter($dto);

        $this->assertSame(200, $adapter->getWeightInGrams());
        // 1 unidade: 200g <= 300g -> R$ 19,90
        $this->assertSame('200g', $adapter->getFormattedWeight(1));
        $this->assertSame(19.90, $adapter->getExpressShippingPrice(1));

        // 2 unidades: 2 * 200g = 400g > 300g e <= 1000g -> R$ 29,90
        $this->assertSame('400g', $adapter->getFormattedWeight(2));
        $this->assertSame(29.90, $adapter->getExpressShippingPrice(2));

        // 6 unidades: 6 * 200g = 1200g > 1000g e <= 2000g -> R$ 39,90
        $this->assertSame('1,2 kg', $adapter->getFormattedWeight(6));
        $this->assertSame(39.90, $adapter->getExpressShippingPrice(6));
    }
}
