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
}
