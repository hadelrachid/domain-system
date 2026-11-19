<?php

declare(strict_types=1);

namespace Tests\Unit;

use DomainSystem\Core\Http\Request;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipCatalogRepositoryInterface;
use DomainSystem\Plugins\domain_drop\Controllers\DropshipFrontController;
use DomainSystem\Plugins\domain_drop\DTO\DropshipProductDto;
use DomainSystem\Plugins\domain_drop\Services\ProductMediaProxyService;
use PHPUnit\Framework\TestCase;

class DomainDropMediaProtectionTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/domain_drop_test_media_' . uniqid();
        @mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->removeDirectory($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    public function testDropshipProductDtoGeneratesMaskedLocalMediaUrls(): void
    {
        $product = new DropshipProductDto(
            aliexpressId: '1005001234567890',
            title: 'Smartwatch Curadoria Teste',
            slug: 'smartwatch-curadoria-teste',
            costPriceUsd: 15.0,
            salePriceBrl: 150.0,
            mainImageUrl: 'https://ae01.alicdn.com/kf/S8550186591034444b94f.jpg',
            images: [
                'https://ae01.alicdn.com/kf/S8550186591034444b94f.jpg',
                'https://ae-pic-a1.aliexpress-media.com/kf/S123456789.png',
            ],
            category: 'Smartwatches',
            id: 42
        );

        $protectedMain = $product->getProtectedMainImageUrl('https://rachidd.com');
        $this->assertStringStartsWith('https://rachidd.com/dropship/media/42/', $protectedMain);
        $this->assertStringEndsWith('.jpg', $protectedMain);
        $this->assertStringNotContainsString('alicdn.com', $protectedMain);
        $this->assertStringNotContainsString('aliexpress', $protectedMain);

        $protectedGallery = $product->getProtectedImages('https://rachidd.com');
        $this->assertCount(2, $protectedGallery);
        foreach ($protectedGallery as $img) {
            $this->assertStringStartsWith('https://rachidd.com/dropship/media/42/', $img);
            $this->assertStringNotContainsString('alicdn.com', $img);
        }
    }

    public function testProductMediaProxyServiceFindsOriginalUrlByToken(): void
    {
        $rawMain = 'https://ae01.alicdn.com/kf/S8550186591034444b94f.jpg';
        $rawGallery = 'https://ae-pic-a1.aliexpress-media.com/kf/S123456789.png';
        $rawVariant = 'https://ae01.alicdn.com/kf/Svariant0001.jpg';

        $product = new DropshipProductDto(
            aliexpressId: '1005001234567890',
            title: 'Produto Teste',
            slug: 'produto-teste',
            costPriceUsd: 10.0,
            salePriceBrl: 100.0,
            mainImageUrl: $rawMain,
            images: [$rawMain, $rawGallery],
            attributes: [
                'variants' => [
                    ['sku_id' => 'SKU-01', 'name' => 'Preto', 'image_url' => $rawVariant]
                ]
            ],
            id: 10
        );

        $service = new ProductMediaProxyService(null, $this->tempDir);

        $mainToken = $service->generateToken($rawMain);
        $galleryToken = $service->generateToken($rawGallery);
        $variantToken = $service->generateToken($rawVariant);

        $this->assertSame($rawMain, $service->findUrlByToken($product, $mainToken));
        $this->assertSame($rawGallery, $service->findUrlByToken($product, $galleryToken));
        $this->assertSame($rawVariant, $service->findUrlByToken($product, $variantToken));
        $this->assertNull($service->findUrlByToken($product, 'inexistente_hash'));
    }

    public function testProductMediaProxyServiceServesCachedFileFromDisk(): void
    {
        $productId = 15;
        $token = 'abc123def4567890';
        $productDir = $this->tempDir . DIRECTORY_SEPARATOR . $productId;
        @mkdir($productDir, 0755, true);

        $fakeJpg = str_repeat('A', 500); // 500 bytes de imagem simulada
        file_put_contents($productDir . DIRECTORY_SEPARATOR . $token . '.jpg', $fakeJpg);

        $service = new ProductMediaProxyService(null, $this->tempDir);
        $response = $service->resolveAndServe($productId, $token);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('image/jpeg', $response->getHeader('Content-Type'));
        $this->assertSame($fakeJpg, $response->getContent());
        $this->assertStringContainsString('max-age=31536000', (string)$response->getHeader('Cache-Control'));
    }

    public function testProductMediaProxyServiceReturnsSvgPlaceholderWhenSourceUnavailable(): void
    {
        $repo = $this->createMock(DropshipCatalogRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $service = new ProductMediaProxyService($repo, $this->tempDir);
        $response = $service->resolveAndServe(999, 'token_que_nao_existe');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('image/svg+xml', (string)$response->getHeader('Content-Type'));
        $this->assertStringContainsString('RachidD Curadoria Verificada', (string)$response->getContent());
        $this->assertStringNotContainsString('aliexpress', (string)$response->getContent());
    }

    public function testDropshipFrontControllerDelegatesToMediaProxy(): void
    {
        $repo = $this->createMock(DropshipCatalogRepositoryInterface::class);
        $proxy = new ProductMediaProxyService($repo, $this->tempDir);

        $controller = new DropshipFrontController(
            catalogRepo: $repo,
            mediaProxy: $proxy
        );

        $req = new Request([], [], ['REQUEST_URI' => '/dropship/media/1/testtoken.jpg']);
        $res = $controller->serveMedia($req, '1', 'testtoken.jpg');

        $this->assertSame(200, $res->getStatusCode());
        $this->assertNotNull($res->getContent());
    }
}
