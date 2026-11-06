<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Core\Contracts\CockpitRegistryInterface;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Security\SessionManager;
use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Plugins\academy\Controllers\StudentAuthController;
use DomainSystem\Plugins\academy\Services\StudentAuthService;
use DomainSystem\SystemApps\auth\Contracts\LoginThrottleInterface;
use DomainSystem\SystemApps\auth\Contracts\UserRepositoryInterface;
use DomainSystem\SystemApps\auth\Controllers\AuthController;
use DomainSystem\SystemApps\auth\Services\PdoLoginThrottle;
use DomainSystem\SystemApps\auth\Services\TwoFactorService;
use PDO;
use PHPUnit\Framework\TestCase;

class AuthThrottlingAndTimingSecurityTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                username VARCHAR(100) UNIQUE NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(50) DEFAULT 'user',
                status VARCHAR(50) DEFAULT 'active',
                created_at DATETIME,
                updated_at DATETIME
            )
        ");
    }

    public function testPdoLoginThrottleLocksAfterConfiguredAttemptsAndClears(): void
    {
        $throttle = new PdoLoginThrottle($this->pdo, 3, 5);
        $key = 'test@example.com|127.0.0.1';

        $this->assertFalse($throttle->tooManyAttempts($key));

        $throttle->hit($key);
        $this->assertFalse($throttle->tooManyAttempts($key));

        $throttle->hit($key);
        $this->assertFalse($throttle->tooManyAttempts($key));

        $throttle->hit($key); // 3ª tentativa atinge o limite
        $this->assertTrue($throttle->tooManyAttempts($key));

        $throttle->clear($key);
        $this->assertFalse($throttle->tooManyAttempts($key));
    }

    public function testStudentAuthBlocksAfterTooManyFailedAttempts(): void
    {
        $session = $this->createMock(SessionManagerInterface::class);
        $session->method('getCsrfToken')->willReturn('valid_csrf');

        $throttle = $this->createMock(LoginThrottleInterface::class);
        $throttle->method('tooManyAttempts')->willReturn(true);

        $studentAuth = $this->createMock(StudentAuthService::class);
        $studentAuth->expects($this->once())
            ->method('setFlash')
            ->with('error', 'Muitas tentativas de login incorretas. Aguarde alguns minutos antes de tentar novamente.');

        $controller = new StudentAuthController(
            $session,
            $this->pdo,
            $studentAuth,
            null,
            null,
            null,
            $throttle
        );

        $request = new Request([], [
            'csrf_token' => 'valid_csrf',
            'email'      => 'aluno@teste.com',
            'password'   => 'QualquerSenha123',
        ], [], [], ['REMOTE_ADDR' => '127.0.0.1']);

        $response = $controller->authenticate($request);
        $this->assertSame(302, $response->getStatusCode());
    }

    public function testStudentAuthTimingMitigationOnNonExistentEmail(): void
    {
        $session = $this->createMock(SessionManagerInterface::class);
        $session->method('getCsrfToken')->willReturn('valid_csrf');

        $throttle = $this->createMock(LoginThrottleInterface::class);
        $throttle->method('tooManyAttempts')->willReturn(false);
        $throttle->expects($this->once())->method('hit');

        $studentAuth = $this->createMock(StudentAuthService::class);
        $studentAuth->expects($this->once())
            ->method('setFlash')
            ->with('error', 'E-mail ou senha incorretos.');

        $controller = new StudentAuthController(
            $session,
            $this->pdo,
            $studentAuth,
            null,
            null,
            null,
            $throttle
        );

        $request = new Request([], [
            'csrf_token' => 'valid_csrf',
            'email'      => 'inexistente@teste.com',
            'password'   => 'SenhaIncorreta!123',
        ], [], [], ['REMOTE_ADDR' => '127.0.0.1']);

        $response = $controller->authenticate($request);
        $this->assertSame(302, $response->getStatusCode());
    }

    public function testStudentRegistrationGracefulUniqueConstraintHandling(): void
    {
        // Insere usuário inicial
        $this->pdo->exec("INSERT INTO users (name, username, email, password, role) VALUES ('Existente', 'existente', 'duplicado@teste.com', 'hash', 'user')");

        $session = $this->createMock(SessionManagerInterface::class);
        $session->method('getCsrfToken')->willReturn('valid_csrf');

        $studentAuth = $this->createMock(StudentAuthService::class);
        // O flash deve ser amigável e NÃO conter SQLSTATE ou tabela
        $studentAuth->expects($this->once())
            ->method('setFlash')
            ->with('error', 'Este e-mail já possui cadastro. Faça login para continuar.');

        $controller = new StudentAuthController(
            $session,
            $this->pdo,
            $studentAuth
        );

        $request = new Request([], [
            'csrf_token'       => 'valid_csrf',
            'name'             => 'Novo Aluno',
            'username'         => 'novoaluno',
            'email'            => 'duplicado@teste.com',
            'password'              => 'SenhaSegura!123',
            'password_confirmation' => 'SenhaSegura!123',
        ]);

        $response = $controller->register($request);
        $this->assertSame(302, $response->getStatusCode());
    }
}
