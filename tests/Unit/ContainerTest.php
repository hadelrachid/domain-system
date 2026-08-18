<?php

namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DomainSystem\Core\Container\Container;
use Exception;

class DummyDependency {}
class DummyService {
    public DummyDependency $dep;
    public function __construct(DummyDependency $dep) {
        $this->dep = $dep;
    }
}

class ContainerTest extends TestCase
{
    public function testBindAndMake()
    {
        $container = new Container();
        $container->bind('foo', function() {
            return 'bar';
        });

        $this->assertEquals('bar', $container->make('foo'));
    }

    public function testSingleton()
    {
        $container = new Container();
        $container->singleton('random', function() {
            return rand(1, 1000);
        });

        $val1 = $container->make('random');
        $val2 = $container->make('random');

        $this->assertEquals($val1, $val2, "Singleton should return the exact same instance/value.");
    }

    public function testAutoWiring()
    {
        $container = new Container();
        
        // Should automatically instantiate DummyDependency and inject it into DummyService
        $service = $container->make(DummyService::class);

        $this->assertInstanceOf(DummyService::class, $service);
        $this->assertInstanceOf(DummyDependency::class, $service->dep);
    }

    public function testThrowsExceptionOnUnresolvable()
    {
        $this->expectException(Exception::class);
        $container = new Container();
        $container->make('NonExistentInterface');
    }
}
