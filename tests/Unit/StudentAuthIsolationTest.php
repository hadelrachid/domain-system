<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Plugins\academy\Services\StudentAuthService;
use PHPUnit\Framework\TestCase;

class StudentAuthIsolationTest extends TestCase
{
    public function testStudentLogoutDoesNotPurgeAdminGlobalSession(): void
    {
        $sessionData = [
            'user_id'            => 1,
            'user_name'          => 'Admin Master',
            'user_role'          => 'admin',
            'user_email'         => 'admin@master.com',
            'academy_student_id' => 77,
            'academy_student_name' => 'Aluno Teste',
        ];

        $session = $this->createMock(SessionManagerInterface::class);
        $session->method('get')->willReturnCallback(function (string $key, mixed $default = null) use (&$sessionData) {
            return $sessionData[$key] ?? $default;
        });
        $session->method('has')->willReturnCallback(function (string $key) use (&$sessionData) {
            return isset($sessionData[$key]);
        });
        $session->method('set')->willReturnCallback(function (string $key, mixed $val) use (&$sessionData) {
            $sessionData[$key] = $val;
        });
        $session->method('remove')->willReturnCallback(function (string $key) use (&$sessionData) {
            unset($sessionData[$key]);
        });
        $session->expects($this->once())->method('regenerate')->with(false);

        $auth = new StudentAuthService($session);

        $this->assertSame(77, $auth->getStudentId());

        $auth->logout();

        // As chaves de aluno devem ter sido removidas
        $this->assertArrayNotHasKey('academy_student_id', $sessionData);
        $this->assertArrayNotHasKey('academy_student_name', $sessionData);
        $this->assertTrue($sessionData['academy_logged_out']);

        // A sessão do Administrador DEVE permanecer intacta!
        $this->assertSame(1, $sessionData['user_id']);
        $this->assertSame('Admin Master', $sessionData['user_name']);
        $this->assertSame('admin', $sessionData['user_role']);
        $this->assertSame('admin@master.com', $sessionData['user_email']);
    }

    public function testGlobalUserSessionDoesNotAuthenticateAcademyStudent(): void
    {
        $sessionData = [
            'user_id'    => 10,
            'user_role'  => 'user',
            'user_name'  => 'Usuário comum do OS',
            'user_email' => 'comum@dominio.com',
        ];

        $session = $this->createMock(SessionManagerInterface::class);
        $session->method('get')->willReturnCallback(function (string $key, mixed $default = null) use (&$sessionData) {
            return $sessionData[$key] ?? $default;
        });

        $auth = new StudentAuthService($session);

        $this->assertNull($auth->getStudentId());
        $this->assertNull($auth->getStudentName());
        $this->assertNull($auth->getStudentEmail());
        $this->assertFalse($auth->isLoggedIn());
    }

    public function testStudentLogoutPreservesGlobalUserSession(): void
    {
        $sessionData = [
            'academy_student_id'   => 45,
            'academy_student_name' => 'Aluno Ativo',
            'user_id'              => 88,
            'user_role'            => 'user',
            'user_name'            => 'Usuário Site',
            'is_authenticated'     => true,
        ];

        $session = $this->createMock(SessionManagerInterface::class);
        $session->method('get')->willReturnCallback(function (string $key, mixed $default = null) use (&$sessionData) {
            return $sessionData[$key] ?? $default;
        });
        $session->method('remove')->willReturnCallback(function (string $key) use (&$sessionData) {
            unset($sessionData[$key]);
        });
        $session->method('set')->willReturnCallback(function (string $key, mixed $val) use (&$sessionData) {
            $sessionData[$key] = $val;
        });

        $auth = new StudentAuthService($session);
        $auth->logout();

        // Aluno deslogado
        $this->assertArrayNotHasKey('academy_student_id', $sessionData);
        $this->assertArrayNotHasKey('academy_student_name', $sessionData);
        $this->assertTrue($sessionData['academy_logged_out']);

        // Sessão do usuário comum preservada
        $this->assertSame(88, $sessionData['user_id']);
        $this->assertSame('user', $sessionData['user_role']);
        $this->assertSame('Usuário Site', $sessionData['user_name']);
        $this->assertTrue($sessionData['is_authenticated']);
    }

    public function testInvalidStudentIdThrowsExceptionOnLogin(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $session = $this->createMock(SessionManagerInterface::class);
        $auth = new StudentAuthService($session);
        $auth->login(0, 'Aluno', 'aluno@teste.com');
    }
}
