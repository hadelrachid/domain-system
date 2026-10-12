<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DomainSystem\Core\Bootstrap\RuntimeDirectories;

class RuntimeDirectoriesTest extends TestCase
{
    private string $testRoot;

    protected function setUp(): void
    {
        $this->testRoot = sys_get_temp_dir() . '/ds_runtime_test_' . bin2hex(random_bytes(6));
        mkdir($this->testRoot, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->testRoot);
    }

    public function testEnsureCreatesRequiredDirectoriesAndSecurityHtaccess(): void
    {
        $this->assertDirectoryDoesNotExist($this->testRoot . '/temp');
        $this->assertDirectoryDoesNotExist($this->testRoot . '/storage');
        $this->assertDirectoryDoesNotExist($this->testRoot . '/public/uploads');

        RuntimeDirectories::ensure($this->testRoot);

        // Verifica diretórios essenciais
        $this->assertDirectoryExists($this->testRoot . '/temp');
        $this->assertDirectoryExists($this->testRoot . '/temp/cache');
        $this->assertDirectoryExists($this->testRoot . '/temp/logs');
        $this->assertDirectoryExists($this->testRoot . '/storage');
        $this->assertDirectoryExists($this->testRoot . '/storage/state');
        $this->assertDirectoryExists($this->testRoot . '/public/uploads');
        $this->assertDirectoryExists($this->testRoot . '/public/uploads/academy');

        // Verifica segurança .htaccess nas pastas internas
        $tempHtaccess = $this->testRoot . '/temp/.htaccess';
        $this->assertFileExists($tempHtaccess);
        $this->assertStringContainsString('Deny from all', file_get_contents($tempHtaccess));

        $storageHtaccess = $this->testRoot . '/storage/.htaccess';
        $this->assertFileExists($storageHtaccess);
        $this->assertStringContainsString('Deny from all', file_get_contents($storageHtaccess));
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
