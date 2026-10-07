<?php
namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DomainSystem\SystemApps\SystemAdmin\Controllers\AclController;
use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Core\Http\Request;
use DomainSystem\SystemApps\auth\Contracts\RoleRepositoryInterface;
use DomainSystem\SystemApps\auth\Contracts\CapabilityRepositoryInterface;
use DomainSystem\Core\Http\Response;

class AclControllerTest extends TestCase
{
    private $themeMock;
    private $rolesMock;
    private $capsMock;

    protected function setUp(): void
    {
        $this->themeMock = $this->createMock(ThemeManager::class);
        $this->rolesMock = $this->createMock(RoleRepositoryInterface::class);
        $this->capsMock = $this->createMock(CapabilityRepositoryInterface::class);
    }

    public function testAclControllerExistsAndCanRenderIndex()
    {
        $this->rolesMock->method('findAll')->willReturn([
            ['id' => 1, 'slug' => 'admin', 'name' => 'Admin']
        ]);
        $this->capsMock->method('findAll')->willReturn([
            ['id' => 1, 'slug' => 'write.post']
        ]);
        $this->capsMock->method('getCapabilitiesForRole')->willReturn([
            ['id' => 1, 'slug' => 'write.post']
        ]);

        $this->themeMock->expects($this->once())
            ->method('render')
            ->with('acl_panel', $this->anything())
            ->willReturn('html');

        $controller = new AclController($this->themeMock, $this->rolesMock, $this->capsMock);
        
        $requestMock = clone $this->createMock(Request::class);
        $res = $controller->index($requestMock);
        
        $this->assertEquals('html', $res);
    }

    public function testAclControllerCanSaveCapabilities()
    {
        $this->rolesMock->method('findAll')->willReturn([
            ['id' => 1, 'slug' => 'admin', 'name' => 'Admin']
        ]);
        
        $this->capsMock->method('getCapabilitiesForRole')->willReturn([
            ['id' => 5]
        ]);

        $this->rolesMock->expects($this->once())->method('revokeCapability')->with(1, 5);
        $this->rolesMock->expects($this->once())->method('grantCapability')->with(1, 10);

        $requestMock = clone $this->createMock(Request::class);
        $requestMock->expects($this->any())->method('input')->willReturn([
            1 => [10]
        ]);

        $controller = new AclController($this->themeMock, $this->rolesMock, $this->capsMock);
        $res = $controller->save($requestMock);
        
        $this->assertEquals('saved', $res);
    }
}
