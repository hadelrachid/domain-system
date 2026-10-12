<?php

namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DomainSystem\Core\Application;
use DomainSystem\Core\Container\Container;
use DomainSystem\Core\Events\EventDispatcher;

class ApplicationTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/ds_app_test_' . bin2hex(random_bytes(5));
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testApplicationInitialization()
    {
        $container = new Container();
        $dispatcher = new EventDispatcher();
        
        $app = new Application($container, $dispatcher, $this->tempDir);
        
        $this->assertSame($container, $app->getContainer());
        $this->assertSame($dispatcher, $app->getDispatcher());
    }

    public function testApplicationSingleton()
    {
        $container = new Container();
        $dispatcher = new EventDispatcher();
        
        $app = new Application($container, $dispatcher, $this->tempDir);
        
        $this->assertSame($app, Application::getInstance());
    }

    public function testPluginInitializationHooks()
    {
        $container = new Container();
        $dispatcher = new EventDispatcher();
        
        $app = new Application($container, $dispatcher, $this->tempDir);
        
        $called = false;
        $dispatcher->addListener('init', function() use (&$called) {
            $called = true;
        });

        // Simulating the kernel boot process
        $app->boot();

        $this->assertTrue($called, "The 'init' hook should be dispatched during application boot.");
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->removeDirectory($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
