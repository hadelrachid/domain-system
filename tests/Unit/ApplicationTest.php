<?php

namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DomainSystem\Core\Application;
use DomainSystem\Core\Container\Container;
use DomainSystem\Core\Events\EventDispatcher;

class ApplicationTest extends TestCase
{
    public function testApplicationInitialization()
    {
        $container = new Container();
        $dispatcher = new EventDispatcher();
        
        $app = new Application($container, $dispatcher, __DIR__);
        
        $this->assertSame($container, $app->getContainer());
        $this->assertSame($dispatcher, $app->getDispatcher());
    }

    public function testApplicationSingleton()
    {
        $container = new Container();
        $dispatcher = new EventDispatcher();
        
        $app = new Application($container, $dispatcher, __DIR__);
        
        $this->assertSame($app, Application::getInstance());
    }

    public function testPluginInitializationHooks()
    {
        $container = new Container();
        $dispatcher = new EventDispatcher();
        
        $app = new Application($container, $dispatcher, __DIR__);
        
        $called = false;
        $dispatcher->addListener('init', function() use (&$called) {
            $called = true;
        });

        // Simulating the kernel boot process
        $app->boot();

        $this->assertTrue($called, "The 'init' hook should be dispatched during application boot.");
    }
}
