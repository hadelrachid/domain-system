<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Core\Contracts\CockpitRegistryInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Security\SafeRedirectValidator;
use DomainSystem\Core\Security\SessionManager;
use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\SystemApps\auth\Contracts\UserRepositoryInterface;
use DomainSystem\SystemApps\auth\Controllers\AuthController;
use DomainSystem\SystemApps\auth\Services\TwoFactorService;
use PHPUnit\Framework\TestCase;

class AuthControllerRedirectTest extends TestCase
{
    private function createAuthController(array $sessionData = [], array $userRecord = null): array
    {
        $theme = $this->createMock(ThemeManager::class);
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $twoFactor = $this->createMock(TwoFactorService::class);
        $cockpit = $this->createMock(CockpitRegistryInterface::class);
        $cockpit->method('getProviderForRole')->willReturn(null);

        $sessionStorage = new \ArrayObject($sessionData);
        $session = $this->createMock(SessionManager::class);
        $session->method('has')->willReturnCallback(function ($key) use ($sessionStorage) {
            return $sessionStorage->offsetExists($key);
        });
        $session->method('get')->willReturnCallback(function ($key, $default = null) use ($sessionStorage) {
            return $sessionStorage->offsetExists($key) ? $sessionStorage->offsetGet($key) : $default;
        });
        $session->method('set')->willReturnCallback(function ($key, $val) use ($sessionStorage) {
            $sessionStorage->offsetSet($key, $val);
        });

        if ($userRecord !== null) {
            $userRepo->method('findByEmail')->willReturn($userRecord);
        }

        $controller = new AuthController(
            $theme,
            $userRepo,
            $twoFactor,
            $cockpit,
            $session,
            new SafeRedirectValidator()
        );

        return [$controller, $sessionStorage];
    }

    public function testAdminLoginWithRedirectParamRedirectsToAdminDashboard(): void
    {
        $passwordHash = password_hash('AdminPass123!', PASSWORD_DEFAULT);
        $userRecord = [
            'id' => 1,
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => $passwordHash,
            'role' => 'admin',
            'status' => 'active',
            'two_factor_type' => 'none',
        ];

        [$controller, $sessionStorage] = $this->createAuthController([], $userRecord);

        $request = new Request([], [
            'email' => 'admin@test.com',
            'password' => 'AdminPass123!',
            'redirect' => '/admin',
        ]);

        $response = $controller->authenticate($request);

        $this->assertSame(302, $response->getStatusCode());
        $location = $response->getHeader('Location') ?? '';
        $this->assertStringEndsWith('/admin', $location);
        $this->assertStringNotContainsString('aluno', $location);
    }

    public function testAdminLoginWithoutRedirectParamDefaultsToAdmin(): void
    {
        $passwordHash = password_hash('AdminPass123!', PASSWORD_DEFAULT);
        $userRecord = [
            'id' => 1,
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => $passwordHash,
            'role' => 'admin',
            'status' => 'active',
            'two_factor_type' => 'none',
        ];

        [$controller, $sessionStorage] = $this->createAuthController([], $userRecord);

        $request = new Request([], [
            'email' => 'admin@test.com',
            'password' => 'AdminPass123!',
        ]);

        $response = $controller->authenticate($request);

        $this->assertSame(302, $response->getStatusCode());
        $location = $response->getHeader('Location') ?? '';
        $this->assertStringEndsWith('/admin', $location);
        $this->assertStringNotContainsString('aluno', $location);
    }

    public function testRegularUserLoginIsRejectedOnAdminLoginGate(): void
    {
        $passwordHash = password_hash('UserPass123!', PASSWORD_DEFAULT);
        $userRecord = [
            'id' => 2,
            'name' => 'Standard User',
            'email' => 'user@test.com',
            'password' => $passwordHash,
            'role' => 'user',
            'status' => 'active',
            'two_factor_type' => 'none',
        ];

        [$controller, $sessionStorage] = $this->createAuthController([], $userRecord);

        $request = new Request([], [
            'email' => 'user@test.com',
            'password' => 'UserPass123!',
        ]);

        $response = $controller->authenticate($request);

        $this->assertSame(302, $response->getStatusCode());
        $location = $response->getHeader('Location') ?? '';
        // Usuário comum é rejeitado na porta de entrada do Admin e permanece no /login com erro amigável
        $this->assertStringEndsWith('/login', $location);
        $this->assertSame('Credenciais inválidas. Verifique seu e-mail e senha.', $sessionStorage['auth_error'] ?? '');
    }

    public function testShowLoginFormForLoggedInAdminRedirectsToAdmin(): void
    {
        [$controller] = $this->createAuthController([
            'user_id' => 1,
            'user_role' => 'admin',
        ]);

        $request = new Request();
        $response = $controller->showLoginForm($request);

        $this->assertSame(302, $response->getStatusCode());
        $location = $response->getHeader('Location') ?? '';
        $this->assertStringEndsWith('/admin', $location);
        $this->assertStringNotContainsString('aluno', $location);
    }
}
