<?php

namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DomainSystem\Core\Plugin\PluginManager;
use DomainSystem\Core\Container\Container;
use DomainSystem\Core\Events\EventDispatcher;

class PluginStateTest extends TestCase
{
    private string $tempPluginsPath;
    private string $tempConfigPath;

    protected function setUp(): void
    {
        $this->tempPluginsPath = sys_get_temp_dir() . '/domain_system_plugins_' . uniqid();
        $this->tempConfigPath = $this->tempPluginsPath . '/plugins.json';
        
        mkdir($this->tempPluginsPath);
        mkdir($this->tempPluginsPath . '/TestPlugin');
        
        $pluginPhp = <<<PHP
<?php
namespace DomainSystem\Plugins\TestPlugin;
use DomainSystem\Core\Plugin\AbstractPlugin;
class Plugin extends AbstractPlugin {
    public function register(): void {}
}
PHP;
        file_put_contents($this->tempPluginsPath . '/TestPlugin/Plugin.php', $pluginPhp);
        
        $pluginJson = json_encode([
            'name' => 'test-plugin',
            'version' => '2.0.0'
        ]);
        file_put_contents($this->tempPluginsPath . '/TestPlugin/plugin.json', $pluginJson);
        
        $configJson = json_encode([
            'test-plugin' => true
        ]);
        file_put_contents($this->tempConfigPath, $configJson);
        
        // Autoload the temp class for the test
        require_once $this->tempPluginsPath . '/TestPlugin/Plugin.php';
    }

    protected function tearDown(): void
    {
        unlink($this->tempPluginsPath . '/TestPlugin/Plugin.php');
        unlink($this->tempPluginsPath . '/TestPlugin/plugin.json');
        rmdir($this->tempPluginsPath . '/TestPlugin');
        unlink($this->tempConfigPath);
        rmdir($this->tempPluginsPath);
    }

    public function testPluginDiscoveryAndState()
    {
        $container = new Container();
        $dispatcher = new EventDispatcher();
        $manager = new PluginManager($container, $dispatcher);
        
        $manager->discoverPlugins($this->tempPluginsPath, $this->tempConfigPath);
        
        $plugins = $manager->getPlugins();
        
        $this->assertArrayHasKey('test-plugin', $plugins);
        $plugin = $plugins['test-plugin'];
        
        $this->assertEquals('test-plugin', $plugin->getName());
        $this->assertEquals('2.0.0', $plugin->getVersion());
        $this->assertTrue($plugin->isActive());
    }
}
