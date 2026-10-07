<?php
namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DomainSystem\Core\Plugin\OsRuntime;
use DomainSystem\Core\Container\Container;
use DomainSystem\SystemApps\auth\Contracts\CapabilityRepositoryInterface;

class OsRuntimeTest extends TestCase
{
    public function testRegisterCapabilityDelegatesToRepository()
    {
        $container = new Container();
        $repoMock = $this->createMock(CapabilityRepositoryInterface::class);
        $repoMock->expects($this->once())
                 ->method('registerCapability')
                 ->with('test.cap', 'Test Context');
                 
        $container->singleton(CapabilityRepositoryInterface::class, function() use ($repoMock) {
            return $repoMock;
        });

        $connectorMock = $this->createMock(\DomainSystem\Core\Plugin\OsConnector::class);
        $linkRegistryMock = $this->createMock(\DomainSystem\Core\Plugin\LinkRegistry::class);
        $dispatcherMock = $this->createMock(\DomainSystem\Core\Events\EventDispatcher::class);
        
        $runtime = new OsRuntime($container, $connectorMock, $linkRegistryMock, $dispatcherMock, 'DomainSystem\\TestPlugin');
        $runtime->registerCapability('test.cap', 'Test Context');
    }

    public function testRegisterCapabilityFailsGracefullyIfNoAuthPlugin()
    {
        $container = new Container();
        $connectorMock = $this->createMock(\DomainSystem\Core\Plugin\OsConnector::class);
        $linkRegistryMock = $this->createMock(\DomainSystem\Core\Plugin\LinkRegistry::class);
        $dispatcherMock = $this->createMock(\DomainSystem\Core\Events\EventDispatcher::class);
        
        $runtime = new OsRuntime($container, $connectorMock, $linkRegistryMock, $dispatcherMock, 'DomainSystem\\TestPlugin');
        
        // This should not throw an exception (it catches silently)
        $runtime->registerCapability('test.cap', 'Test Context');
        
        $this->assertTrue(true, 'Executed silently without throwing exceptions');
    }
}
