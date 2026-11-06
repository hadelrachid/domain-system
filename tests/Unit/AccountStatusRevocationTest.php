<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Core\Contracts\AccountStatusProviderInterface;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Routing\Middlewares\AuthMiddleware;
use DomainSystem\Core\Security\IdentityManager;
use DomainSystem\Plugins\academy\Contracts\CourseEnrollmentInterface;
use DomainSystem\Plugins\academy\Contracts\CourseRepositoryInterface;
use DomainSystem\Plugins\academy\Contracts\CourseStructureRepositoryInterface;
use DomainSystem\Plugins\academy\Contracts\StudentAuthServiceInterface;
use DomainSystem\Plugins\academy\Contracts\StudentProgressInterface;
use DomainSystem\Plugins\academy\Contracts\StudentStorageInterface;
use DomainSystem\Plugins\academy\Controllers\StudentDashboardController;
use DomainSystem\SystemApps\auth\Contracts\UserRepositoryInterface;
use DomainSystem\SystemApps\auth\Services\UserAccountStatusProvider;
use PHPUnit\Framework\TestCase;

class AccountStatusRevocationTest extends TestCase
{
    public function testUserAccountStatusProviderReturnsCorrectStatusAndCaches(): void
    {
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->expects($this->once())
            ->method('findById')
            ->with(10)
            ->willReturn(['id' => 10, 'status' => 'banned']);

        $provider = new UserAccountStatusProvider($userRepo);

        $this->assertSame('banned', $provider->statusOf(10));
        // Segunda chamada usa o cache em memória (não deve chamar findById novamente)
        $this->assertSame('banned', $provider->statusOf(10));
    }

    public function testUserAccountStatusProviderReturnsBannedWhenUserNotFound(): void
    {
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findById')->with(999)->willReturn(null);

        $provider = new UserAccountStatusProvider($userRepo);
        $this->assertSame('banned', $provider->statusOf(999));
    }

    public function testAuthMiddlewareDestroysSessionAndRedirectsWhenUserIsBanned(): void
    {
        $session = $this->createMock(SessionManagerInterface::class);
        $session->method('get')->willReturnMap([
            ['user_id', null, 42],
            ['user_role', '', 'admin'],
        ]);
        $session->expects($this->once())->method('destroy');

        $identity = $this->createMock(IdentityManager::class);

        $statusProvider = $this->createMock(AccountStatusProviderInterface::class);
        $statusProvider->expects($this->once())
            ->method('statusOf')
            ->with(42)
            ->willReturn('banned');

        $middleware = new AuthMiddleware($session, $identity, null, $statusProvider);

        $request = new Request([], [], [], [], ['REQUEST_URI' => '/admin/dashboard']);

        $called = false;
        $next = function () use (&$called) {
            $called = true;
            return new Response('OK');
        };

        $response = $middleware->handle($request, $next, ['capabilities' => ['admin']]);

        $this->assertFalse($called, 'O pipeline não deve continuar para usuário banido');
        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringContainsString('/login', $response->getHeader('Location') ?? '');
    }

    public function testAuthMiddlewareAllowsActiveUser(): void
    {
        $session = $this->createMock(SessionManagerInterface::class);
        $session->method('get')->willReturnMap([
            ['user_id', null, 42],
            ['user_role', '', 'admin'],
        ]);
        $session->expects($this->never())->method('destroy');

        $identity = $this->createMock(IdentityManager::class);
        $identity->method('userCan')->willReturn(true);

        $statusProvider = $this->createMock(AccountStatusProviderInterface::class);
        $statusProvider->expects($this->once())
            ->method('statusOf')
            ->with(42)
            ->willReturn('active');

        $middleware = new AuthMiddleware($session, $identity, null, $statusProvider);

        $request = new Request([], [], [], [], ['REQUEST_URI' => '/admin/dashboard']);

        $called = false;
        $next = function () use (&$called) {
            $called = true;
            return new Response('OK');
        };

        $response = $middleware->handle($request, $next, ['capabilities' => ['admin']]);

        $this->assertTrue($called);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testStudentDashboardLogsOutAndDestroysSessionWhenStudentIsSuspended(): void
    {
        $session = $this->createMock(SessionManagerInterface::class);
        $session->expects($this->once())->method('destroy');

        $studentAuth = $this->createMock(StudentAuthServiceInterface::class);
        $studentAuth->method('isLoggedIn')->willReturn(true);
        $studentAuth->method('getStudentId')->willReturn(7);
        $studentAuth->expects($this->once())->method('logout');

        $statusProvider = $this->createMock(AccountStatusProviderInterface::class);
        $statusProvider->expects($this->once())
            ->method('statusOf')
            ->with(7)
            ->willReturn('suspended');

        $enrollment = $this->createMock(CourseEnrollmentInterface::class);
        $courseRepo = $this->createMock(CourseRepositoryInterface::class);
        $structureRepo = $this->createMock(CourseStructureRepositoryInterface::class);
        $progress = $this->createMock(StudentProgressInterface::class);
        $storage = $this->createMock(StudentStorageInterface::class);

        $controller = new StudentDashboardController(
            $session,
            $enrollment,
            $courseRepo,
            $structureRepo,
            $progress,
            $storage,
            $studentAuth,
            null,
            null,
            $statusProvider
        );

        $request = new Request();
        $response = $controller->index($request);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringContainsString('/aluno/login', $response->getHeader('Location') ?? '');
    }
}
