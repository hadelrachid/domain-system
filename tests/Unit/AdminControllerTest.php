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

        $controller = new AdminController($pluginManagerMock, $themeManager, $shortcodesMock, $dispatcherMock);
        $html = $controller->listPlugins($requestMock);
        
        $this->assertStringContainsString('<html>', $html);
    }

    }


