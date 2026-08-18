<?php

namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DomainSystem\Core\Plugin\PluginManager;
use DomainSystem\Core\Plugin\PluginInterface;
use DomainSystem\Core\Container\Container;
use DomainSystem\Core\Events\EventDispatcher;

class DummyPlugin implements PluginInterface
{
    private string $name;
    private array $dependencies;
    public bool $registered = false;

    public function __construct(string $name = 'dummy', array $dependencies = [])
    {
        $this->name = $name;
        $this->dependencies = $dependencies;
    }

    public function register(): void
    {
        $this->registered = true;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    public function isActive(): bool
    {
        return true;
    }
}

class PluginManagerTest extends TestCase
{
    public function testRegisterPlugins()
    {
        $container = new Container();
        $dispatcher = new EventDispatcher();
        $manager = new PluginManager($container, $dispatcher);

        $plugin1 = new DummyPlugin('plugin1');
        $plugin2 = new DummyPlugin('plugin2');

        $manager->addPlugin($plugin1);
        $manager->addPlugin($plugin2);

        $manager->bootPlugins();

        $this->assertTrue($plugin1->registered);
        $this->assertTrue($plugin2->registered);
    }

    public function testDependencyResolution()
    {
        $container = new Container();
        $dispatcher = new EventDispatcher();
        $manager = new PluginManager($container, $dispatcher);

        $pluginA = new DummyPlugin('pluginA', ['pluginB']);
        $pluginB = new DummyPlugin('pluginB');

        // Added in reverse order of dependency
        $manager->addPlugin($pluginA);
        $manager->addPlugin($pluginB);

        $order = [];
        $dispatcher->addListener('plugin.registered', function($pluginName) use (&$order) {
            $order[] = $pluginName;
        });

        $manager->bootPlugins();

        // B should be registered before A because A depends on B
        $this->assertEquals(['pluginB', 'pluginA'], $order);
    }

    public function testMissingDependencyThrowsException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Dependency 'plugin_missing' for plugin 'pluginA' not found.");

        $container = new Container();
        $dispatcher = new EventDispatcher();
        $manager = new PluginManager($container, $dispatcher);

        $pluginA = new DummyPlugin('pluginA', ['plugin_missing']);
        $manager->addPlugin($pluginA);

        $manager->bootPlugins();
    }
}
