<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Core\Contracts\PasswordPolicyInterface;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Contracts\ThemeManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Responses\ViewResponse;
use DomainSystem\Core\Security\SafeRedirectValidator;
use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Plugins\academy\Contracts\CourseEnrollmentRepositoryInterface;
use DomainSystem\Plugins\academy\Contracts\CourseRepositoryInterface;
use DomainSystem\Plugins\academy\Contracts\StudentAdminRepositoryInterface;
use DomainSystem\Plugins\academy\Contracts\StudentAdminServiceInterface;
use DomainSystem\Plugins\academy\Contracts\StudentAuthServiceInterface;
use DomainSystem\Plugins\academy\Controllers\StudentAdminController;
use DomainSystem\Plugins\academy\Controllers\StudentAuthController;
use DomainSystem\Plugins\academy\Repositories\PdoStudentAdminRepository;
use DomainSystem\Plugins\academy\Services\StudentAdminService;
use DomainSystem\Plugins\academy\Services\StudentAuthService;
use DomainSystem\SystemApps\auth\Contracts\UserRepositoryInterface;
use DomainSystem\SystemApps\auth\Controllers\AuthController;
use DomainSystem\SystemApps\auth\Controllers\UserController;
use DomainSystem\SystemApps\auth\Repositories\UserRepository;
use DomainSystem\SystemApps\auth\Services\TwoFactorService;
use DomainSystem\SystemApps\Database\Connection;
use PDO;
use PHPUnit\Framework\TestCase;

class StudentManagementAndModerationTest extends TestCase
{
    private function createInMemoryUserRepository(): UserRepository
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $pdo->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                username VARCHAR(100) NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(50) DEFAULT 'user',
                status VARCHAR(20) DEFAULT 'active',
                avatar VARCHAR(255) NULL,
                profile_image VARCHAR(255) NULL,
                theme_color VARCHAR(50) DEFAULT 'default',
                dashboard_layout TEXT NULL,
                two_factor_type VARCHAR(20) DEFAULT 'none',
                two_factor_secret VARCHAR(255) NULL,
                email_2fa_code VARCHAR(6) NULL,
                email_2fa_expiry DATETIME NULL,
                session_timeout INTEGER NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $connMock = $this->createMock(Connection::class);
        $connMock->method('getPdo')->willReturn($pdo);

