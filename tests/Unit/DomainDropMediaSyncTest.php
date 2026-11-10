<?php

declare(strict_types=1);

namespace Tests\Unit;

use DomainSystem\Plugins\domain_drop\Contracts\AiProductEnricherInterface;
use DomainSystem\Plugins\domain_drop\Contracts\CurrencyConverterInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipCatalogRepositoryInterface;
use DomainSystem\Plugins\domain_drop\Contracts\ProductLinkScraperInterface;
use DomainSystem\Plugins\domain_drop\DTO\DropshipProductDto;
use DomainSystem\Plugins\domain_drop\DTO\ScrapedProductDto;
use DomainSystem\Plugins\domain_drop\Repositories\PdoDropshipCatalogRepository;
use DomainSystem\Plugins\domain_drop\Services\AiProductEnricherService;
use DomainSystem\Plugins\domain_drop\Services\AliExpressLinkScraperService;
use DomainSystem\Plugins\domain_drop\Services\CurrencyConverterService;
use DomainSystem\Plugins\domain_drop\Services\ProductMediaSyncService;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;

class DomainDropMediaSyncTest extends TestCase
{
    private PDO $pdo;
    private PdoDropshipCatalogRepository $repo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec("
            CREATE TABLE domain_drop_catalog (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                aliexpress_id VARCHAR(64) NOT NULL,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL UNIQUE,
                cost_price_usd DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
                sale_price_brl DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
                main_image_url TEXT NOT NULL,
                images TEXT NULL,
                category VARCHAR(100) NOT NULL DEFAULT 'Geral',
                description TEXT NULL,
                rating DECIMAL(3, 2) NOT NULL DEFAULT 4.80,
                orders_count INT NOT NULL DEFAULT 0,
                attributes TEXT NULL,
                status VARCHAR(30) NOT NULL DEFAULT 'published',
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            )
        ");

        $this->repo = new PdoDropshipCatalogRepository($this->pdo);
    }

    public function testScrapedProductDtoContractEnforcesMediaInvariants(): void
    {
        $dto = new ScrapedProductDto(
            sourceId: '1005013345247302',
            sourceUrl: 'https://pt.aliexpress.com/item/1005013345247302.html',
            rawTitle: 'Pulseira Homem de Ferro',
            mainImageUrl: 'https://ae-pic-a1.aliexpress-media.com/kf/S034dd953aa8d4e5c9744115de0efc991x.jpg',
            galleryImages: [
                'https://ae-pic-a1.aliexpress-media.com/kf/S034dd953aa8d4e5c9744115de0efc991x.jpg',
                'https://ae-pic-a1.aliexpress-media.com/kf/Sf1fdef1c56604b1bbd4ba590e1f0bd72g.jpg'
            ]
        );

        $this->assertSame('1005013345247302', $dto->sourceId);
        $this->assertCount(2, $dto->getAllImages());
        $this->assertSame('https://ae-pic-a1.aliexpress-media.com/kf/S034dd953aa8d4e5c9744115de0efc991x.jpg', $dto->getAllImages()[0]);

        $this->expectException(InvalidArgumentException::class);
        new ScrapedProductDto(
            sourceId: '',
            sourceUrl: '',
            rawTitle: 'Teste',
            mainImageUrl: ''
        );
    }

    public function testAliExpressLinkScraperCanHandleSupportedUrls(): void
    {
        $scraper = new AliExpressLinkScraperService();

        $this->assertTrue($scraper->canHandle('https://pt.aliexpress.com/item/1005013345247302.html'));
        $this->assertTrue($scraper->canHandle('https://www.aliexpress.com/item/1005006123456789.html?spm=123'));
        $this->assertTrue($scraper->canHandle('1005013345247302'));
        $this->assertFalse($scraper->canHandle('https://mercadolivre.com.br/item/123'));
        $this->assertFalse($scraper->canHandle('texto-invalido'));

        // Valida que URLs malformadas retornam false no filtro de link vivo
        $this->assertFalse($scraper->isProductAlive('link-inexistente-invalido'));
    }

    public function testProductMediaSyncServiceUpdatesCatalogFromScraperContract(): void
    {
        // 1. Salva produto com imagens antigas/incompatíveis
        $initialDto = new DropshipProductDto(
            aliexpressId: '1005013345247302',
            title: 'Produto com Foto Antiga',
            slug: 'produto-com-foto-antiga',
            costPriceUsd: 10.0,
            salePriceBrl: 99.90,
            mainImageUrl: 'https://exemplo.com/foto-antiga.jpg',
            images: ['https://exemplo.com/foto-antiga.jpg'],
            category: 'Eletrônicos'
        );
        $savedId = $this->repo->save($initialDto);

        // 2. Mock do Scraper retornando dados oficiais do link
        $mockScraper = $this->createMock(ProductLinkScraperInterface::class);
        $mockScraper->method('scrape')
            ->with('1005013345247302')
            ->willReturn(new ScrapedProductDto(
                sourceId: '1005013345247302',
                sourceUrl: 'https://pt.aliexpress.com/item/1005013345247302.html',
                rawTitle: 'Pulseira Homem de Ferro para Apple Watch',
                mainImageUrl: 'https://ae-pic-a1.aliexpress-media.com/kf/S034dd953aa8d4e5c9744115de0efc991x.jpg',
                galleryImages: [
                    'https://ae-pic-a1.aliexpress-media.com/kf/S034dd953aa8d4e5c9744115de0efc991x.jpg',
                    'https://ae-pic-a1.aliexpress-media.com/kf/Sf1fdef1c56604b1bbd4ba590e1f0bd72g.jpg'
                ],
                costPriceUsd: 5.50,
                category: 'Eletrônicos'
            ));

        $tmpCache = sys_get_temp_dir() . '/test_exchange_' . uniqid() . '.json';
        file_put_contents($tmpCache, json_encode(['USD' => 5.25]));
        $converter = new CurrencyConverterService($tmpCache);
        $enricher = new AiProductEnricherService();

        $syncService = new ProductMediaSyncService($this->repo, $mockScraper, $enricher, $converter);
        $updated = $syncService->syncProductMedia($savedId);

        // 3. Valida que a imagem foi substituída pelas fotos reais do link
        $this->assertSame('https://ae-pic-a1.aliexpress-media.com/kf/S034dd953aa8d4e5c9744115de0efc991x.jpg', $updated->mainImageUrl);
        $this->assertCount(2, $updated->images);
        $this->assertStringContainsString('Pulseira Homem De Ferro', $updated->title);

        @unlink($tmpCache);
    }
}
