<?php

namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DomainSystem\Core\Plugin\Services\PluginDiscoverer;
use DomainSystem\Core\Plugin\Services\PluginStateManager;
use DomainSystem\Core\Container\Container;
use DomainSystem\Core\Events\EventDispatcher;

class PluginDiscovererTest extends TestCase
{
    private string $tempPluginsPath;
    private string $tempConfigPath;
    private PluginDiscoverer $discoverer;
    private PluginStateManager $stateManager;

    protected function setUp(): void
    {
        $this->tempPluginsPath = sys_get_temp_dir() . '/domain_system_plugins_' . uniqid();
        mkdir($this->tempPluginsPath . '/config', 0777, true);
        mkdir($this->tempPluginsPath . '/src/Plugins/TestPlugin', 0777, true);
        
        $this->tempConfigPath = $this->tempPluginsPath . '/config/plugins.json';
        
        $pluginPhp = <<<PHP
<?php
namespace DomainSystem\Plugins\TestPlugin;
use DomainSystem\Core\Plugin\AbstractPlugin;
class Plugin extends AbstractPlugin {
    public function register(): void {}
}
PHP;
        file_put_contents($this->tempPluginsPath . '/src/Plugins/TestPlugin/Plugin.php', $pluginPhp);
        
        $pluginJson = json_encode([
            'name' => 'test-plugin',
            'version' => '2.0.0'
        ]);
        file_put_contents($this->tempPluginsPath . '/src/Plugins/TestPlugin/plugin.json', $pluginJson);
        
        $configJson = json_encode([
            'test-plugin' => true
        ]);
        file_put_contents($this->tempConfigPath, $configJson);
        
        // Autoload the temp class for the test
        require_once $this->tempPluginsPath . '/src/Plugins/TestPlugin/Plugin.php';
        
        $container = new Container();
        $dispatcher = new EventDispatcher();
        $this->stateManager = new PluginStateManager($this->tempPluginsPath);
        
        $this->discoverer = new PluginDiscoverer($container, $dispatcher, $this->stateManager);
    }

    protected function tearDown(): void
    {
        unlink($this->tempPluginsPath . '/src/Plugins/TestPlugin/Plugin.php');
        unlink($this->tempPluginsPath . '/src/Plugins/TestPlugin/plugin.json');
        rmdir($this->tempPluginsPath . '/src/Plugins/TestPlugin');
        rmdir($this->tempPluginsPath . '/src/Plugins');
        rmdir($this->tempPluginsPath . '/src');
        unlink($this->tempConfigPath);
        rmdir($this->tempPluginsPath . '/config');
        rmdir($this->tempPluginsPath);
    }

    public function testPluginDiscoveryAndState()
    {
        $plugins = $this->discoverer->discover($this->tempPluginsPath . '/src/Plugins');
        
        $this->assertArrayHasKey('test-plugin', $plugins);
        $plugin = $plugins['test-plugin'];
        
        $this->assertEquals('test-plugin', $plugin->getName());
        $this->assertEquals('2.0.0', $plugin->getVersion());
        $this->assertTrue($plugin->isActive());
    }
}