        return new UserRepository($connMock);
    }

    public function testUserRepositoryPaginationFilteringAndStatus(): void
    {
        $repo = $this->createInMemoryUserRepository();

        $repo->createUser(['name' => 'Admin Boss', 'email' => 'admin@test.com', 'password' => 'secret', 'role' => 'admin', 'status' => 'active']);
        $repo->createUser(['name' => 'Aluno Carlos', 'email' => 'carlos@test.com', 'password' => 'secret', 'role' => 'user', 'status' => 'active']);
        $repo->createUser(['name' => 'Aluna Maria', 'email' => 'maria@test.com', 'password' => 'secret', 'role' => 'user', 'status' => 'suspended']);
        $repo->createUser(['name' => 'Troll Banido', 'email' => 'troll@test.com', 'password' => 'secret', 'role' => 'user', 'status' => 'banned']);

        // 1. Testa contagem total e por grupo
        $totalAll = $repo->countUsers();
        $this->assertSame(4, $totalAll);

        $totalStudents = $repo->countUsers('students_group');
        $this->assertSame(3, $totalStudents);

        $totalAdmins = $repo->countUsers('admins_group');
        $this->assertSame(1, $totalAdmins);

        // 2. Testa busca por termo
        $searchCarlos = $repo->getPaginatedUsers(1, 10, null, 'carlos');
        $this->assertCount(1, $searchCarlos);
        $this->assertSame('Aluno Carlos', $searchCarlos[0]['name']);

        // 3. Testa filtro por status
        $banned = $repo->getPaginatedUsers(1, 10, null, null, 'banned');
        $this->assertCount(1, $banned);
        $this->assertSame('Troll Banido', $banned[0]['name']);

        // 4. Testa alteração de status
        $repo->updateStatus((int)$searchCarlos[0]['id'], 'suspended');
        $updated = $repo->findById((int)$searchCarlos[0]['id']);
        $this->assertSame('suspended', $updated['status']);
    }

    public function testStudentAdminServiceConsolidatesEnrollmentsAndMetrics(): void
    {
        $studentRepo = $this->createMock(StudentAdminRepositoryInterface::class);
        $enrollmentRepo = $this->createMock(CourseEnrollmentRepositoryInterface::class);
        $courseRepo = $this->createMock(CourseRepositoryInterface::class);

        $studentRepo->method('countStudents')->willReturn(1);
        $studentRepo->method('getPaginatedStudents')->willReturn([
            [
                'id' => 10,
                'name' => 'Lucas Aluno',
                'email' => 'lucas@test.com',
                'username' => 'lucas',
                'role' => 'user',
                'status' => 'active',
                'created_at' => '2026-11-01 10:00:00'
            ]
        ]);

        $courseRepo->method('findAll')->willReturn([
            ['id' => 1, 'title' => 'PHP Moderno 8.2'],
            ['id' => 2, 'title' => 'Arquitetura Limpa']
        ]);

        $enrollmentRepo->method('findActiveByStudent')->with(10)->willReturn([
            ['course_id' => 1, 'expires_at' => null, 'order_ref' => 'REF123']
        ]);

        $service = new StudentAdminService($studentRepo, $enrollmentRepo, $courseRepo);

        $result = $service->listStudents(1, 20, null, null);
        $this->assertSame(1, $result['total']);
        $this->assertCount(1, $result['items']);
        $student = $result['items'][0];
        $this->assertSame(10, $student['id']);
        $this->assertSame('Lucas Aluno', $student['name']);
        $this->assertSame(1, $student['courses_count']);
        $this->assertSame('PHP Moderno 8.2', $student['enrolled_courses'][0]['title']);

        // Testa Concessão e Revogação
        $enrollmentRepo->expects($this->once())
            ->method('saveEnrollment')
            ->with(10, 2, 'active', null, 'MANUAL_ADMIN')
            ->willReturn(true);
        $this->assertTrue($service->enrollStudent(10, 2));

        $enrollmentRepo->expects($this->once())
            ->method('updateStatus')
            ->with(10, 2, 'cancelled')
            ->willReturn(true);
        $this->assertTrue($service->revokeEnrollment(10, 2));
    }

    public function testStudentAdminControllerCockpitAndActions(): void
    {
        $studentService = $this->createMock(StudentAdminServiceInterface::class);
        $courseRepo = $this->createMock(CourseRepositoryInterface::class);
        $session = $this->createMock(SessionManagerInterface::class);

        $session->method('getCsrfToken')->willReturn('valid_csrf_token');

        $studentService->method('listStudents')->willReturn([
            'items' => [
                [
                    'id' => 5,
                    'name' => 'Mariana Silva',
                    'email' => 'mariana@test.com',
                    'username' => 'mariana',
                    'role' => 'user',
                    'status' => 'active',
                    'courses_count' => 1,
                    'enrolled_courses' => [['course_id' => 1, 'title' => 'Design Patterns']],
                    'created_at' => '2026-11-02 12:00:00'
                ]
            ],
            'total' => 1,
            'page' => 1,
            'per_page' => 15
        ]);

        $studentService->method('getStudentMetrics')->willReturn([
            'total_students' => 10,
            'active_students' => 8,
            'suspended_students' => 2,
            'total_enrollments' => 15
        ]);

        $courseRepo->method('findAll')->willReturn([
            ['id' => 1, 'title' => 'Design Patterns']
        ]);

        $controller = new StudentAdminController($studentService, $courseRepo, $session);

        // 1. Testa Renderização do Cockpit
        $request = new Request(['q' => '', 'status' => 'all'], [], [], [], []);
        $response = $controller->index($request);
        $this->assertInstanceOf(ViewResponse::class, $response);
        $html = $response->getContent();
        $this->assertStringContainsString('Alunos & Matrículas', $html);
        $this->assertStringContainsString('Mariana Silva', $html);
        $this->assertStringContainsString('Design Patterns', $html);

        // 2. Testa Mudança de Status
        $studentService->expects($this->once())
            ->method('setStudentStatus')
            ->with(5, 'banned')
            ->willReturn(true);

        $session->expects($this->once())
            ->method('setFlash')
            ->with('success', $this->stringContains('banido com sucesso'));

        $statusReq = new Request([], ['csrf_token' => 'valid_csrf_token', 'status' => 'banned'], [], [], []);
        $controller->changeStatus($statusReq, 5);
    }

    public function testLoginRejectionForBannedAndSuspendedUsers(): void
    {
        // 1. AuthController rejeitando usuário banido
        $theme = $this->createMock(ThemeManager::class);
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $twoFactor = $this->createMock(TwoFactorService::class);
        $cockpit = $this->createMock(\DomainSystem\Core\Contracts\CockpitRegistryInterface::class);
        $session = $this->createMock(\DomainSystem\Core\Security\SessionManager::class);

        $passwordHash = password_hash('secret123', PASSWORD_DEFAULT);

        // Usuário banido
        $userRepo->method('findByEmail')->with('banned@test.com')->willReturn([
            'id' => 99,
            'name' => 'Infrator',
            'email' => 'banned@test.com',
            'password' => $passwordHash,
            'role' => 'user',
            'status' => 'banned',
            'two_factor_type' => 'none'
        ]);

        $session->expects($this->once())
            ->method('set')
            ->with('auth_error', $this->stringContains('Esta conta foi banida'));

        $authController = new AuthController($theme, $userRepo, $twoFactor, $cockpit, $session);
        $request = new Request([], ['email' => 'banned@test.com', 'password' => 'secret123'], [], [], []);
        $response = $authController->authenticate($request);
        $this->assertSame(302, $response->getStatusCode());
    }

    public function testStudentAuthControllerRejectionForSuspendedStudent(): void
    {
        $session = $this->createMock(SessionManagerInterface::class);
        $session->method('getCsrfToken')->willReturn('token_aluno');

        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY,
                name VARCHAR(100),
                email VARCHAR(100),
                password VARCHAR(255),
                role VARCHAR(50),
                status VARCHAR(20)
            )
        ");

        $hash = password_hash('senha123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO users (id, name, email, password, role, status) VALUES (1, 'Aluno Suspenso', 'suspenso@test.com', '{$hash}', 'student', 'suspended')");

        $studentAuth = $this->createMock(StudentAuthServiceInterface::class);
        $studentAuth->expects($this->once())
            ->method('setFlash')
            ->with('error', $this->stringContains('temporariamente suspensa'));

        $controller = new StudentAuthController($session, $pdo, $studentAuth);
        $request = new Request([], ['csrf_token' => 'token_aluno', 'email' => 'suspenso@test.com', 'password' => 'senha123'], [], [], []);
        $response = $controller->authenticate($request);
        $this->assertSame(302, $response->getStatusCode());
    }

    public function testAcademyAdminMenuIncludesSubmenu(): void
    {
        $pluginPath = dirname(__DIR__, 2) . '/src/Plugins/academy';
        $plugin = new \DomainSystem\Plugins\academy\Plugin($pluginPath);
        $runtime = $this->createMock(\DomainSystem\Core\Contracts\OsRuntimeInterface::class);

        $hookCallback = null;
        $runtime->method('onHook')->willReturnCallback(function ($hook, $cb) use (&$hookCallback) {
            if ($hook === 'admin.menu') {
                $hookCallback = $cb;
            }
        });

        $plugin->osBoot($runtime);
        $this->assertNotNull($hookCallback);

        $menu = $hookCallback([], 'admin');
        $this->assertCount(1, $menu);
        $this->assertSame('Academy (Cursos)', $menu[0]['title']);
        $this->assertArrayHasKey('submenu', $menu[0]);
        $this->assertCount(2, $menu[0]['submenu']);
        $this->assertSame('Cursos & Aulas', $menu[0]['submenu'][0]['title']);
        $this->assertSame('Alunos & Matrículas', $menu[0]['submenu'][1]['title']);
        $this->assertSame('/admin/academy/students', $menu[0]['submenu'][1]['url']);
    }

    public function testStudentAdminRepositoryResilienceWithLegacyTableWithoutUsernameOrStatus(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // Cria tabela legada de users SEM username e SEM status
        $pdo->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(50) DEFAULT 'student',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $pdo->exec("INSERT INTO users (name, email, password, role) VALUES ('Legacy Student', 'legacy@test.com', 'hash', 'student')");

        $repo = new PdoStudentAdminRepository($pdo);
        $students = $repo->getPaginatedStudents(1, 20);

        $this->assertCount(1, $students);
        $this->assertSame('Legacy Student', $students[0]['name']);
        $this->assertArrayHasKey('username', $students[0]);
        $this->assertArrayHasKey('status', $students[0]);
        $this->assertSame(1, $repo->countStudents());

        // Testar busca
        $searchResult = $repo->getPaginatedStudents(1, 20, 'Legacy');
        $this->assertCount(1, $searchResult);
    }
}
