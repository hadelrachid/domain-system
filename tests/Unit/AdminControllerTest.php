<?php

namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DomainSystem\Core\Container\Container;
use DomainSystem\Plugins\SystemAdmin\Controllers\AdminController;
use DomainSystem\Core\Theme\ThemeManager;

class AdminControllerTest extends TestCase
{
    private string $tempThemesPath;

    protected function setUp(): void
    {
        $this->tempThemesPath = sys_get_temp_dir() . '/domain_system_themes_' . uniqid();
        mkdir($this->tempThemesPath);
        mkdir($this->tempThemesPath . '/admin');
        
        // Mock a simple admin template
        file_put_contents(
            $this->tempThemesPath . '/admin/plugins.php', 
            '<?php foreach($plugins as $p) { echo $p["name"] . ":" . ($p["is_active"] ? "1" : "0") . ";"; } ?>'
        );
    }

    protected function tearDown(): void
    {
        unlink($this->tempThemesPath . '/admin/plugins.php');
        rmdir($this->tempThemesPath . '/admin');
        rmdir($this->tempThemesPath);
    }

    public function testListPlugins()
    {
        $container = new Container();
        $themeManager = new ThemeManager($this->tempThemesPath);
        $container->singleton(ThemeManager::class, function() use ($themeManager) {
            return $themeManager;
        });

        $controller = new AdminController($container);
        $html = $controller->listPlugins();

        // The exact output depends on what plugins exist in the actual src/Plugins directory,
        // because AdminController reads from dirname(__DIR__, 4). 
        // We know at least 'database' and 'system-admin' exist.
        
        $this->assertStringContainsString('database', $html);
        $this->assertStringContainsString('system-admin', $html);
    }
}
