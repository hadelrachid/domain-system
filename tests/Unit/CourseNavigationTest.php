<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Plugins\academy\Contracts\CourseNavigationInterface;
use DomainSystem\Plugins\academy\Repositories\PdoCourseRepository;
use DomainSystem\Plugins\academy\Repositories\PdoCourseStructureRepository;
use DomainSystem\Plugins\academy\Services\CourseNavigationService;
use PDO;
use PHPUnit\Framework\TestCase;

class CourseNavigationTest extends TestCase
{
    private PDO $pdo;
    private PdoCourseRepository $courseRepo;
    private PdoCourseStructureRepository $structureRepo;
    private CourseNavigationService $navService;
    private int $courseId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->courseRepo = new PdoCourseRepository($this->pdo);
        $this->structureRepo = new PdoCourseStructureRepository($this->pdo);

        $this->courseId = $this->courseRepo->create([
            'title'       => 'Mastering OS 2.0',
            'slug'        => 'mastering-os-2-0',
            'description' => 'Aprenda o ciclo de vida do micro-kernel.',
            'status'      => 'published',
        ]);

        $this->structureRepo->replaceCourseStructure($this->courseId, [
            [
                'title' => 'Módulo 1: Setup',
                'lessons' => [
                    [
                        'title' => '01 - Instalação',
                        'slug'  => '01-instalacao',
                        'source_type' => 'theme_file',
                        'source' => 'pages/tutoriais/01-instalacao.php',
                    ],
                    [
                        'title' => '02 - Escotilha de Emergência',
                        'slug'  => '02-emergencia',
                        'source_type' => 'theme_file',
                        'source' => 'pages/tutoriais/02-a-escotilha-de-emergencia.php',
                    ]
                ]
            ],
            [
                'title' => 'Módulo 2: Extensões',
                'lessons' => [
                    [
                        'title' => '03 - Pacotes de Plugins',
                        'slug'  => '03-pacotes',
                        'source_type' => 'theme_file',
                        'source' => 'pages/tutoriais/03-pacotes-de-plugins.php',
                    ]
                ]
            ]
        ]);

        $this->navService = new CourseNavigationService($this->courseRepo, $this->structureRepo);
    }

    public function testImplementsCourseNavigationInterface(): void
    {
        $this->assertInstanceOf(CourseNavigationInterface::class, $this->navService);
    }

    public function testGetCourseTreeReturnsStructuredTree(): void
    {
        $tree = $this->navService->getCourseTree('mastering-os-2-0');

        $this->assertCount(2, $tree);
        $this->assertSame('Módulo 1: Setup', $tree[0]['title']);
        $this->assertCount(2, $tree[0]['lessons']);
        $this->assertSame('01 - Instalação', $tree[0]['lessons'][0]['title']);
        $this->assertSame('01-instalacao', $tree[0]['lessons'][0]['slug']);

        $this->assertSame('Módulo 2: Extensões', $tree[1]['title']);
        $this->assertCount(1, $tree[1]['lessons']);
        $this->assertSame('03 - Pacotes de Plugins', $tree[1]['lessons'][0]['title']);
    }

    public function testRenderCourseNavOutputsAccessibleNav(): void
    {
        $html = $this->navService->renderCourseNav('mastering-os-2-0', '02-emergencia');

        $this->assertStringContainsString('<nav', $html);
        $this->assertStringContainsString('class="course-nav', $html);
        $this->assertStringContainsString('Módulo 1: Setup', $html);
        $this->assertStringContainsString('01 - Instalação', $html);
        $this->assertStringContainsString('02 - Escotilha de Emergência', $html);
        $this->assertStringContainsString('is-active', $html);
        $this->assertStringContainsString('/cursos/mastering-os-2-0/02-emergencia', $html);
    }

    public function testGetAdjacentLessonsReturnsPrevAndNext(): void
    {
        // Aula 1 (primeira): prev null, next aula 2
        $adj1 = $this->navService->getAdjacentLessons('mastering-os-2-0', '01-instalacao');
        $this->assertNull($adj1['prev']);
        $this->assertNotNull($adj1['next']);
        $this->assertSame('02-emergencia', $adj1['next']['slug']);

        // Aula 2 (meio): prev aula 1, next aula 3 (do próximo módulo!)
        $adj2 = $this->navService->getAdjacentLessons('mastering-os-2-0', '02-emergencia');
        $this->assertNotNull($adj2['prev']);
        $this->assertSame('01-instalacao', $adj2['prev']['slug']);
        $this->assertNotNull($adj2['next']);
        $this->assertSame('03-pacotes', $adj2['next']['slug']);

        // Aula 3 (última): prev aula 2, next null
        $adj3 = $this->navService->getAdjacentLessons('mastering-os-2-0', '03-pacotes');
        $this->assertNotNull($adj3['prev']);
        $this->assertSame('02-emergencia', $adj3['prev']['slug']);
        $this->assertNull($adj3['next']);
    }
}
