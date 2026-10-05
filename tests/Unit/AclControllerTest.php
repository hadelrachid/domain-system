<?php

namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DomainSystem\SystemApps\SystemAdmin\Controllers\AclController;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\SystemApps\Database\Connection;

class AclControllerTest extends TestCase
{
    private $themeMock;
    private $pdoMock;
    private $dbMock;
    
    protected function setUp(): void
    {
        $this->themeMock = $this->createMock(ThemeManager::class);
        $this->themeMock->method('render')->willReturn('<h1>Controle de Acessos (ACL)</h1>');
        
        $this->pdoMock = $this->createMock(\PDO::class);
        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->method('fetchAll')->willReturn([]);
        $this->pdoMock->method('query')->willReturn($stmtMock);

        $this->dbMock = $this->createMock(Connection::class);
        $this->dbMock->method('getPdo')->willReturn($this->pdoMock);
    }

    public function testAclControllerExistsAndCanRenderIndex()
    {
        $controller = new AclController($this->themeMock, $this->dbMock);
        $requestMock = $this->createMock(Request::class);
        
        $response = $controller->index($requestMock);
        $this->assertStringContainsString('Controle de Acessos (ACL)', $response);
    }
    
    public function testAclControllerCanSaveCapabilities()
    {
        $controller = new AclController($this->themeMock, $this->dbMock);
        $requestMock = $this->createMock(Request::class);
        $requestMock->expects($this->any())->method('input')->willReturnMap([
            ['permissions', [], [
                1 => [2, 3], // role_id = 1, tem capabilities 2 e 3
                2 => [3]     // role_id = 2, tem capability 3
            ]]
        ]);
        
        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $this->pdoMock->method('prepare')->willReturn($stmtMock);
        
        $response = $controller->save($requestMock);
        $this->assertNotNull($response);
    }
}
