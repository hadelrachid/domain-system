<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Core\Contracts\NotificationManagerInterface;
use DomainSystem\Core\Theme\ThemeUpdateChecker;
use PHPUnit\Framework\TestCase;

class ThemeUpdateCheckerTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/ds_theme_update_test_' . uniqid();
        @mkdir($this->tempDir . '/themes', 0777, true);
        @mkdir($this->tempDir . '/temp/cache', 0777, true);
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
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    public function testCheckForUpdatesDetectsNewerVersionFromMarketplace(): void
    {
        // Setup local theme
        $themeDir = $this->tempDir . '/themes/rachidd_store';
        @mkdir($themeDir, 0777, true);
        file_put_contents($themeDir . '/theme.json', json_encode([
            'name' => 'rachidd_store',
            'version' => '1.0.0'
        ]));

        // Setup mock marketplace file
        $marketplaceFile = $this->tempDir . '/marketplace_themes.json';
        file_put_contents($marketplaceFile, json_encode([
            'themes' => [
                'rachidd_store' => [
                    'name' => 'rachidd_store',
                    'title' => 'RachidD Store',
                    'version' => '1.0.1',
                    'download_url' => 'https://example.com/rachidd_store.zip'
                ]
            ]
        ]));

        $checker = new ThemeUpdateChecker(
            $this->tempDir,
            null,
            $marketplaceFile,
            0 // sem cache para o teste
        );

        $updates = $checker->checkForUpdates(true);

        $this->assertArrayHasKey('rachidd_store', $updates);
        $this->assertTrue($updates['rachidd_store']['has_update']);
        $this->assertSame('1.0.0', $updates['rachidd_store']['installed_version']);
        $this->assertSame('1.0.1', $updates['rachidd_store']['latest_version']);
    }

    public function testCheckForUpdatesReportsNoUpdateWhenVersionsMatch(): void
    {
        $themeDir = $this->tempDir . '/themes/rachidd_store';
        @mkdir($themeDir, 0777, true);
        file_put_contents($themeDir . '/theme.json', json_encode([
            'name' => 'rachidd_store',
            'version' => '1.0.0'
        ]));

        $marketplaceFile = $this->tempDir . '/marketplace_themes.json';
        file_put_contents($marketplaceFile, json_encode([
            'themes' => [
                'rachidd_store' => [
                    'name' => 'rachidd_store',
                    'version' => '1.0.0',
                    'download_url' => 'https://example.com/rachidd_store.zip'
                ]
            ]
        ]));

        $checker = new ThemeUpdateChecker($this->tempDir, null, $marketplaceFile, 0);
        $updates = $checker->checkForUpdates(true);

        $this->assertArrayHasKey('rachidd_store', $updates);
        $this->assertFalse($updates['rachidd_store']['has_update']);
        $this->assertSame(0, $checker->getPendingUpdatesCount(true));
    }

    public function testNotifyIfUpdatesAvailablePushesNotification(): void
    {
        $themeDir = $this->tempDir . '/themes/rachidd_store';
        @mkdir($themeDir, 0777, true);
        file_put_contents($themeDir . '/theme.json', json_encode([
            'name' => 'rachidd_store',
            'version' => '1.0.0'
        ]));

        $marketplaceFile = $this->tempDir . '/marketplace_themes.json';
        file_put_contents($marketplaceFile, json_encode([
            'themes' => [
                'rachidd_store' => [
                    'name' => 'rachidd_store',
                    'version' => '1.1.0',
                    'download_url' => 'https://example.com/rachidd_store.zip'
                ]
            ]
        ]));

        $notifications = $this->createMock(NotificationManagerInterface::class);
        $notifications->expects($this->once())
            ->method('push')
            ->with(
                $this->stringContains('Atualização de Tema disponível'),
                'info',
                'Theme Updater',
                $this->callback(fn($data) => ($data['url'] ?? '') === '/admin/themes')
            );

        $checker = new ThemeUpdateChecker($this->tempDir, $notifications, $marketplaceFile, 0);
        $checker->notifyIfUpdatesAvailable(true);
    }

    public function testUpdateThemeFromRemoteRejectsSystemThemes(): void
    {
        $checker = new ThemeUpdateChecker($this->tempDir);
        $result = $checker->updateThemeFromRemote('admin');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Segurança do Sistema', $result['message']);
    }
}
