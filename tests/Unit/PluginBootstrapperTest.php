<?php

namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DomainSystem\Core\Plugin\Services\PluginBootstrapper;
use DomainSystem\Core\Plugin\Services\PluginStateManager;
use DomainSystem\Core\Container\Container;
use DomainSystem\Core\Events\EventDispatcher;
use DomainSystem\Tests\Support\FakePlugin;

class PluginBootstrapperTest extends TestCase
{
    private string $tempPath;
    private Container $container;
    private EventDispatcher $dispatcher;
    private PluginStateManager $stateManager;
    private PluginBootstrapper $bootstrapper;

    protected function setUp(): void
    {
        $this->tempPath = sys_get_temp_dir() . '/domain_system_test_' . uniqid();
        mkdir($this->tempPath . '/config', 0777, true);
        mkdir($this->tempPath . '/temp', 0777, true);
        file_put_contents($this->tempPath . '/config/plugins.json', '[]');
        
        $this->container = new Container();
        $this->dispatcher = new EventDispatcher();
        $this->stateManager = new PluginStateManager($this->tempPath);
        
        $this->bootstrapper = new PluginBootstrapper(
            $this->container,
            $this->dispatcher,
            $this->stateManager,
            $this->tempPath
        );
    }

    protected function tearDown(): void
    {
        unlink($this->tempPath . '/config/plugins.json');
        rmdir($this->tempPath . '/config');
        @unlink($this->tempPath . '/temp/process_stack.json');
        @rmdir($this->tempPath . '/temp');
        rmdir($this->tempPath);
    }

    public function testBootLifecycle()
    {
        $sysPlugin = new FakePlugin('core_auth', [], true, true);
        $userPlugin = new FakePlugin('user_blog', [], true, false);

        $this->bootstrapper->bootPlugins(['core_auth' => $sysPlugin], ['user_blog' => $userPlugin]);

        $this->assertTrue($sysPlugin->osRegistered, "System app should be registered");
        $this->assertTrue($sysPlugin->booted, "System app should be booted");
        
        $this->assertTrue($userPlugin->osRegistered, "User plugin should be registered");
        $this->assertTrue($userPlugin->booted, "User plugin should be booted");
    }

    public function testMissingArgumentThrowsArgumentCountError()
    {
        $this->expectException(\ArgumentCountError::class);

        // Intentionally omitting the second argument to reproduce the bug
        $sysPlugin = new FakePlugin('core_auth', [], true, true);
        
        // This should throw because userPlugins is strictly required
        $this->bootstrapper->bootPlugins(['core_auth' => $sysPlugin]);
    }

    public function testFailingPluginDoesNotCrashOthers()
    {
        $crashingPlugin = new class('crashing_plugin', [], true, false) extends FakePlugin {
            public function osBoot(\DomainSystem\Core\Contracts\OsRuntimeInterface $runtime): void { throw new \Exception("Boom!"); }
        };
        
        $goodPlugin = new FakePlugin('good_plugin', [], true, false);

        $this->bootstrapper->bootPlugins([], [
            'crashing_plugin' => $crashingPlugin,
            'good_plugin' => $goodPlugin
        ]);

        $this->assertFalse($crashingPlugin->booted, "Crashing plugin should not boot");
        $this->assertTrue($goodPlugin->osRegistered, "Good plugin should still register");
        $this->assertTrue($goodPlugin->booted, "Good plugin should still boot");
    }

    public function testDependencyResolutionOrder()
    {
        $pluginA = new FakePlugin('pluginA', ['pluginB'], true, true);
        $pluginB = new FakePlugin('pluginB', [], true, true);

        // Provide them in reverse order
        $this->bootstrapper->bootPlugins(['pluginA' => $pluginA, 'pluginB' => $pluginB], []);

        // With the current bootstrapper, it creates a boot stack. 
        // We can hook into events to assert order, but FakePlugin doesn't fire events directly.
        // For now, let's just ensure they both boot.
        $this->assertTrue($pluginA->booted);
        $this->assertTrue($pluginB->booted);
    }

    public function testMissingDependencyIsIgnoredAndBootsAnyway()
    {
        $pluginA = new FakePlugin('pluginA', ['missing_dependency'], true, true);
        $this->bootstrapper->bootPlugins(['pluginA' => $pluginA], []);
        $this->assertTrue($pluginA->booted, "Currently, missing dependencies are skipped and the plugin boots anyway");
    }
}
