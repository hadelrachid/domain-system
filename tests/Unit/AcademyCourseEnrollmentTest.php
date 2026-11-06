<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DateTimeImmutable;
use DomainSystem\Plugins\academy\Contracts\CourseRepositoryInterface;
use DomainSystem\Plugins\academy\Repositories\PdoCourseEnrollmentRepository;
use DomainSystem\Plugins\academy\Services\CourseEnrollmentService;
use PDO;
use PHPUnit\Framework\TestCase;

final class AcademyCourseEnrollmentTest extends TestCase
{
    private PDO $pdo;
    private PdoCourseEnrollmentRepository $repository;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->repository = new PdoCourseEnrollmentRepository($this->pdo);
    }

    public function testEnrollmentLifecycleActiveExpireAndRevoke(): void
    {
        $service = new CourseEnrollmentService($this->repository);

        $studentId = 42;
        $courseId = 10;

        // 1. Sem matrícula inicial
        $this->assertFalse($service->hasActiveEnrollment($studentId, $courseId));

        // 2. Efetiva matrícula vitalícia
        $enrolled = $service->enroll($studentId, $courseId, null, 'PED_1001');
        $this->assertTrue($enrolled);
        $this->assertTrue($service->hasActiveEnrollment($studentId, $courseId));
        $this->assertContains($courseId, $service->getActiveCourseIds($studentId));

        // 3. Matrícula com data expirada
        $yesterday = (new DateTimeImmutable())->modify('-1 day');
        $service->enroll($studentId, $courseId, $yesterday);
        $this->assertFalse($service->hasActiveEnrollment($studentId, $courseId));

        // 4. Renova com data futura
        $nextMonth = (new DateTimeImmutable())->modify('+30 days');
        $service->enroll($studentId, $courseId, $nextMonth);
        $this->assertTrue($service->hasActiveEnrollment($studentId, $courseId));

        // 5. Revoga matrícula (cancelamento / estorno)
        $revoked = $service->revoke($studentId, $courseId);
        $this->assertTrue($revoked);
        $this->assertFalse($service->hasActiveEnrollment($studentId, $courseId));
    }

    public function testFreeCourseGrantsActiveEnrollmentRegardlessOfDatabase(): void
    {
        $courseRepo = $this->createMock(CourseRepositoryInterface::class);
        $courseRepo->method('findById')->with(99)->willReturn([
            'id'      => 99,
            'title'   => 'Curso Introdutório Gratuito',
            'is_free' => 1,
            'price'   => 0.00,
        ]);

        $service = new CourseEnrollmentService($this->repository, $courseRepo);

        // Aluno nunca matriculado tem acesso liberado por ser curso gratuito
        $this->assertTrue($service->hasActiveEnrollment(77, 99));
    }

    public function testCountEnrolledInCourse(): void
    {
        $service = new CourseEnrollmentService($this->repository);
        $courseId = 5;

        $service->enroll(1, $courseId);
        $service->enroll(2, $courseId);
        $service->enroll(3, $courseId);

        $this->assertSame(3, $this->repository->countEnrolledInCourse($courseId));

        $service->revoke(2, $courseId);
        $this->assertSame(2, $this->repository->countEnrolledInCourse($courseId));
    }

    public function testCourseFrontControllerBlocksPaidLessonWithoutEnrollmentAndUnlocksWhenEnrolled(): void
    {
        $courseRepo = $this->createMock(CourseRepositoryInterface::class);
        $courseRepo->method('findBySlug')->with('curso-pago')->willReturn([
            'id'          => 12,
            'title'       => 'Curso de Especialização',
            'slug'        => 'curso-pago',
            'status'      => 'published',
            'price'       => 197.00,
            'is_free'     => 0,
            'description' => 'Descrição do curso',
        ]);

        $structureRepo = $this->createMock(\DomainSystem\Plugins\academy\Contracts\CourseStructureRepositoryInterface::class);
        $structureRepo->method('getPublishedLessonBySlug')->with(12, 'aula-01')->willReturn([
            'id'          => 101,
            'course_id'   => 12,
            'title'       => 'Aula 01 — Fundamentos',
            'slug'        => 'aula-01',
            'status'      => 'published',
            'is_free'     => 0,
            'content'     => json_encode(['blocks' => [['type' => 'paragraph', 'data' => ['text' => 'Conteúdo Secreto da Aula 01']]]]),
        ]);

        $navService = $this->createMock(\DomainSystem\Plugins\academy\Contracts\CourseNavigationInterface::class);
        $navService->method('renderCourseNav')->willReturn('<nav>menu</nav>');
        $navService->method('getAdjacentLessons')->willReturn(['prev' => null, 'next' => null]);

        $catalogRenderer = $this->createMock(\DomainSystem\Plugins\academy\Contracts\CourseCatalogRendererInterface::class);
        $session = $this->createMock(\DomainSystem\Core\Contracts\SessionManagerInterface::class);
        $session->method('get')->willReturnCallback(function ($k) {
            return $k === 'user_id' ? 42 : ($k === 'user_role' ? 'student' : null);
        });

        $enrollmentService = new CourseEnrollmentService($this->repository, $courseRepo);

        $controller = new \DomainSystem\Plugins\academy\Controllers\CourseFrontController(
            $courseRepo,
            $structureRepo,
            $navService,
            $catalogRenderer,
            null,
            null,
            new \DomainSystem\Plugins\academy\Services\LessonBlockRenderer(),
            null,
            $enrollmentService,
            $session
        );

        // 1. Aluno 42 NÃO está matriculado no curso 12 -> Conteúdo secreto é bloqueado
        $responseBlocked = $controller->showLesson('curso-pago', 'aula-01');
        $htmlBlocked = $responseBlocked->getContent();

        $this->assertStringContainsString('Conteúdo Exclusivo para Alunos Matriculados', $htmlBlocked);
        $this->assertStringContainsString('Matricular-se por R$ 197,00', $htmlBlocked);
        $this->assertStringNotContainsString('Conteúdo Secreto da Aula 01', $htmlBlocked);

        // 2. Aluno 42 É matriculado no curso 12 -> Conteúdo secreto é liberado
        $enrollmentService->enroll(42, 12);
        $responseUnlocked = $controller->showLesson('curso-pago', 'aula-01');
        $htmlUnlocked = $responseUnlocked->getContent();

        $this->assertStringNotContainsString('Conteúdo Exclusivo para Alunos Matriculados', $htmlUnlocked);
        $this->assertStringContainsString('Conteúdo Secreto da Aula 01', $htmlUnlocked);
    }
}
