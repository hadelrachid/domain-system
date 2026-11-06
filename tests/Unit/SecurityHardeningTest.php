<?php

declare(strict_types=1);

namespace Tests\Unit;

use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Routing\Middlewares\AuthMiddleware;
use DomainSystem\Core\Security\IdentityManager;
use DomainSystem\Core\Security\SafeRedirectValidator;
use DomainSystem\Core\Security\SessionManager;
use PHPUnit\Framework\TestCase;

class SecurityHardeningTest extends TestCase
{
    public function testSafeRedirectValidatorBlocksOpenRedirectAndPhishing(): void
    {
        $validator = new SafeRedirectValidator();

        // 1. Phishing externo com protocolo absoluto
        $this->assertSame('/fallback', $validator->sanitize('https://evil-phishing-site.com', '/fallback'));
        $this->assertFalse($validator->isSafe('https://evil-phishing-site.com'));

        // 2. Esquema relativo de protocolo //evil.com
        $this->assertSame('/fallback', $validator->sanitize('//evil.com/login', '/fallback'));
        $this->assertFalse($validator->isSafe('//evil.com/login'));

        // 3. Barra invertida \\evil.com
        $this->assertSame('/fallback', $validator->sanitize('\\\\evil.com/login', '/fallback'));
        $this->assertFalse($validator->isSafe('\\\\evil.com/login'));

        // 4. Esquemas perigosos javascript:, data:
        $this->assertSame('/fallback', $validator->sanitize('javascript:alert(1)', '/fallback'));
        $this->assertSame('/fallback', $validator->sanitize('data:text/html,<script>alert(1)</script>', '/fallback'));

        // 5. CRLF Injection
        $this->assertSame('/fallback', $validator->sanitize("/admin\r\nSet-Cookie: evil=1", '/fallback'));

        // 6. URLs relativas seguras locais
        $this->assertSame('/admin', $validator->sanitize('/admin', '/fallback'));
        $this->assertSame('/aluno/painel', $validator->sanitize('/aluno/painel', '/fallback'));
        $this->assertTrue($validator->isSafe('/aluno/painel'));
    }

    public function testAuthMiddlewareDefaultDenyOnAdminRoutesRedirectsUnauthenticated(): void
    {
        $sessionMock = $this->createMock(SessionManager::class);
        $sessionMock->method('get')->willReturn(null); // Deslogado

        $identityMock = $this->createMock(IdentityManager::class);

        $middleware = new AuthMiddleware($sessionMock, $identityMock);

        $request = new Request([], [], ['REQUEST_URI' => '/admin/settings']);

        $nextCalled = false;
        $next = function () use (&$nextCalled) {
            $nextCalled = true;
            return new Response('passed');
        };

        // Rota sem capabilities explícitas (desenvolvedor esqueceu)
        $result = $middleware->handle($request, $next, ['capabilities' => []]);

        $this->assertFalse($nextCalled);
        $this->assertSame(302, $result->getStatusCode());
        $location = $result->getHeader('Location');
        $this->assertStringContainsString('/login?redirect=', $location);
        $this->assertStringContainsString(urlencode('/admin/settings'), $location);
    }

    public function testAuthMiddlewareBlocksStudentAndUserRolesFromAdmin(): void
    {
        $sessionMock = $this->createMock(SessionManager::class);
        $sessionMock->method('get')->willReturnCallback(function ($key, $default = null) {
            if ($key === 'user_id') return 99;
            if ($key === 'user_role') return 'student'; // Aluno tentando invadir admin
            if ($key === 'user_name') return 'Aluno Invasor';
            return $default;
        });

        $identityMock = $this->createMock(IdentityManager::class);

        $middleware = new AuthMiddleware($sessionMock, $identityMock);

        $request = new Request([], [], ['REQUEST_URI' => '/admin']);

        $nextCalled = false;
        $next = function () use (&$nextCalled) {
            $nextCalled = true;
            return new Response('passed');
        };

        $result = $middleware->handle($request, $next, ['capabilities' => ['admin']]);

        $this->assertFalse($nextCalled);
        $this->assertSame(403, $result->getStatusCode());
        $this->assertStringContainsString('Acesso Negado', $result->getContent());
        $this->assertStringContainsString('Usuários comuns e alunos não possuem permissão', $result->getContent());
    }

    public function testEmergencyRouteIsBypassedByAuthMiddleware(): void
    {
        $sessionMock = $this->createMock(SessionManager::class);
        $sessionMock->method('get')->willReturn(null);

        $identityMock = $this->createMock(IdentityManager::class);

        $middleware = new AuthMiddleware($sessionMock, $identityMock);

        $request = new Request([], [], ['REQUEST_URI' => '/admin/emergency']);

        $nextCalled = false;
        $next = function () use (&$nextCalled) {
            $nextCalled = true;
            return new Response('emergency_view');
        };

        $result = $middleware->handle($request, $next, []);

        $this->assertTrue($nextCalled);
        $this->assertSame('emergency_view', $result->getContent());
    }
}
