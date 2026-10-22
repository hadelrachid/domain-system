<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Plugins\flextheme\Services\ThemeDiscoveryService;
use PHPUnit\Framework\TestCase;

class ThemeDiscoveryServiceTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/ds_theme_discovery_' . uniqid('', true);
        @mkdir($this->tempDir . '/themes/rachidd', 0755, true);
        @mkdir($this->tempDir . '/themes/admin', 0755, true); // deve ser ignorado
        @mkdir($this->tempDir . '/config', 0755, true);

        file_put_contents($this->tempDir . '/themes/rachidd/theme.json', json_encode([
            'name' => 'Rachid Theme Pro',
        ]));

        file_put_contents($this->tempDir . '/config/active_theme.json', json_encode([
            'active' => 'rachidd',
        ]));
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

    public function testGetAvailableThemesDiscoversThemesAndSkipsAdmin(): void
    {
        $service = new ThemeDiscoveryService($this->tempDir);
        $themes = $service->getAvailableThemes();

        $this->assertCount(1, $themes);
        $this->assertSame('rachidd', $themes[0]['folder']);
        $this->assertSame('Rachid Theme Pro', $themes[0]['name']);
    }

    public function testGetActiveThemeFolderResolvesConfiguredTheme(): void
    {
        $service = new ThemeDiscoveryService($this->tempDir);
        $this->assertSame('rachidd', $service->getActiveThemeFolder());
    }

    public function testGetActiveThemeFolderReturnsDefaultWhenMissing(): void
    {
        @unlink($this->tempDir . '/config/active_theme.json');
        $service = new ThemeDiscoveryService($this->tempDir);
        $this->assertSame('default', $service->getActiveThemeFolder());
    }
}
