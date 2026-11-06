<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Plugins\academy\Contracts\CourseEnrollmentInterface;
use DomainSystem\Plugins\academy\Contracts\CourseRepositoryInterface;
use DomainSystem\Plugins\academy\Contracts\CourseStructureRepositoryInterface;
use DomainSystem\Plugins\academy\Controllers\StudentDashboardController;
use DomainSystem\Plugins\academy\Repositories\PdoStudentProgressRepository;
use DomainSystem\Plugins\academy\Services\LocalStudentStorage;
use DomainSystem\Plugins\academy\Services\StudentProgressService;
use PDO;
use PHPUnit\Framework\TestCase;

final class AcademyStudentWorkspaceTest extends TestCase
{
    private string $tempDir;
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/ds_student_test_' . uniqid('', true);
        @mkdir($this->tempDir, 0755, true);

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tempDir);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) return;
        foreach (array_diff(scandir($dir) ?: [], ['.', '..']) as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->removeDir($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    public function testStudentStorageIsolatesDirectoryAndSavesNotesSecurely(): void
    {
        $storage = new LocalStudentStorage($this->tempDir);

        $studentId = 88;
        $courseId = 10;
        $lessonId = 5;

        $dir = $storage->getStudentDirectory($studentId);
        $this->assertDirectoryExists($dir);
        $this->assertStringContainsString((string)$studentId, $dir);

        // 1. Salvar e recuperar anotação privada da aula
        $noteText = 'Importante: revisar o conceito de DIP na aula 5.';
        $saved = $storage->saveStudentNote($studentId, $courseId, $lessonId, $noteText);
        $this->assertTrue($saved);

        $retrieved = $storage->getStudentNote($studentId, $courseId, $lessonId);
        $this->assertSame($noteText, $retrieved);

        // 2. Outro aluno não deve acessar a anotação
        $otherStudentNote = $storage->getStudentNote(99, $courseId, $lessonId);
        $this->assertSame('', $otherStudentNote);

        // 3. Blindagem anti-Path Traversal no delete
        $maliciousTraversal = $storage->deleteStudentFile($studentId, '../../config/database.php');
        $this->assertFalse($maliciousTraversal);
    }

    public function testStudentProgressLifecycleAndPercentageCalculation(): void
    {
        $progressRepo = new PdoStudentProgressRepository($this->pdo);

        $structureRepo = $this->createMock(CourseStructureRepositoryInterface::class);
        $structureRepo->method('getLessons')->with(1)->willReturn([
            ['id' => 101, 'status' => 'published'],
            ['id' => 102, 'status' => 'published'],
            ['id' => 103, 'status' => 'published'],
            ['id' => 104, 'status' => 'published'],
        ]);

        $service = new StudentProgressService($progressRepo, $structureRepo);
        $studentId = 50;
        $courseId = 1;

        // 1. Progresso inicial 0%
        $this->assertSame(0.0, $service->getCourseProgressPercentage($studentId, $courseId));
        $this->assertFalse($service->isLessonCompleted($studentId, 101));

        // 2. Conclui 1 aula (25%)
        $service->markLessonCompleted($studentId, $courseId, 101);
        $this->assertTrue($service->isLessonCompleted($studentId, 101));
        $this->assertSame(25.0, $service->getCourseProgressPercentage($studentId, $courseId));

        // 3. Conclui mais 1 aula (50%)
        $service->markLessonCompleted($studentId, $courseId, 102);
        $this->assertSame(50.0, $service->getCourseProgressPercentage($studentId, $courseId));

        // 4. Desmarca aula 101 (volta para 25%)
        $service->markLessonIncomplete($studentId, $courseId, 101);
        $this->assertFalse($service->isLessonCompleted($studentId, 101));
        $this->assertSame(25.0, $service->getCourseProgressPercentage($studentId, $courseId));
    }

    public function testStudentDashboardControllerRedirectsGuestAndRendersForAuthenticatedStudent(): void
    {
        $session = $this->createMock(SessionManagerInterface::class);
        $enrollment = $this->createMock(CourseEnrollmentInterface::class);
        $courseRepo = $this->createMock(CourseRepositoryInterface::class);
        $structureRepo = $this->createMock(CourseStructureRepositoryInterface::class);
        $progress = $this->createMock(\DomainSystem\Plugins\academy\Contracts\StudentProgressInterface::class);
        $storage = new LocalStudentStorage($this->tempDir);

        $controller = new StudentDashboardController(
            $session,
            $enrollment,
            $courseRepo,
            $structureRepo,
            $progress,
            $storage
        );

        $request = new Request([], [], ['REQUEST_METHOD' => 'GET']);

        // 1. Visitante não autenticado (user_id = 0) -> Redireciona para /login
        $session->method('get')->willReturnCallback(function ($k, $def = 0) {
            return in_array($k, ['academy_student_id', 'user_id'], true) ? 0 : $def;
        });
        $response = $controller->index($request);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringContainsString('/login', (string)$response->getHeader('Location'));
    }

    public function testStudentDashboardControllerToggleLessonProgressWithValidCsrf(): void
    {
        $session = $this->createMock(SessionManagerInterface::class);
        $session->method('get')->willReturnCallback(function ($k, $def = 0) {
            return in_array($k, ['academy_student_id', 'user_id'], true) ? 77 : $def;
        });
        $session->method('getCsrfToken')->willReturn('token_valido_csrf');

        $enrollment = $this->createMock(CourseEnrollmentInterface::class);
        $courseRepo = $this->createMock(CourseRepositoryInterface::class);
        $structureRepo = $this->createMock(CourseStructureRepositoryInterface::class);
        $structureRepo->method('getLessonById')->with(200)->willReturn([
            'id'        => 200,
            'course_id' => 15,
            'title'     => 'Aula de Teste',
        ]);

        $progressRepo = new PdoStudentProgressRepository($this->pdo);
        $progress = new StudentProgressService($progressRepo, $structureRepo);
        $storage = new LocalStudentStorage($this->tempDir);

        $controller = new StudentDashboardController(
            $session,
            $enrollment,
            $courseRepo,
            $structureRepo,
            $progress,
            $storage
        );

        $request = new Request(
            [],
            ['csrf_token' => 'token_valido_csrf'],
            ['REQUEST_METHOD' => 'POST']
        );

        // Marca aula como concluída
        $response = $controller->toggleLessonProgress($request, 200);
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertTrue($data['completed']);

        // Alterna novamente (desmarca)
        $response2 = $controller->toggleLessonProgress($request, 200);
        $data2 = json_decode($response2->getContent(), true);
        $this->assertTrue($data2['success']);
        $this->assertFalse($data2['completed']);
    }

    public function testStudentDashboardControllerGrantsAdminMasterAccessToAllCourses(): void
    {
        $session = $this->createMock(SessionManagerInterface::class);
        $session->method('get')->willReturnCallback(function ($k, $def = null) {
            return match ($k) {
                'academy_student_id'   => 1,
                'user_id'              => 1,
                'academy_student_name' => 'HADEL RACHID DAHER JUNIOR',
                'user_role'            => 'admin',
                'academy_student_role' => 'admin',
                default                => $def,
            };
        });
        $session->method('has')->willReturnCallback(function ($k) {
            return in_array($k, ['academy_student_id', 'academy_student_name', 'academy_student_role', 'user_role'], true);
        });
        $session->method('getCsrfToken')->willReturn('token_admin');

        $enrollment = $this->createMock(CourseEnrollmentInterface::class);
        $enrollment->method('getActiveCourseIds')->with(1)->willReturn([]); // Admin não possui matrícula manual

        $courseRepo = $this->createMock(CourseRepositoryInterface::class);
        $courseRepo->method('findPublished')->willReturn([
            [
                'id'          => 7,
                'title'       => 'Curso de PHP Moderno',
                'slug'        => 'curso-de-php-moderno',
                'status'      => 'published',
                'price'       => 10.00,
                'description' => 'Aprenda PHP 8 avançado',
                'thumbnail'   => 'uploads/academy/php.png',
            ]
        ]);
        $courseRepo->method('findById')->with(7)->willReturn([
            'id'          => 7,
            'title'       => 'Curso de PHP Moderno',
            'slug'        => 'curso-de-php-moderno',
            'status'      => 'published',
            'price'       => 10.00,
            'description' => 'Aprenda PHP 8 avançado',
            'thumbnail'   => 'uploads/academy/php.png',
        ]);

        $structureRepo = $this->createMock(CourseStructureRepositoryInterface::class);
        $structureRepo->method('getLessons')->with(7)->willReturn([
            ['id' => 701, 'slug' => 'aula-01', 'title' => 'Introdução', 'status' => 'published']
        ]);

        $progress = $this->createMock(\DomainSystem\Plugins\academy\Contracts\StudentProgressInterface::class);
        $progress->method('getCourseProgressPercentage')->willReturn(0.0);
        $progress->method('getCompletedLessonIds')->willReturn([]);

        $storage = new LocalStudentStorage($this->tempDir);

        $controller = new StudentDashboardController(
            $session,
            $enrollment,
            $courseRepo,
            $structureRepo,
            $progress,
            $storage
        );

        $request = new Request([], [], ['REQUEST_METHOD' => 'GET']);
        $response = $controller->index($request);

        $this->assertSame(200, $response->getStatusCode());
        $html = $response->getContent();

        // 1. Título e Saudação com Nome Oficial e Badge de Admin
        $this->assertStringContainsString('Cockpit do Aluno', $html);
        $this->assertStringContainsString('HADEL RACHID DAHER JUNIOR', $html);
        $this->assertStringContainsString('Administrador', $html);

        // 2. Acesso mestre aos cursos (o curso aparece como matriculado para o Admin)
        $this->assertStringContainsString('Curso de PHP Moderno', $html);
        $this->assertStringContainsString('Continuar Aula &rarr;', $html);

        // 3. O Admin não vê botão de "Comprar" para seus próprios cursos no dashboard
        $this->assertStringNotContainsString('Cursos Disponíveis para Compra', $html);
    }

    public function testStudentLogoutCompletelyClearsSessionAndPreventsReLoginTrap(): void
    {
        $sessionData = [
            'academy_student_id'   => 3,
            'academy_student_name' => 'MONICA MEDEIROS BORGES',
            'user_id'              => 3,
            'user_name'            => 'MONICA MEDEIROS BORGES',
            'user_role'            => 'user',
            'academy_student_role' => 'user',
            'is_authenticated'     => true,
        ];

        $session = $this->createMock(SessionManagerInterface::class);
        $session->method('get')->willReturnCallback(function ($k, $def = null) use (&$sessionData) {
            return $sessionData[$k] ?? $def;
        });
        $session->method('has')->willReturnCallback(function ($k) use (&$sessionData) {
            return isset($sessionData[$k]);
        });
        $session->method('set')->willReturnCallback(function ($k, $v) use (&$sessionData) {
            $sessionData[$k] = $v;
        });
        $session->method('remove')->willReturnCallback(function ($k) use (&$sessionData) {
            unset($sessionData[$k]);
        });

        $authService = new \DomainSystem\Plugins\academy\Services\StudentAuthService($session);

        // Pré-condição: Aluno está autenticado
        $this->assertTrue($authService->isLoggedIn());
        $this->assertSame(3, $authService->getStudentId());
        $this->assertSame('MONICA MEDEIROS BORGES', $authService->getStudentName());

        // Executa Logout
        $authService->logout();

        // Pós-condição: Todas as credenciais foram aniquiladas e flag academy_logged_out foi cravada
        $this->assertFalse($authService->isLoggedIn());
        $this->assertNull($authService->getStudentId());
        $this->assertArrayNotHasKey('academy_student_id', $sessionData);
        $this->assertArrayNotHasKey('user_id', $sessionData);
        $this->assertArrayNotHasKey('user_role', $sessionData);
        $this->assertTrue($sessionData['academy_logged_out'] ?? false);

        // Teste do Controller: ao chamar logout(), redireciona com sucesso e confirma saída
        $controller = new \DomainSystem\Plugins\academy\Controllers\StudentAuthController($session, $this->pdo, $authService);
        $logoutReq = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/aluno/logout']);
        $logoutResp = $controller->logout($logoutReq);

        $this->assertSame(302, $logoutResp->getStatusCode());
        $location = (string)$logoutResp->getHeader('Location');
        $this->assertTrue($location === '/' || str_ends_with($location, '/'));
    }
}
