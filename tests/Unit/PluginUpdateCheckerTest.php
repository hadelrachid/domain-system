<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Core\Contracts\NotificationManagerInterface;
use DomainSystem\Core\Plugin\Services\PluginUpdateChecker;
use PHPUnit\Framework\TestCase;

class PluginUpdateCheckerTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/ds_plugin_update_test_' . uniqid();
        @mkdir($this->tempDir . '/src/Plugins', 0777, true);
        @mkdir($this->tempDir . '/src/SystemApps', 0777, true);
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
        // Setup local plugin
        $pluginDir = $this->tempDir . '/src/Plugins/domain_drop';
        @mkdir($pluginDir, 0777, true);
        file_put_contents($pluginDir . '/plugin.json', json_encode([
            'name' => 'DomainDrop',
            'version' => '2.8.2',
            'core' => false,
        ]));

        // Setup mock marketplace
        $marketplaceFile = $this->tempDir . '/marketplace.json';
        file_put_contents($marketplaceFile, json_encode([
            'version' => '2.8.3',
            'plugins' => [
                'domain_drop' => [
                    'name' => 'DomainDrop',
                    'version' => '2.8.3',
                    'download_url' => 'https://example.com/domain_drop.zip',
                    'sha256' => 'abc123hash',
                    'size_bytes' => 1024,
                    'description' => 'Módulo AliExpress Dropshipping'
                ]
            ]
        ]));

        $checker = new PluginUpdateChecker(
            $this->tempDir,
            null,
            $marketplaceFile
        );

        $updates = $checker->checkForUpdates(true);

        $this->assertArrayHasKey('domain_drop', $updates);
        $this->assertTrue($updates['domain_drop']['has_update']);
        $this->assertSame('2.8.2', $updates['domain_drop']['installed_version']);
        $this->assertSame('2.8.3', $updates['domain_drop']['latest_version']);
        $this->assertSame('2.8.3', $updates['domain_drop']['new_version']);
        $this->assertSame(1, $checker->getPendingUpdatesCount(false));
    }

    public function testCheckForUpdatesIgnoresCorePlugins(): void
    {
        // Setup core plugin
        $pluginDir = $this->tempDir . '/src/Plugins/nobreak_shield';
        @mkdir($pluginDir, 0777, true);
        file_put_contents($pluginDir . '/plugin.json', json_encode([
            'name' => 'No-Break Shield',
            'version' => '2.8.0',
            'core' => true,
        ]));

        // Setup mock marketplace with a newer version
        $marketplaceFile = $this->tempDir . '/marketplace.json';
        file_put_contents($marketplaceFile, json_encode([
            'version' => '2.8.3',
            'plugins' => [
                'nobreak_shield' => [
                    'name' => 'No-Break Shield',
                    'version' => '2.8.3',
                    'download_url' => 'https://example.com/nobreak_shield.zip'
                ]
            ]
        ]));

        $checker = new PluginUpdateChecker(
            $this->tempDir,
            null,
            $marketplaceFile
        );

        $updates = $checker->checkForUpdates(true);

        $this->assertArrayNotHasKey('nobreak_shield', $updates);
        $this->assertSame(0, $checker->getPendingUpdatesCount(false));
    }

    public function testNotifyIfUpdatesAvailablePushesNotification(): void
    {
        $pluginDir = $this->tempDir . '/src/Plugins/commerce';
        @mkdir($pluginDir, 0777, true);
        file_put_contents($pluginDir . '/plugin.json', json_encode([
            'name' => 'Commerce',
            'version' => '2.8.0',
            'core' => false,
        ]));

        $marketplaceFile = $this->tempDir . '/marketplace.json';
        file_put_contents($marketplaceFile, json_encode([
            'version' => '2.8.3',
            'plugins' => [
                'commerce' => [
                    'name' => 'Commerce',
                    'version' => '2.8.3',
                    'download_url' => 'https://example.com/commerce.zip'
                ]
            ]
        ]));

        $notifications = $this->createMock(NotificationManagerInterface::class);
        $notifications->expects($this->once())
            ->method('push')
            ->with(
                $this->stringContains('Atualização disponível para 1 plugin(s)'),
                'info',
                'Plugin Updater'
            );

        $checker = new PluginUpdateChecker(
            $this->tempDir,
            $notifications,
            $marketplaceFile
        );

        $checker->notifyIfUpdatesAvailable(true);
        $this->assertSame(1, $checker->getPendingUpdatesCount(false));
    }

    public function testUpdatePluginFromRemoteRejectsSystemAppsAndCorePlugins(): void
    {
        // 1. SystemApp
        $sysDir = $this->tempDir . '/src/SystemApps/SystemAdmin';
        @mkdir($sysDir, 0777, true);

        $checker = new PluginUpdateChecker($this->tempDir);
        $resSys = $checker->updatePluginFromRemote('SystemAdmin');
        $this->assertFalse($resSys['success']);
        $this->assertStringContainsString('SystemApps', $resSys['message']);

        // 2. Core Plugin
        $coreDir = $this->tempDir . '/src/Plugins/auth';
        @mkdir($coreDir, 0777, true);
        file_put_contents($coreDir . '/plugin.json', json_encode([
            'name' => 'auth',
            'version' => '2.8.3',
            'core' => true,
        ]));

        $resCore = $checker->updatePluginFromRemote('auth');
        $this->assertFalse($resCore['success']);
        $this->assertStringContainsString('Módulos Core vitais', $resCore['message']);
    }

    public function testUpdatePluginFromRemoteValidatesSha256Checksum(): void
    {
        // Create an aggregated plugin
        $pluginDir = $this->tempDir . '/src/Plugins/seo';
        @mkdir($pluginDir, 0777, true);
        file_put_contents($pluginDir . '/plugin.json', json_encode([
            'name' => 'seo',
            'version' => '2.8.0',
            'core' => false,
        ]));

        // Create a dummy zip file
        $zipFile = $this->tempDir . '/dummy_seo.zip';
        file_put_contents($zipFile, str_repeat('A', 200));

        // Marketplace with deliberately mismatching hash
        $marketplaceFile = $this->tempDir . '/marketplace.json';
        file_put_contents($marketplaceFile, json_encode([
            'version' => '2.8.3',
            'plugins' => [
                'seo' => [
                    'name' => 'seo',
                    'version' => '2.8.3',
                    'download_url' => $zipFile,
                    'sha256' => 'expected_hash_that_does_not_match',
                ]
            ]
        ]));

        $checker = new PluginUpdateChecker(
            $this->tempDir,
            null,
            $marketplaceFile
        );

        $result = $checker->updatePluginFromRemote('seo');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Falha de integridade: SHA-256', $result['message']);
    }

    public function testUpdatePluginFromRemoteRejectsWhenAlreadyAtLatestVersion(): void
    {
        $pluginDir = $this->tempDir . '/src/Plugins/academy';
        @mkdir($pluginDir, 0777, true);
        file_put_contents($pluginDir . '/plugin.json', json_encode([
            'name' => 'academy',
            'version' => '2.8.3',
            'core' => false,
        ]));

        $marketplaceFile = $this->tempDir . '/marketplace.json';
        file_put_contents($marketplaceFile, json_encode([
            'version' => '2.8.3',
            'plugins' => [
                'academy' => [
                    'name' => 'academy',
                    'version' => '2.8.3',
                    'download_url' => 'https://example.com/academy.zip',
                    'sha256' => 'somehash',
                ]
            ]
        ]));

        $checker = new PluginUpdateChecker(
            $this->tempDir,
            null,
            $marketplaceFile
        );

        $result = $checker->updatePluginFromRemote('academy');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('já está na versão mais recente', $result['message']);
    }
}
