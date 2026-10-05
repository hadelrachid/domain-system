<?php

namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DomainSystem\Core\Security\IdentityManager;
use DomainSystem\Core\Container\Container;

class IdentityManagerTest extends TestCase
{
    private $dbMock;
    private $container;

    protected function setUp(): void
    {
        // Mock do PDO e Connection
        $this->dbMock = $this->createMock(\PDO::class);
        $connMock = $this->createMock(\DomainSystem\SystemApps\Database\Connection::class);
        $connMock->method('getPdo')->willReturn($this->dbMock);

        $this->container = new Container();
        $this->container->singleton(\DomainSystem\SystemApps\Database\Connection::class, function() use ($connMock) {
            return $connMock;
        });
    }

    public function testUserCanReturnsTrueForExistingCapability()
    {
        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn(['ler_arquivos', 'core.plugin.install']);

        // Mock para verificação do hasRole('admin') (retorna falso neste caso)
        $stmtAdminMock = $this->createMock(\PDOStatement::class);
        $stmtAdminMock->method('execute')->willReturn(true);
        $stmtAdminMock->method('fetch')->willReturn(false);

        // Map PDO prepare arguments to specific statement mocks
        $this->dbMock->method('prepare')->willReturnCallback(function($sql) use ($stmtMock, $stmtAdminMock) {
            if (strpos($sql, 'SELECT DISTINCT c.slug') !== false) {
                return $stmtMock;
            }
            return $stmtAdminMock;
        });

        $identity = new IdentityManager($this->container);
        
        $this->assertTrue($identity->userCan(1, 'core.plugin.install'));
        $this->assertFalse($identity->userCan(1, 'core.tema.delete'));
    }

    public function testUserCanReturnsTrueForAdminWildcard()
    {
        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn(['apenas_uma_permissao']);

        $stmtAdminMock = $this->createMock(\PDOStatement::class);
        $stmtAdminMock->method('execute')->willReturn(true);
        $stmtAdminMock->method('fetch')->willReturn(true); // O usuário é Admin

        $this->dbMock->method('prepare')->willReturnCallback(function($sql) use ($stmtMock, $stmtAdminMock) {
            if (strpos($sql, 'SELECT DISTINCT c.slug') !== false) {
                return $stmtMock;
            }
            return $stmtAdminMock;
        });

        $identity = new IdentityManager($this->container);
        
        // Se é admin, deve poder fazer TUDO, inclusive o que não está explicitamente no array
        $this->assertTrue($identity->userCan(99, 'uma_capability_qualquer'));
    }
}
