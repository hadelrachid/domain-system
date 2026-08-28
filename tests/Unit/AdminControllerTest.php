<?php

namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DomainSystem\Core\Container\Container;
use DomainSystem\Plugins\SystemAdmin\Controllers\AdminController;
use DomainSystem\Plugins\SystemAdmin\Controllers\DashboardController;
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
        $container = new Container();
        $themeManager = new ThemeManager($this->tempThemesPath);
        
        $events = new \DomainSystem\Core\Events\EventDispatcher();
        $pluginManager = new \DomainSystem\Core\Plugin\PluginManager($container, $events);
        
        // Mock the Request object since listPlugins now takes a Request argument
        $requestMock = $this->createMock(\DomainSystem\Core\Http\Request::class);

        $controller = new AdminController($pluginManager, $themeManager);
        $html = $controller->listPlugins($requestMock);
        
        $this->assertStringContainsString('database', $html);
        $this->assertStringContainsString('system-admin', $html);
        $this->assertStringContainsString('<html>', $html); // via layout
    }

    public function testDashboard()
    {
        $container = new Container();
        $themeManager = new ThemeManager($this->tempThemesPath);
        
        // Mock DashboardRepositoryInterface
        $repoMock = $this->createMock(\DomainSystem\Plugins\SystemAdmin\Contracts\DashboardRepositoryInterface::class);
        $repoMock->method('getGlobalStats')->willReturn(['totalPatients' => 10, 'totalDoctors' => 5, 'appointmentsToday' => 2]);
        $repoMock->method('getGlobalQueue')->willReturn([]);
        $repoMock->method('getWaitingRoom')->willReturn([]);
        $repoMock->method('getAppointmentsChartData')->willReturn([]);

        $requestMock = $this->createMock(\DomainSystem\Core\Http\Request::class);

        $controller = new DashboardController($themeManager, $repoMock);
        $html = $controller->index($requestMock);
        
        $this->assertStringContainsString('Dashboard', $html);
        $this->assertStringContainsString('<html>', $html);
    }
}
