<?php

namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DomainSystem\Core\Routing\Middlewares\AuthMiddleware;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\SessionManager;
use DomainSystem\Core\Security\IdentityManager;

class AuthMiddlewareTest extends TestCase
{
    private $sessionMock;
    private $identityMock;
    private $requestMock;

    protected function setUp(): void
    {
        $this->sessionMock = $this->createMock(SessionManager::class);
        $this->identityMock = $this->createMock(IdentityManager::class);
        $this->requestMock = $this->createMock(Request::class);
    }

    public function testMiddlewareAllowsPublicRoutes()
    {
        $middleware = new AuthMiddleware($this->sessionMock, $this->identityMock);
        
        $called = false;
        $next = function($req) use (&$called) {
            $called = true;
            return 'passed';
        };

        // Rota pública (capabilities vazio)
        $result = $middleware->handle($this->requestMock, $next, ['capabilities' => []]);
        
        $this->assertTrue($called);
        $this->assertEquals('passed', $result);
    }
    
    // Testa o die() por reflection ou processo separado é complexo. 
    // Em vez disso testaremos pelo menos o comportamento de permissão.
    
    public function testMiddlewareAllowsAccessWhenUserHasCapability()
    {
        $this->sessionMock->method('get')->willReturnMap([
            ['user_id', null, 5] // User is logged in
        ]);
        
        // Identidade diz que ele PODE
        $this->identityMock->method('userCan')->with(5, 'core.manage')->willReturn(true);
        
        $middleware = new AuthMiddleware($this->sessionMock, $this->identityMock);
        
        $called = false;
        $next = function($req) use (&$called) {
            $called = true;
            return 'passed';
        };

        $result = $middleware->handle($this->requestMock, $next, ['capabilities' => ['core.manage']]);
        
        $this->assertTrue($called);
    }

    public function testMiddlewareFallbacksToLegacyRoles()
    {
        $this->sessionMock->method('get')->willReturnMap([
            ['user_id', null, 10]
        ]);
        
        // Simulando um plugin legado que não usa capabilities, e sim roles (cargo puro)
        // userCan vai retornar falso, mas hasRole vai retornar verdadeiro
        $this->identityMock->method('userCan')->willReturn(false);
        $this->identityMock->method('hasRole')->with(10, 'editor')->willReturn(true);
        
        $middleware = new AuthMiddleware($this->sessionMock, $this->identityMock);
        
        $called = false;
        $next = function($req) use (&$called) {
            $called = true;
            return 'passed';
        };

        // Rota legada usando 'roles'
        $result = $middleware->handle($this->requestMock, $next, ['roles' => ['editor']]);
        
        $this->assertTrue($called);
    }
}
