<?php

namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DomainSystem\Plugins\Database\Plugin;
use DomainSystem\Plugins\Database\Connection;
use DomainSystem\Plugins\Database\QueryBuilder;
use DomainSystem\Core\Container\Container;

class DatabasePluginTest extends TestCase
{
    public function testPluginRegistrationBindsToContainer()
    {
        $container = new Container();
        // Mock environment variables for test
        putenv('DB_DSN=sqlite::memory:');
        
        $plugin = new Plugin($container);
        
        $this->assertEquals('database', $plugin->getName());
        $this->assertTrue($plugin->isActive());
        $this->assertEmpty($plugin->getDependencies());

        $plugin->register();

        $connection = $container->make(Connection::class);
        $this->assertInstanceOf(Connection::class, $connection);

        $qb = $container->make(QueryBuilder::class);
        $this->assertInstanceOf(QueryBuilder::class, $qb);
    }
}
