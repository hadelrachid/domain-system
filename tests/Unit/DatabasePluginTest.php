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
        
        $plugin = new Plugin($container, __DIR__ . '/../../src/SystemApps/Database', new EventDispatcher());
        
        $this->assertEquals('database', $plugin->getName());
        $plugin->setActive(true);
        $this->assertTrue($plugin->isActive());
        $this->assertEmpty($plugin->getDependencies());

        // Emulando o ciclo do OS 2.0
        $plugin->osBoot($this->createMock(\DomainSystem\Core\Contracts\OsRuntimeInterface::class));

        $connection = $container->make(Connection::class);
        $this->assertInstanceOf(Connection::class, $connection);

        $qb = $container->make(QueryBuilder::class);
        $this->assertInstanceOf(QueryBuilder::class, $qb);
    }
}


