<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Core\Http\Request;
use DomainSystem\Plugins\domain_drop\Contracts\AliExpressApiClientInterface;
use DomainSystem\Plugins\domain_drop\Contracts\CurrencyConverterInterface;
use DomainSystem\Plugins\domain_drop\Contracts\AiProductEnricherInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipCatalogRepositoryInterface;
use DomainSystem\Plugins\domain_drop\Controllers\DropshipFrontController;
use DomainSystem\Plugins\domain_drop\Repositories\PdoDropshipCatalogRepository;
use DomainSystem\Plugins\domain_drop\Services\ProductMiningService;
use PDO;
use PHPUnit\Framework\TestCase;

final class DomainDropImageAndRouteFixTest extends TestCase
{
    private PDO $pdo;
    private DropshipCatalogRepositoryInterface $catalogRepo;

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
            );
        ");

        $this->catalogRepo = new PdoDropshipCatalogRepository($this->pdo);
    }

    private function createMiningService(): ProductMiningService
    {
        $apiClientMock = $this->createMock(AliExpressApiClientInterface::class);
        $currencyMock = $this->createMock(CurrencyConverterInterface::class);
        $currencyMock->method('getExchangeRate')->willReturn(5.75);
        $currencyMock->method('calculateSalePrice')->willReturnCallback(fn($usd) => round($usd * 5.75 * 1.45, 2));
        $aiEnricherMock = $this->createMock(AiProductEnricherInterface::class);
        $aiEnricherMock->method('polishTitle')->willReturnCallback(fn($t) => $t);
        $aiEnricherMock->method('generateSalesCopy')->willReturnCallback(fn($d) => $d);

        return new ProductMiningService(
            apiClient: $apiClientMock,
            currencyConverter: $currencyMock,
            aiEnricher: $aiEnricherMock,
            repository: $this->catalogRepo
        );
    }

    public function testNoMismatchedHairBrushImagesInHeadphonesOrTools(): void
    {
        $miningService = $this->createMiningService();

        $curatedItem = $miningService->findCuratedProductBySlug('headphone-bluetooth-over-ear-cancelamento-de-ruido-anc-bateria-60h');
        $this->assertNotNull($curatedItem, 'Headphone Bluetooth deve ser localizado pelo slug');
        $this->assertStringNotContainsString('S19d1c7dace654de7918987ecffe76d2fh.jpg', $curatedItem['product_main_image_url'], 'Headphone não pode ter imagem de escova de cabelo');
        $this->assertStringContainsString('photo-1505740420928', $curatedItem['product_main_image_url'], 'Headphone deve ter imagem real de headphone');

        $twsItem = $miningService->findCuratedProductBySlug('fone-de-ouvido-tws-bluetooth');
        $this->assertNotNull($twsItem);
        $this->assertStringNotContainsString('S19d1c7dace654de7918987ecffe76d2fh.jpg', $twsItem['product_main_image_url'], 'Fone TWS não pode ter imagem de escova de cabelo');

        $drillItem = $miningService->findCuratedProductBySlug('parafusadeira-furadeira-sem-fio');
        $this->assertNotNull($drillItem);
        $this->assertStringNotContainsString('S19d1c7dace654de7918987ecffe76d2fh.jpg', $drillItem['product_main_image_url'], 'Furadeira não pode ter imagem de escova de cabelo');
        $this->assertStringContainsString('photo-1504148455328', $drillItem['product_main_image_url'], 'Furadeira deve ter imagem de ferramenta');
    }

    public function testSingleProductAutoPersistsCuratedItemAndAvoids404(): void
    {
        $miningService = $this->createMiningService();

        // O catálogo no banco está completamente vazio
        $this->assertSame(0, $this->catalogRepo->count());

        $controller = new DropshipFrontController(
            catalogRepo: $this->catalogRepo,
            themeManager: null,
            miningService: $miningService
        );

        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/produto/headphone-bluetooth-over-ear-cancelamento-de-ruido-anc-bateria-60h']);
        $response = $controller->singleProduct($request, 'headphone-bluetooth-over-ear-cancelamento-de-ruido-anc-bateria-60h');

        $this->assertSame(200, $response->getStatusCode(), 'Produto curado deve retornar HTTP 200 em vez de 404');
        $this->assertStringContainsString('Headphone Bluetooth Over-Ear', $response->getContent());
        $this->assertStringNotContainsString('404 - Produto não encontrado', $response->getContent());

        // Agora o produto DEVE ter sido persistido no banco de dados SQLite/MySQL
        $this->assertSame(1, $this->catalogRepo->count(), 'Produto deve ter sido auto-persistido no banco de dados');
        $savedProduct = $this->catalogRepo->findBySlug('headphone-bluetooth-over-ear-cancelamento-de-ruido-anc-bateria-60h');
        $this->assertNotNull($savedProduct);
        $this->assertStringContainsString('photo-1505740420928', $savedProduct->mainImageUrl);
    }

    public function testGraceful404WhenProductDoesNotExistAnywhere(): void
    {
        $miningService = $this->createMiningService();

        $controller = new DropshipFrontController(
            catalogRepo: $this->catalogRepo,
            themeManager: null,
            miningService: $miningService
        );

        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/produto/item-inexistente-xyz-999']);
        $response = $controller->singleProduct($request, 'item-inexistente-xyz-999');

        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringContainsString('Produto Indisponível no Momento', $response->getContent());
        $this->assertStringContainsString('Ver Todas as Ofertas', $response->getContent());
    }
}
