<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Plugins\flextheme\Services\MediaUploadService;
use PHPUnit\Framework\TestCase;

class MediaUploadServiceTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/ds_upload_test_' . uniqid('', true);
        @mkdir($this->tempDir . '/public/uploads/builder', 0755, true);
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
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    public function testUploadRejectsWhenNoFileOrUploadError(): void
    {
        $service = new MediaUploadService($this->tempDir, '/domain-system/public');
        $result = $service->upload(['error' => UPLOAD_ERR_NO_FILE]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Nenhum arquivo', $result['message']);
    }

    public function testUploadRejectsDisallowedExtension(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'test_php_');
        file_put_contents($tmp, '<?php phpinfo();');

        $service = new MediaUploadService($this->tempDir, '/domain-system/public');
        $result = $service->upload([
            'name'     => 'malicious.php',
            'type'     => 'application/x-php',
            'tmp_name' => $tmp,
            'error'    => UPLOAD_ERR_OK,
            'size'     => filesize($tmp),
        ]);

        @unlink($tmp);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Extensão não permitida', $result['message']);
    }

    public function testUploadSucceedsForValidPngImage(): void
    {
        // 1x1 transparent PNG bytes
        $pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        $tmp = tempnam(sys_get_temp_dir(), 'test_png_');
        file_put_contents($tmp, $pngBytes);

        $service = new MediaUploadService($this->tempDir, '/domain-system/public');
        $result = $service->upload([
            'name'     => 'logo.png',
            'type'     => 'image/png',
            'tmp_name' => $tmp,
            'error'    => UPLOAD_ERR_OK,
            'size'     => strlen($pngBytes),
        ]);

        @unlink($tmp);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('url', $result);
        $this->assertStringStartsWith('/domain-system/public/uploads/builder/', $result['url']);
        $this->assertFileExists($this->tempDir . '/public/uploads/builder/' . $result['filename']);
    }

    public function testUploadRejectsFileExceedingMaxSize(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'test_big_');
        file_put_contents($tmp, 'small');

        // Cria serviço com limite de 10 bytes
        $service = new MediaUploadService($this->tempDir, '/domain-system/public', 10);
        $result = $service->upload([
            'name'     => 'big.png',
            'type'     => 'image/png',
            'tmp_name' => $tmp,
            'error'    => UPLOAD_ERR_OK,
            'size'     => 1024, // 1KB > 10 bytes
        ]);

        @unlink($tmp);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('limite máximo permitido', $result['message']);
    }
}
