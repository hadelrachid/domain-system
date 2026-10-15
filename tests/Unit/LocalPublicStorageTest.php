<?php

declare(strict_types=1);

namespace Tests\Unit;

use DomainSystem\Core\Contracts\PublicStorageInterface;
use DomainSystem\Core\Storage\LocalPublicStorage;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class LocalPublicStorageTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ds_storage_test_' . bin2hex(random_bytes(6));
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->removeDirectory($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    public function testGetUploadPathResolvesPublicFolderWhenPresent(): void
    {
        $publicDir = $this->tempDir . DIRECTORY_SEPARATOR . 'public';
        mkdir($publicDir, 0755, true);

        $storage = new LocalPublicStorage($this->tempDir, '/domain-system');
        $uploadPath = $storage->getUploadPath('academy/test.png');

        $expected = $publicDir . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'academy' . DIRECTORY_SEPARATOR . 'test.png';
        $this->assertSame($expected, $uploadPath);
        $this->assertDirectoryExists(dirname($uploadPath));
    }

    public function testGetUploadPathResolvesRootUploadsWhenNoPublicFolder(): void
    {
        $storage = new LocalPublicStorage($this->tempDir, '');
        $uploadPath = $storage->getUploadPath('test.png');

        $expected = $this->tempDir . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'test.png';
        $this->assertSame($expected, $uploadPath);
        $this->assertDirectoryExists(dirname($uploadPath));
    }

    public function testGetUploadPathNormalizesUploadsPrefix(): void
    {
        $storage = new LocalPublicStorage($this->tempDir);
        $path1 = $storage->getUploadPath('uploads/academy/photo.png');
        $path2 = $storage->getUploadPath('academy/photo.png');

        $this->assertSame($path1, $path2);
    }

    public function testDirectoryTraversalThrowsException(): void
    {
        $storage = new LocalPublicStorage($this->tempDir);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tentativa de travessia');
        $storage->getUploadPath('../secret.env');
    }

    public function testGetPublicUrlFormatsCorrectly(): void
    {
        // 1. Com BaseUrl (ex: XAMPP)
        $storageWithBase = new LocalPublicStorage($this->tempDir, '/domain-system');
        $url1 = $storageWithBase->getPublicUrl('uploads/academy/card.png');
        $this->assertSame('/domain-system/uploads/academy/card.png', $url1);

        // Subpath sem 'uploads/'
        $url2 = $storageWithBase->getPublicUrl('academy/card.png');
        $this->assertSame('/domain-system/uploads/academy/card.png', $url2);

        // 2. Sem BaseUrl (ex: Hostinger / Produção)
        $storageNoBase = new LocalPublicStorage($this->tempDir, '');
        $url3 = $storageNoBase->getPublicUrl('uploads/academy/card.png');
        $this->assertSame('/uploads/academy/card.png', $url3);

        // 3. URLs externas
        $externalHttp = 'http://example.com/images/foto.jpg';
        $this->assertSame($externalHttp, $storageWithBase->getPublicUrl($externalHttp));

        $externalHttps = 'https://cdn.site.com/foto.webp';
        $this->assertSame($externalHttps, $storageWithBase->getPublicUrl($externalHttps));

        $dataUri = 'data:image/svg+xml;base64,PHN2Zz4=';
        $this->assertSame($dataUri, $storageWithBase->getPublicUrl($dataUri));
    }

    public function testGetPublicUrlSupportsDynamicCallableBaseUrl(): void
    {
        $currentBase = '';
        $storage = new LocalPublicStorage($this->tempDir, function() use (&$currentBase) {
            return $currentBase;
        });

        $this->assertSame('/uploads/photo.jpg', $storage->getPublicUrl('uploads/photo.jpg'));

        $currentBase = '/subfolder';
        $this->assertSame('/subfolder/uploads/photo.jpg', $storage->getPublicUrl('uploads/photo.jpg'));
    }

    public function testPutExistsAndDeleteOperations(): void
    {
        $storage = new LocalPublicStorage($this->tempDir);

        $this->assertFalse($storage->exists('academy/document.txt'));

        $relativePath = $storage->put('academy/document.txt', 'Conteudo de teste');
        $this->assertSame('uploads/academy/document.txt', $relativePath);
        $this->assertTrue($storage->exists('academy/document.txt'));
        $this->assertTrue($storage->exists('uploads/academy/document.txt'));

        $deleted = $storage->delete('uploads/academy/document.txt');
        $this->assertTrue($deleted);
        $this->assertFalse($storage->exists('academy/document.txt'));
    }

    public function testContainerAndLinkRegistryResolvePublicStorage(): void
    {
        $container = new \DomainSystem\Core\Container\Container();
        $dispatcher = new \DomainSystem\Core\Events\EventDispatcher();
        $provider = new \DomainSystem\Core\Providers\CoreServiceProvider();
        $provider->register($container, $dispatcher, $this->tempDir);

        $storageFromContainer = $container->make(PublicStorageInterface::class);
        $this->assertInstanceOf(PublicStorageInterface::class, $storageFromContainer);

        $linkRegistry = new \DomainSystem\Core\Plugin\LinkRegistry($container);
        $storageFromLink = $linkRegistry->resolve('core.storage');
        $this->assertInstanceOf(PublicStorageInterface::class, $storageFromLink);
        $this->assertSame($storageFromContainer, $storageFromLink);
    }
}
