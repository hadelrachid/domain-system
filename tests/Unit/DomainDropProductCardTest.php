<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use DomainSystem\Plugins\domain_drop\Cards\AbstractProductCard;
use DomainSystem\Plugins\domain_drop\Cards\FashionProductCard;
use DomainSystem\Plugins\domain_drop\Cards\NationalStockProductCard;
use DomainSystem\Plugins\domain_drop\Cards\ProductCardFactory;
use DomainSystem\Plugins\domain_drop\Cards\StandardProductCard;
use DomainSystem\Plugins\domain_drop\Cards\TechProductCard;
use DomainSystem\Plugins\domain_drop\Contracts\ProductCardInterface;
use DomainSystem\Plugins\domain_drop\DTO\DropshipProductDto;

class DomainDropProductCardTest extends TestCase
{
    protected function setUp(): void
    {
        if (!is_dir(dirname(__DIR__, 2) . '/src/Plugins/domain_drop')) {
            $this->markTestSkipped('Plugin agregado externo [domain_drop] não está presente no checkout.');
            return;
        }
        parent::setUp();
    }

    private function makeDto(array $overrides = []): object
    {
        return new DropshipProductDto(
            aliexpressId: $overrides['aliexpress_id'] ?? '1005009876543210',
            title: $overrides['title'] ?? 'Produto de Teste Dropshipping',
            slug: $overrides['slug'] ?? 'produto-de-teste-dropshipping',
            costPriceUsd: (float)($overrides['cost_price_usd'] ?? 10.00),
            salePriceBrl: (float)($overrides['sale_price_brl'] ?? 99.90),
            mainImageUrl: $overrides['main_image_url'] ?? 'https://ae01.alicdn.com/kf/test.jpg',
            images: $overrides['images'] ?? ['https://ae01.alicdn.com/kf/test1.jpg', 'https://ae01.alicdn.com/kf/test2.jpg'],
            category: $overrides['category'] ?? 'Utilidades Domésticas',
            description: $overrides['description'] ?? 'Descrição do produto teste',
            rating: (float)($overrides['rating'] ?? 4.8),
            ordersCount: (int)($overrides['orders_count'] ?? 350),
            attributes: $overrides['attributes'] ?? [],
            status: $overrides['status'] ?? 'draft',
            id: $overrides['id'] ?? 101
        );
    }

    public function testStandardCardInstantiationAndRendering(): void
    {
        $dto = $this->makeDto(['category' => 'Casa e Jardim']);
        $card = ProductCardFactory::create($dto, ['rate' => 5.75, 'baseUrl' => 'http://localhost']);

        $this->assertInstanceOf(ProductCardInterface::class, $card);
        $this->assertInstanceOf(AbstractProductCard::class, $card);
        $this->assertInstanceOf(StandardProductCard::class, $card);

        $html = $card->render();
        $this->assertStringContainsString('id="prod-card-101"', $html);
        $this->assertStringContainsString('card-product-standard', $html);
        $this->assertStringContainsString('id="card-check-101"', $html);
        $this->assertStringContainsString('⏳ Em Revisão', $html);
        $this->assertStringContainsString('Carregar no Site', $html);
        $this->assertStringContainsString('R$ 99,90', $html);
    }

    public function testNationalStockCardSpecialization(): void
    {
        $dto = $this->makeDto([
            'title' => 'Smartwatch Estoque no Brasil Pronta Entrega',
            'attributes' => ['origem_brasil' => true, 'ship_from' => 'BR'],
        ]);
        $card = ProductCardFactory::create($dto, ['rate' => 5.75]);

        $this->assertInstanceOf(NationalStockProductCard::class, $card);
        $html = $card->render();

        $this->assertStringContainsString('card-product-national', $html);
        $this->assertStringContainsString('Estoque Brasil', $html);
        $this->assertStringContainsString('Sem taxa de importação • Despacho imediato 24h', $html);
        $this->assertStringContainsString('<span>🇧🇷</span> BR', $html);
    }

    public function testFashionCardSpecializationWithSizesAndColors(): void
    {
        $dto = $this->makeDto([
            'category' => 'Moda Feminina',
            'title' => 'Vestido Longo Floral Elegante',
            'attributes' => [
                'sizes' => ['P', 'M', 'G', 'GG'],
                'colors' => ['Preto', 'Azul Marinho', 'Vinho'],
            ],
            'status' => 'published',
        ]);
        $card = ProductCardFactory::create($dto, ['rate' => 5.75]);

        $this->assertInstanceOf(FashionProductCard::class, $card);
        $html = $card->render();

        $this->assertStringContainsString('card-product-fashion', $html);
        $this->assertStringContainsString('Moda', $html);
        $this->assertStringContainsString('Tam:', $html);
        $this->assertStringContainsString('>P</span>', $html);
        $this->assertStringContainsString('>GG</span>', $html);
        $this->assertStringContainsString('Cores:', $html);
        $this->assertStringContainsString('Preto', $html);
        $this->assertStringContainsString('✅ No Ar', $html);
        $this->assertStringContainsString('Ocultar', $html);
    }

    public function testTechCardSpecializationWithVideoAndSpecs(): void
    {
        $dto = $this->makeDto([
            'category' => 'Eletrônicos e Smartphones',
            'title' => 'Fone de Ouvido Bluetooth TWS Pro',
            'attributes' => [
                'video_url' => 'https://video.aliexpress.com/demo.mp4',
            ],
        ]);
        $card = ProductCardFactory::create($dto, ['rate' => 5.75]);

        $this->assertInstanceOf(TechProductCard::class, $card);
        $html = $card->render();

        $this->assertStringContainsString('card-product-tech', $html);
        $this->assertStringContainsString('Tech', $html);
        $this->assertStringContainsString('Vídeo de Anúncio Disponível', $html);
        $this->assertStringContainsString('prod-video-btn-101', $html);
    }

    public function testFactoryDirectRender(): void
    {
        $dto = $this->makeDto();
        $html = ProductCardFactory::render($dto, ['rate' => 6.00]);

        $this->assertNotEmpty($html);
        $this->assertStringContainsString('prod-card-101', $html);
        $this->assertStringContainsString('$10.00 (~R$ 60,00)', $html);
    }
}
