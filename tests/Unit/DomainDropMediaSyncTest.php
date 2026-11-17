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

    public function testAdminSyncImagesReturnsJsonOnAjaxRequest(): void
    {
        $savedId = $this->repo->save(new DropshipProductDto(
            aliexpressId: '1005012009938049',
            title: 'Smartwatch Ultra Teste',
            slug: 'smartwatch-ultra-teste',
            costPriceUsd: 10.0,
            salePriceBrl: 110.0,
            mainImageUrl: 'https://images.unsplash.com/photo-1526738549149-8e07eca6c147?w=600',
            images: [],
            category: 'Eletrônicos',
            description: 'Desc',
            rating: 4.9,
            ordersCount: 100
        ));

        $mockScraper = $this->createMock(ProductLinkScraperInterface::class);
        $mockScraper->method('scrape')->with('1005012009938049')->willReturn(new ScrapedProductDto(
            sourceId: '1005012009938049',
            sourceUrl: 'https://pt.aliexpress.com/item/1005012009938049.html',
            rawTitle: 'Smartwatch Ultra Pro',
            mainImageUrl: 'https://ae-pic-a1.aliexpress-media.com/kf/A111.jpg',
            galleryImages: [
                'https://ae-pic-a1.aliexpress-media.com/kf/A111.jpg',
                'https://ae-pic-a1.aliexpress-media.com/kf/B222.jpg',
            ],
            costPriceUsd: 12.0,
            category: 'Eletrônicos'
        ));

        $tmpCache = sys_get_temp_dir() . '/test_exchange_' . uniqid() . '.json';
        file_put_contents($tmpCache, json_encode(['USD' => 5.50]));
        $converter = new CurrencyConverterService($tmpCache);
        $enricher = new AiProductEnricherService();
        $syncService = new ProductMediaSyncService($this->repo, $mockScraper, $enricher, $converter);
        $mining = $this->createMock(\DomainSystem\Plugins\domain_drop\Contracts\ProductMiningServiceInterface::class);
        $session = $this->createMock(\DomainSystem\Core\Contracts\SessionManagerInterface::class);
        $session->method('getCsrfToken')->willReturn('tok123');

        $controller = new \DomainSystem\Plugins\domain_drop\Controllers\DropshipAdminController(
            catalogRepo: $this->repo,
            miningService: $mining,
            currencyConverter: $converter,
            session: $session,
            mediaSync: $syncService
        );

        $request = new \DomainSystem\Core\Http\Request(
            query: [],
            request: ['csrf_token' => 'tok123', 'ajax' => '1'],
            server: ['REQUEST_METHOD' => 'POST', 'HTTP_ACCEPT' => 'application/json', 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        );

        $response = $controller->syncImages($request, (string)$savedId);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertSame('https://ae-pic-a1.aliexpress-media.com/kf/A111.jpg', $data['main_image_url']);
        $this->assertSame(2, $data['count']);

        @unlink($tmpCache);
    }

    public function testAdminUpdateImagesSanitizesUnsafeUrlsAndPersistsGallery(): void
    {
        $savedId = $this->repo->save(new DropshipProductDto(
            aliexpressId: '1005005967732299',
            title: 'Fone TWS',
            slug: 'fone-tws',
            costPriceUsd: 12.0,
            salePriceBrl: 130.0,
            mainImageUrl: 'https://ae-pic-a1.aliexpress-media.com/kf/OLD.jpg',
            images: ['https://ae-pic-a1.aliexpress-media.com/kf/OLD.jpg'],
            category: 'Eletrônicos',
            description: 'Desc',
            rating: 4.8,
            ordersCount: 250
        ));

        $converter = $this->createMock(CurrencyConverterInterface::class);
        $mining = $this->createMock(\DomainSystem\Plugins\domain_drop\Contracts\ProductMiningServiceInterface::class);
        $session = $this->createMock(\DomainSystem\Core\Contracts\SessionManagerInterface::class);
        $session->method('getCsrfToken')->willReturn('tok123');

        $controller = new \DomainSystem\Plugins\domain_drop\Controllers\DropshipAdminController(
            catalogRepo: $this->repo,
            miningService: $mining,
            currencyConverter: $converter,
            session: $session
        );

        $request = new \DomainSystem\Core\Http\Request(
            query: [],
            request: [
                'csrf_token'     => 'tok123',
                'ajax'           => '1',
                'main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/CAPA.jpg',
                'images_json'    => json_encode([
                    'javascript:alert(1)',
                    'https://ae-pic-a1.aliexpress-media.com/kf/CAPA.jpg',
                    'https://ae-pic-a1.aliexpress-media.com/kf/EXTRA.jpg',
                ]),
            ],
            server: ['REQUEST_METHOD' => 'POST', 'HTTP_ACCEPT' => 'application/json', 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        );

        $response = $controller->updateImages($request, (string)$savedId);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertSame('https://ae-pic-a1.aliexpress-media.com/kf/CAPA.jpg', $data['main_image_url']);
        $this->assertCount(2, $data['images']);
        $this->assertNotContains('javascript:alert(1)', $data['images']);

        $reloaded = $this->repo->findById($savedId);
        $this->assertSame('https://ae-pic-a1.aliexpress-media.com/kf/CAPA.jpg', $reloaded->mainImageUrl);
        $this->assertCount(2, $reloaded->images);
    }

    public function testSyncAllCatalogMediaSkipsProductsWithMultipleImages(): void
    {
        // Produto 1: já tem galeria completa (> 1 foto) -> deve ser ignorado
        $this->repo->save(new DropshipProductDto(
            aliexpressId: '1005011111111111',
            title: 'Produto com Galeria Completa',
            slug: 'produto-com-galeria-completa',
            costPriceUsd: 10.0,
            salePriceBrl: 100.0,
            mainImageUrl: 'https://exemplo.com/foto1.jpg',
            images: ['https://exemplo.com/foto1.jpg', 'https://exemplo.com/foto2.jpg'],
            category: 'Moda'
        ));

        // Produto 2: tem apenas 1 foto -> deve ser sincronizado
        $this->repo->save(new DropshipProductDto(
            aliexpressId: '1005022222222222',
            title: 'Produto com Foto Única',
            slug: 'produto-com-foto-unica',
            costPriceUsd: 15.0,
            salePriceBrl: 150.0,
            mainImageUrl: 'https://exemplo.com/foto-unica.jpg',
            images: ['https://exemplo.com/foto-unica.jpg'],
            category: 'Moda'
        ));

        $mockScraper = $this->createMock(ProductLinkScraperInterface::class);
        $mockScraper->expects($this->once()) // Somente o produto 2 deve acionar o scraper!
            ->method('scrape')
            ->with('1005022222222222')
            ->willReturn(new ScrapedProductDto(
                sourceId: '1005022222222222',
                sourceUrl: 'https://pt.aliexpress.com/item/1005022222222222.html',
                rawTitle: 'Produto com Foto Única Raspado',
                mainImageUrl: 'https://exemplo.com/nova1.jpg',
                galleryImages: ['https://exemplo.com/nova1.jpg', 'https://exemplo.com/nova2.jpg', 'https://exemplo.com/nova3.jpg'],
                costPriceUsd: 15.0,
                category: 'Moda'
            ));

        $tmpCache = sys_get_temp_dir() . '/test_exchange_' . uniqid() . '.json';
        file_put_contents($tmpCache, json_encode(['USD' => 5.0]));
        $converter = new CurrencyConverterService($tmpCache);
        $enricher = new AiProductEnricherService();

        $syncService = new ProductMediaSyncService($this->repo, $mockScraper, $enricher, $converter);
        $result = $syncService->syncAllCatalogMedia(skipIfAlreadyMultiple: true);

        $this->assertSame(2, $result['total']);
        $this->assertSame(2, $result['checked']);
        $this->assertSame(1, $result['skipped_already_multiple']);
        $this->assertSame(1, $result['synced']);
        $this->assertSame(0, $result['failed']);

        @unlink($tmpCache);
    }

    public function testSyncMissingGalleriesTargetsOnlyIncompleteGalleries(): void
    {
        // Produto 1: já tem 3 fotos
        $this->repo->save(new DropshipProductDto(
            aliexpressId: '1005033333333333',
            title: 'Smartwatch 3 Fotos',
            slug: 'smartwatch-3-fotos',
            costPriceUsd: 20.0,
            salePriceBrl: 200.0,
            mainImageUrl: 'https://exemplo.com/s1.jpg',
            images: ['https://exemplo.com/s1.jpg', 'https://exemplo.com/s2.jpg', 'https://exemplo.com/s3.jpg'],
            category: 'Eletrônicos'
        ));

        // Produto 2: tem 1 foto
        $id2 = $this->repo->save(new DropshipProductDto(
            aliexpressId: '1005044444444444',
            title: 'Fone 1 Foto',
            slug: 'fone-1-foto',
            costPriceUsd: 8.0,
            salePriceBrl: 80.0,
            mainImageUrl: 'https://exemplo.com/f1.jpg',
            images: ['https://exemplo.com/f1.jpg'],
            category: 'Eletrônicos'
        ));

        $mockScraper = $this->createMock(ProductLinkScraperInterface::class);
        $mockScraper->expects($this->once())
            ->method('scrape')
            ->with('1005044444444444')
            ->willReturn(new ScrapedProductDto(
                sourceId: '1005044444444444',
                sourceUrl: 'https://pt.aliexpress.com/item/1005044444444444.html',
                rawTitle: 'Fone com Nova Galeria',
                mainImageUrl: 'https://exemplo.com/f1-novo.jpg',
                galleryImages: ['https://exemplo.com/f1-novo.jpg', 'https://exemplo.com/f2-novo.jpg'],
                costPriceUsd: 8.0,
                category: 'Eletrônicos'
            ));

        $tmpCache = sys_get_temp_dir() . '/test_exchange_' . uniqid() . '.json';
        file_put_contents($tmpCache, json_encode(['USD' => 5.0]));
        $converter = new CurrencyConverterService($tmpCache);
        $enricher = new AiProductEnricherService();

        $syncService = new ProductMediaSyncService($this->repo, $mockScraper, $enricher, $converter);
        $result = $syncService->syncMissingGalleries(limit: 10);

        $this->assertSame(2, $result['total_checked']);
        $this->assertSame(1, $result['needed_sync']);
        $this->assertSame(1, $result['synced']);
        $this->assertSame(0, $result['failed']);

        $updatedProduct = $this->repo->findById($id2);
        $this->assertCount(2, $updatedProduct->images);

        @unlink($tmpCache);
    }

    public function testAdminSyncQueueReturnsStructuredItemsAndCounts(): void
    {
        $this->repo->save(new DropshipProductDto(
            aliexpressId: '1005011111111111',
            title: 'Produto 1 (Multi)',
            slug: 'prod-1',
            costPriceUsd: 10.0,
            salePriceBrl: 100.0,
            mainImageUrl: 'https://exemplo.com/1.jpg',
            images: ['https://exemplo.com/1.jpg', 'https://exemplo.com/2.jpg'],
            category: 'Moda'
        ));

        $this->repo->save(new DropshipProductDto(
            aliexpressId: '1005022222222222',
            title: 'Produto 2 (Single)',
            slug: 'prod-2',
            costPriceUsd: 15.0,
            salePriceBrl: 150.0,
            mainImageUrl: 'https://exemplo.com/single.jpg',
            images: ['https://exemplo.com/single.jpg'],
            category: 'Eletrônicos'
        ));

        $controller = new \DomainSystem\Plugins\domain_drop\Controllers\DropshipAdminController(
            catalogRepo: $this->repo,
            miningService: $this->createMock(\DomainSystem\Plugins\domain_drop\Contracts\ProductMiningServiceInterface::class),
            currencyConverter: $this->createMock(CurrencyConverterInterface::class),
            session: $this->createMock(\DomainSystem\Core\Contracts\SessionManagerInterface::class)
        );

        $request = new \DomainSystem\Core\Http\Request(query: [], request: [], server: ['REQUEST_METHOD' => 'GET']);
        $response = $controller->syncQueue($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertSame(2, $data['total']);
        $this->assertSame(1, $data['single_or_empty_count']);
        $this->assertSame(1, $data['multi_count']);
        $this->assertCount(2, $data['items']);

        $itemsByAli = [];
        foreach ($data['items'] as $item) {
            $itemsByAli[$item['aliexpress_id']] = $item;
        }

        $this->assertFalse($itemsByAli['1005011111111111']['needs_sync']);
        $this->assertSame(2, $itemsByAli['1005011111111111']['images_count']);

        $this->assertTrue($itemsByAli['1005022222222222']['needs_sync']);
        $this->assertSame(1, $itemsByAli['1005022222222222']['images_count']);
    }
}
