<?php

namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DomainSystem\Core\Container\Container;
use DomainSystem\SystemApps\SystemAdmin\Controllers\AdminController;
use DomainSystem\Core\Theme\ThemeManager;

class AdminControllerTest extends TestCase
{
    private string $tempThemesPath;

    protected function setUp(): void
    {
        $this->tempThemesPath = sys_get_temp_dir() . '/domain_system_themes_' . uniqid();
        mkdir($this->tempThemesPath);
        mkdir($this->tempThemesPath . '/admin');
        
        file_put_contents(
            $this->tempThemesPath . '/admin/layout.php', 
            '<html><?= $content ?? "" ?></html>'
        );

        file_put_contents(
            $this->tempThemesPath . '/admin/dashboard.php', 
            '<?php ob_start(); ?><h1>Dashboard</h1><?php $content = ob_get_clean(); require __DIR__ . "/layout.php"; ?>'
        );

        file_put_contents(
            $this->tempThemesPath . '/admin/plugins.php', 
            '<?php ob_start(); foreach($plugins as $p) { echo $p["name"] . ":" . ($p["is_active"] ? "1" : "0") . ";"; } $content = ob_get_clean(); require __DIR__ . "/layout.php"; ?>'
        );
    }

    protected function tearDown(): void
    {
        unlink($this->tempThemesPath . '/admin/layout.php');
        unlink($this->tempThemesPath . '/admin/dashboard.php');
        unlink($this->tempThemesPath . '/admin/plugins.php');
        rmdir($this->tempThemesPath . '/admin');
        rmdir($this->tempThemesPath);
    }

        public function testListPlugins()
    {
        $themeManager = new \DomainSystem\Core\Theme\ThemeManager($this->tempThemesPath . '/admin');
        
        $pluginManagerMock = $this->createMock(\DomainSystem\Core\Plugin\PluginManager::class);
        $pluginManagerMock->method('getPlugins')->willReturn([]);

        $shortcodesMock = $this->createMock(\DomainSystem\Core\Theme\ShortcodeManager::class);
        $dispatcherMock = $this->createMock(\DomainSystem\Core\Contracts\EventDispatcherInterface::class);
        $dispatcherMock->method('applyFilters')->willReturnCallback(function($hook, $val) { return $val; });

        $requestMock = $this->createMock(\DomainSystem\Core\Http\Request::class);

        $sessionMock = $this->createMock(\DomainSystem\Core\Contracts\SessionManagerInterface::class);
        $controller = new AdminController($pluginManagerMock, $themeManager, $shortcodesMock, $dispatcherMock, $sessionMock);
        $html = $controller->listPlugins($requestMock);
        
        $this->assertStringContainsString('<html>', $html);
    }

    public function testUpdatePluginRemoteSuccessSetsFlashAndRedirects(): void
    {
        $themeManager = new \DomainSystem\Core\Theme\ThemeManager($this->tempThemesPath . '/admin');
        $pluginManagerMock = $this->createMock(\DomainSystem\Core\Plugin\PluginManager::class);
        $shortcodesMock = $this->createMock(\DomainSystem\Core\Theme\ShortcodeManager::class);
        $dispatcherMock = $this->createMock(\DomainSystem\Core\Contracts\EventDispatcherInterface::class);
        $dispatcherMock->method('applyFilters')->willReturnArgument(1);
        $sessionMock = $this->createMock(\DomainSystem\Core\Contracts\SessionManagerInterface::class);

        $checkerMock = $this->createMock(\DomainSystem\Core\Contracts\PluginUpdateCheckerInterface::class);
        $checkerMock->expects($this->once())
            ->method('updatePluginFromRemote')
            ->with('domain_drop')
            ->willReturn(['success' => true, 'version' => '2.8.3']);

        $sessionMock->expects($this->once())
            ->method('setFlash')
            ->with('success', $this->stringContains('domain_drop'));

        $request = new \DomainSystem\Core\Http\Request([], ['plugin_slug' => 'domain_drop'], ['REQUEST_METHOD' => 'POST']);

        $controller = new AdminController(
            $pluginManagerMock,
            $themeManager,
            $shortcodesMock,
            $dispatcherMock,
            $sessionMock,
            null,
            null,
            $checkerMock
        );

        $response = $controller->updatePluginRemote($request);
        $this->assertInstanceOf(\DomainSystem\Core\Http\Response::class, $response);
        $this->assertSame(302, $response->getStatusCode());
    }

