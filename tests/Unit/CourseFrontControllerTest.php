<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Core\Contracts\ThemeManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Plugins\academy\Controllers\CourseFrontController;
use DomainSystem\Plugins\academy\Repositories\PdoCourseRepository;
use DomainSystem\Plugins\academy\Repositories\PdoCourseStructureRepository;
use DomainSystem\Plugins\academy\Services\CourseCatalogRenderer;
use DomainSystem\Plugins\academy\Services\CourseNavigationService;
use DomainSystem\Plugins\seo\Contracts\SeoManagerInterface;
use PDO;
use PHPUnit\Framework\TestCase;

class CourseFrontControllerTest extends TestCase
{
    private PDO $pdo;
    private PdoCourseRepository $courseRepo;
    private PdoCourseStructureRepository $structureRepo;
    private CourseNavigationService $navService;
    private CourseCatalogRenderer $catalogRenderer;
    private $seoMock;
    private $themeManagerMock;
    private CourseFrontController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->courseRepo = new PdoCourseRepository($this->pdo);
        $this->structureRepo = new PdoCourseStructureRepository($this->pdo);
        $this->navService = new CourseNavigationService($this->courseRepo, $this->structureRepo, '');
        $this->catalogRenderer = new CourseCatalogRenderer($this->courseRepo);

        $this->seoMock = $this->createMock(SeoManagerInterface::class);
        $this->seoMock->method('setTitle')->willReturnSelf();
        $this->seoMock->method('setDescription')->willReturnSelf();

        $this->themeManagerMock = $this->createMock(ThemeManagerInterface::class);
        $this->themeManagerMock->method('getActiveThemePath')->willReturn(__DIR__);

        $this->controller = new CourseFrontController(
            $this->courseRepo,
            $this->structureRepo,
            $this->navService,
            $this->catalogRenderer,
            $this->seoMock,
            $this->themeManagerMock
        );
    }

    public function testIndexRendersCourseCatalog(): void
    {
        $this->courseRepo->create([
            'title'       => 'Curso de Arquitetura de Software',
            'slug'        => 'arquitetura-software',
            'description' => 'Aprenda padrões modernos de engenharia.',
            'status'      => 'published',
            'link'        => null,
        ]);

        $response = $this->controller->index(new Request());

        $this->assertSame(200, $response->getStatusCode());
        $html = $response->getContent();
        $this->assertStringContainsString('Curso de Arquitetura de Software', $html);
        $this->assertStringContainsString('academy-grid', $html);
        $this->assertStringContainsString('/cursos/arquitetura-software', $html);
    }

    public function testShowCourseReturns404WhenNotFound(): void
    {
        $response = $this->controller->showCourse('curso-inexistente');
        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringContainsString('Curso não encontrado', $response->getContent());
    }

    public function testShowCourseRendersOverviewAndNav(): void
    {
        $courseId = $this->courseRepo->create([
            'title'       => 'Mastering OS 2.0',
            'slug'        => 'mastering-os-2-0',
            'description' => 'Aprenda tudo sobre o kernel modular.',
            'status'      => 'published',
            'link'        => null,
        ]);

        $this->structureRepo->replaceCourseStructure($courseId, [
            [
                'title'    => 'Módulo 1: Setup',
                'position' => 1,
                'lessons'  => [
                    [
                        'title'      => '01 - Instalação',
                        'slug'       => '01-instalacao',
                        'theme_file' => null,
                        'content'    => 'Passo a passo de instalação.',
                        'position'   => 1,
                    ],
                ],
            ],
        ]);

        $response = $this->controller->showCourse('mastering-os-2-0');

        $this->assertSame(200, $response->getStatusCode());
        $html = $response->getContent();
        $this->assertStringContainsString('Mastering OS 2.0', $html);
        $this->assertStringContainsString('Aprenda tudo sobre o kernel modular.', $html);
        $this->assertStringContainsString('class="course-nav ds-academy-nav"', $html);
        $this->assertStringContainsString('01 - Instalação', $html);
        $this->assertStringContainsString('/cursos/mastering-os-2-0/01-instalacao', $html);
    }

    public function testShowLessonReturns404WhenCourseOrLessonNotFound(): void
    {
        $responseCourseNotFound = $this->controller->showLesson('inexistente', 'aula-1');
        $this->assertSame(404, $responseCourseNotFound->getStatusCode());

        $courseId = $this->courseRepo->create([
            'title'  => 'Curso Ativo',
            'slug'   => 'curso-ativo',
            'status' => 'published',
        ]);

        $responseLessonNotFound = $this->controller->showLesson('curso-ativo', 'aula-fantasma');
        $this->assertSame(404, $responseLessonNotFound->getStatusCode());
        $this->assertStringContainsString('Aula não encontrada', $responseLessonNotFound->getContent());
    }

    public function testShowLessonRendersContentWithNavAndAdjacent(): void
    {
        $courseId = $this->courseRepo->create([
            'title'       => 'Mastering OS 2.0',
            'slug'        => 'mastering-os-2-0',
            'description' => 'Aprenda o kernel.',
            'status'      => 'published',
        ]);

        $this->structureRepo->replaceCourseStructure($courseId, [
            [
                'title'    => 'Módulo 1',
                'position' => 1,
                'lessons'  => [
                    [
                        'title'      => '01 - Introdução',
                        'slug'       => '01-introducao',
                        'theme_file' => null,
                        'content'    => '<p>Conteúdo exclusivo da aula 1 sobre micro-kernel.</p>',
                        'position'   => 1,
                    ],
                    [
                        'title'      => '02 - Plugins',
                        'slug'       => '02-plugins',
                        'theme_file' => null,
                        'content'    => '<p>Conteúdo da aula 2 sobre plugins.</p>',
                        'position'   => 2,
                    ],
                ],
            ],
        ]);

        $response = $this->controller->showLesson('mastering-os-2-0', '01-introducao');

        $this->assertSame(200, $response->getStatusCode());
        $html = $response->getContent();

        // 1. Navigation Sidebar presente
        $this->assertStringContainsString('class="course-nav ds-academy-nav"', $html);
        $this->assertStringContainsString('is-active current-lesson', $html);

        // 2. Conteúdo da aula renderizado
        $this->assertStringContainsString('Conteúdo exclusivo da aula 1 sobre micro-kernel.', $html);

        // 3. Paginação / Adjacentes: Próxima aula linkada
        $this->assertStringContainsString('/cursos/mastering-os-2-0/02-plugins', $html);
        $this->assertStringContainsString('Próxima Aula', $html);
    }
}
