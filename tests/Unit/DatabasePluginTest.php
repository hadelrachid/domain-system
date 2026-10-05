<?php

namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DomainSystem\SystemApps\Database\Plugin;
use DomainSystem\SystemApps\Database\Connection;
use DomainSystem\SystemApps\Database\QueryBuilder;
use DomainSystem\Core\Container\Container;
use DomainSystem\Core\Events\EventDispatcher;

class DatabasePluginTest extends TestCase
{
    public function testPluginRegistrationBindsToContainer()
    {
        $container = new Container();
        // Mock environment variables for test
        putenv('DB_DSN=sqlite::memory:');
        
        $plugin = new Plugin(__DIR__ . '/../../src/SystemApps/Database');
        
        $this->assertEquals('database', $plugin->getName());
        $plugin->setActive(true);
        $this->assertTrue($plugin->isActive());
        $this->assertEmpty($plugin->getDependencies());

        // Emulando o ciclo do OS 2.0
                $runtime = $this->createMock(\DomainSystem\Core\Contracts\OsRuntimeInterface::class);
        $runtime->method('bind')->willReturnCallback(function($abstract, $concrete) use ($container) {
            $container->bind($abstract, $concrete);
        });
        $runtime->method('singleton')->willReturnCallback(function($abstract, $concrete) use ($container) {
            $container->singleton($abstract, $concrete);
        });
        $plugin->osBoot($runtime);

        $connection = $container->make(Connection::class);
        $this->assertInstanceOf(Connection::class, $connection);

        $qb = $container->make(QueryBuilder::class);
        $this->assertInstanceOf(QueryBuilder::class, $qb);
    }
}