    public function testCheckPluginUpdatesSetsFlashAndRedirects(): void
    {
        $themeManager = new \DomainSystem\Core\Theme\ThemeManager($this->tempThemesPath . '/admin');
        $pluginManagerMock = $this->createMock(\DomainSystem\Core\Plugin\PluginManager::class);
        $shortcodesMock = $this->createMock(\DomainSystem\Core\Theme\ShortcodeManager::class);
        $dispatcherMock = $this->createMock(\DomainSystem\Core\Contracts\EventDispatcherInterface::class);
        $dispatcherMock->method('applyFilters')->willReturnArgument(1);
        $sessionMock = $this->createMock(\DomainSystem\Core\Contracts\SessionManagerInterface::class);

        $checkerMock = $this->createMock(\DomainSystem\Core\Contracts\PluginUpdateCheckerInterface::class);
        $checkerMock->expects($this->once())
            ->method('checkForUpdates')
            ->with(true)
            ->willReturn(['commerce' => ['has_update' => true]]);
        $checkerMock->expects($this->once())
            ->method('notifyIfUpdatesAvailable')
            ->with(true);

        $sessionMock->expects($this->once())
            ->method('setFlash')
            ->with('success', $this->stringContains('1 atualização(ões)'));

        $request = new \DomainSystem\Core\Http\Request([], [], ['REQUEST_METHOD' => 'POST']);

        $controller = new AdminController(
            $pluginManagerMock,
            $themeManager,
            $shortcodesMock,
            $dispatcherMock,
            $sessionMock,
            null,
            null,
            $checkerMock
        );

        $response = $controller->checkPluginUpdates($request);
        $this->assertInstanceOf(\DomainSystem\Core\Http\Response::class, $response);
        $this->assertSame(302, $response->getStatusCode());
    }

    public function testListPluginsOnlyEnablesHasUpdateWhenNewerVersionExists(): void
    {
        $themeManagerMock = $this->createMock(\DomainSystem\Core\Theme\ThemeManager::class);
        $pluginManagerMock = $this->createMock(\DomainSystem\Core\Plugin\PluginManager::class);
        $shortcodesMock = $this->createMock(\DomainSystem\Core\Theme\ShortcodeManager::class);
        $dispatcherMock = $this->createMock(\DomainSystem\Core\Contracts\EventDispatcherInterface::class);
        $dispatcherMock->method('applyFilters')->willReturnArgument(1);
        $sessionMock = $this->createMock(\DomainSystem\Core\Contracts\SessionManagerInterface::class);
        $checkerMock = $this->createMock(\DomainSystem\Core\Contracts\PluginUpdateCheckerInterface::class);

        // When a plugin has has_update => false, listPlugins must NOT set has_update = true
        $checkerMock->method('checkForUpdates')->willReturn([
            'commerce' => [
                'has_update' => false,
                'latest_version' => '2.8.3',
                'new_version' => '2.8.3',
                'download_url' => 'https://example.com/commerce.zip'
            ]
        ]);

        $themeManagerMock->expects($this->once())
            ->method('render')
            ->with(
                'plugins',
                $this->callback(function (array $data) {
                    $plugins = $data['plugins'];
                    $this->assertSame(0, $data['pendingUpdatesCount']);

                    foreach ($plugins as $p) {
                        if ($p['folder'] === 'commerce') {
                            $this->assertFalse($p['has_update'], 'Plugin sem versão superior deve ter has_update = false');
                        }
                    }
                    return true;
                })
            )
            ->willReturn('<html>rendered plugins</html>');

        $controller = new AdminController(
            $pluginManagerMock,
            $themeManagerMock,
            $shortcodesMock,
            $dispatcherMock,
            $sessionMock,
            null,
            null,
            $checkerMock
        );

        $request = new \DomainSystem\Core\Http\Request([], [], ['REQUEST_METHOD' => 'GET']);
        $html = $controller->listPlugins($request);
        $this->assertSame('<html>rendered plugins</html>', $html);
    }
}


