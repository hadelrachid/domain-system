<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Plugins\academy\Repositories\PdoCourseRepository;
use DomainSystem\Plugins\academy\Repositories\PdoCourseStructureRepository;
use PDO;
use PHPUnit\Framework\TestCase;

class CourseStructureRepositoryTest extends TestCase
{
    private PDO $pdo;
    private int $courseId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Cria tabela de cursos
        $courseRepo = new PdoCourseRepository($this->pdo);
        $this->courseId = $courseRepo->create([
            'title'       => 'Curso de Arquitetura de Software',
            'slug'        => 'curso-de-arquitetura-de-software',
            'description' => 'Aprenda SOLID e micro-kernels na prática.',
            'status'      => 'published',
        ]);
    }

    public function testEnsureTablesCreatesModulesAndLessons(): void
    {
        $repo = new PdoCourseStructureRepository($this->pdo);
        $repo->ensureTables();

        $modules = $repo->getModules($this->courseId);
        $this->assertIsArray($modules);
        $this->assertEmpty($modules);

        $lessons = $repo->getLessons($this->courseId);
        $this->assertIsArray($lessons);
        $this->assertEmpty($lessons);
    }

    public function testModulesCrud(): void
    {
        $repo = new PdoCourseStructureRepository($this->pdo);
        $repo->ensureTables();

        $modId1 = $repo->createModule($this->courseId, 'Módulo 1: Fundamentos', 1);
        $this->assertGreaterThan(0, $modId1);

        $modId2 = $repo->createModule($this->courseId, 'Módulo 2: Arquitetura SOLID', 2);
        $this->assertGreaterThan(0, $modId2);

        $modules = $repo->getModules($this->courseId);
        $this->assertCount(2, $modules);
        $this->assertSame('Módulo 1: Fundamentos', $modules[0]['title']);

        // Update
        $updated = $repo->updateModule($modId1, ['title' => 'Módulo 1: Introdução ao Kernel']);
        $this->assertTrue($updated);

        $modulesAfter = $repo->getModules($this->courseId);
        $this->assertSame('Módulo 1: Introdução ao Kernel', $modulesAfter[0]['title']);

        // Delete
        $deleted = $repo->deleteModule($modId2);
        $this->assertTrue($deleted);
        $this->assertCount(1, $repo->getModules($this->courseId));
    }

    public function testLessonsCrud(): void
    {
        $repo = new PdoCourseStructureRepository($this->pdo);
        $repo->ensureTables();

        $modId = $repo->createModule($this->courseId, 'Módulo 1: Instalação', 1);

        $lessonId = $repo->createLesson([
            'course_id'   => $this->courseId,
            'module_id'   => $modId,
            'title'       => '01 - Instalação do Sistema',
            'slug'        => '01-instalacao',
            'source_type' => 'theme_file',
            'source'      => 'pages/tutoriais/01-instalacao.php',
            'position'    => 1,
            'status'      => 'published',
        ]);

        $this->assertGreaterThan(0, $lessonId);

        $lesson = $repo->getLessonById($lessonId);
        $this->assertNotNull($lesson);
        $this->assertSame('01 - Instalação do Sistema', $lesson['title']);
        $this->assertSame('01-instalacao', $lesson['slug']);
        $this->assertSame('pages/tutoriais/01-instalacao.php', $lesson['source']);

        $bySlug = $repo->getLessonBySlug($this->courseId, '01-instalacao');
        $this->assertNotNull($bySlug);
        $this->assertSame($lessonId, (int)$bySlug['id']);

        // Update
        $updated = $repo->updateLesson($lessonId, ['title' => '01 - Instalação Rápida']);
        $this->assertTrue($updated);

        $updatedLesson = $repo->getLessonById($lessonId);
        $this->assertSame('01 - Instalação Rápida', $updatedLesson['title']);

        // Delete
        $this->assertTrue($repo->deleteLesson($lessonId));
        $this->assertNull($repo->getLessonById($lessonId));
    }

    public function testReplaceCourseStructureIsAtomic(): void
    {
        $repo = new PdoCourseStructureRepository($this->pdo);
        $repo->ensureTables();

        $structure = [
            [
                'title' => 'Módulo 1: Setup',
                'lessons' => [
                    [
                        'title' => 'Aula 1: Baixando o Repo',
                        'slug' => 'aula-1-baixando-repo',
                        'source_type' => 'theme_file',
                        'source' => 'pages/tutoriais/01-instalacao.php',
                    ],
                    [
                        'title' => 'Aula 2: Escotilha de Emergência',
                        'slug' => 'aula-2-escotilha',
                        'source_type' => 'theme_file',
                        'source' => 'pages/tutoriais/02-a-escotilha-de-emergencia.php',
                    ]
                ]
            ],
            [
                'title' => 'Módulo 2: Plugins',
                'lessons' => [
                    [
                        'title' => 'Aula 3: Pacotes de Plugins',
                        'slug' => 'aula-3-pacotes',
                        'source_type' => 'theme_file',
                        'source' => 'pages/tutoriais/03-pacotes-de-plugins.php',
                    ]
                ]
            ]
        ];

        $result = $repo->replaceCourseStructure($this->courseId, $structure);
        $this->assertTrue($result);

        $modules = $repo->getModules($this->courseId);
        $this->assertCount(2, $modules);

        $lessons = $repo->getLessons($this->courseId);
        $this->assertCount(3, $lessons);
    }
}
