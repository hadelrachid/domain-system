<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Contracts\ThemeManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Plugins\academy\Contracts\CourseImageStorageInterface;
use DomainSystem\Plugins\academy\Controllers\CourseAdminController;
use DomainSystem\Plugins\academy\Controllers\CourseFrontController;
use DomainSystem\Plugins\academy\Controllers\LessonAdminController;
use DomainSystem\Plugins\academy\Repositories\PdoCourseRepository;
use DomainSystem\Plugins\academy\Repositories\PdoCourseStructureRepository;
use DomainSystem\Plugins\academy\Services\CourseCatalogRenderer;
use DomainSystem\Plugins\academy\Services\CourseNavigationService;
use DomainSystem\Plugins\academy\Services\CourseService;
use DomainSystem\Plugins\academy\Services\LessonBlockRenderer;
use DomainSystem\Plugins\seo\Contracts\SeoManagerInterface;
use PDO;
use PHPUnit\Framework\TestCase;

class AcademyBlockEditorTest extends TestCase
{
    private PDO $pdo;
    private PdoCourseRepository $courseRepo;
    private PdoCourseStructureRepository $structureRepo;
    private LessonBlockRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->courseRepo = new PdoCourseRepository($this->pdo);
        $this->structureRepo = new PdoCourseStructureRepository($this->pdo);
        $this->renderer = new LessonBlockRenderer();
    }

    public function testRenderAllEightNativeBlockTypes(): void
    {
        $json = json_encode([
            'time' => 1729100000,
            'blocks' => [
                [
                    'id' => 'b1',
                    'type' => 'header',
                    'data' => ['level' => 2, 'text' => 'Arquitetura SOLID no Kernel']
                ],
                [
                    'id' => 'b2',
                    'type' => 'paragraph',
                    'data' => ['text' => 'Aplicando o <strong>Princípio da Responsabilidade Única</strong> com <code>Container</code>.']
                ],
                [
                    'id' => 'b3',
                    'type' => 'video',
                    'data' => ['url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'caption' => 'Aula em vídeo 16:9']
                ],
                [
                    'id' => 'b4',
                    'type' => 'code',
                    'data' => ['language' => 'php', 'code' => "<?php\n\$kernel->boot();"]
                ],
                [
                    'id' => 'b5',
                    'type' => 'callout',
                    'data' => ['type' => 'success', 'text' => 'Inversão de dependência validada!']
                ],
                [
                    'id' => 'b6',
                    'type' => 'list',
                    'data' => ['style' => 'ordered', 'items' => ['Passo 1: Contrato', 'Passo 2: Implementação']]
                ],
                [
                    'id' => 'b7',
                    'type' => 'image',
                    'data' => ['url' => '/assets/img/diagrama-solid.png', 'caption' => 'Diagrama de classes']
                ],
                [
                    'id' => 'b8',
                    'type' => 'delimiter',
                    'data' => []
                ],
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $html = $this->renderer->render((string)$json);

        $this->assertStringContainsString('class="ds-lesson-blocks"', $html);
        $this->assertStringContainsString('<h2 class="ds-block-heading ds-heading-h2"', $html);
        $this->assertStringContainsString('Arquitetura SOLID no Kernel</h2>', $html);
        $this->assertStringContainsString('<strong>Princípio da Responsabilidade Única</strong>', $html);
        $this->assertStringContainsString('https://www.youtube.com/embed/dQw4w9WgXcQ', $html);
        $this->assertStringContainsString('padding-bottom: 56.25%', $html);
        $this->assertStringContainsString('language-php', $html);
        $this->assertStringContainsString('&lt;?php', $html);
        $this->assertStringContainsString('ds-callout-success', $html);
        $this->assertStringContainsString('<ol class="ds-block-list"', $html);
        $this->assertStringContainsString('Passo 1: Contrato', $html);
        $this->assertStringContainsString('/assets/img/diagrama-solid.png', $html);
        $this->assertStringContainsString('class="ds-block-delimiter"', $html);
    }

    public function testSanitizeInlineHtmlBlocksXssAndMaliciousAttributes(): void
    {
        $json = json_encode([
            'blocks' => [
                [
                    'type' => 'paragraph',
                    'data' => [
                        'text' => 'Texto <script>alert("xss")</script> com <strong onmouseover="evil()">destaque</strong> e <a href="javascript:alert(1)" onclick="steal()">link perigoso</a>.'
                    ]
                ]
            ]
        ]);

        $html = $this->renderer->render((string)$json);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('onmouseover', $html);
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringContainsString('<strong>destaque</strong>', $html);
        $this->assertStringContainsString('href="#"', $html);
    }

    public function testConvertVideoUrlSupportsYoutubeVimeoAndDirectMp4(): void
    {
        $this->assertSame(
            'https://www.youtube.com/embed/abc123XYZ_-',
            $this->renderer->convertVideoUrlToEmbed('https://youtu.be/abc123XYZ_-')
        );
        $this->assertSame(
            'https://player.vimeo.com/video/987654321',
            $this->renderer->convertVideoUrlToEmbed('https://vimeo.com/987654321')
        );
        $this->assertNull($this->renderer->convertVideoUrlToEmbed('/uploads/aula-01.mp4'));

        $mp4Html = $this->renderer->renderVideo(['url' => '/uploads/aula-01.mp4', 'caption' => 'Vídeo local']);
        $this->assertStringContainsString('<video controls', $mp4Html);
        $this->assertStringContainsString('/uploads/aula-01.mp4', $mp4Html);
    }

    public function testExtractPlainTextFromBlocksForSeo(): void
    {
        $json = json_encode([
            'blocks' => [
                ['type' => 'header', 'data' => ['text' => 'Introdução']],
                ['type' => 'paragraph', 'data' => ['text' => 'Aprenda <strong>SOLID</strong> na prática.']]
            ]
        ]);

        $plain = $this->renderer->extractPlainText((string)$json);
        $this->assertSame('Introdução Aprenda SOLID na prática.', $plain);
    }

    public function testLessonAdminControllerEditorAndUpdateContent(): void
    {
        $courseId = $this->courseRepo->create([
            'title'       => 'Curso PHP Avançado',
            'slug'        => 'curso-php-avancado',
            'description' => 'Descrição do curso',
            'status'      => 'published',
        ]);

        $moduleId = $this->structureRepo->createModule($courseId, 'Módulo 1', 1);
        $lessonId = $this->structureRepo->createLesson([
            'course_id'   => $courseId,
            'module_id'   => $moduleId,
            'title'       => 'Aula Inicial',
            'slug'        => 'aula-inicial',
            'source_type' => 'content',
            'content'     => '',
            'position'    => 1,
            'status'      => 'published',
        ]);

        $session = $this->createMock(SessionManagerInterface::class);
        $session->method('getCsrfToken')->willReturn('valid_csrf_token');

        $controller = new LessonAdminController(
            $this->courseRepo,
            $this->structureRepo,
            $session
        );

        // 1. Testa abertura do editor visual
        $editorResp = $controller->editor(new Request(), $lessonId);
        $this->assertSame(200, $editorResp->getStatusCode());
        $this->assertStringContainsString('Editor em Blocos da Aula', $editorResp->getContent());
        $this->assertStringContainsString('Aula Inicial', $editorResp->getContent());

        // 2. Testa salvamento de blocos via updateContent (AJAX)
        $blocksPayload = [
            ['id' => 'b1', 'type' => 'header', 'data' => ['level' => 2, 'text' => 'Novo Título em Bloco']],
            ['id' => 'b2', 'type' => 'code', 'data' => ['language' => 'php', 'code' => 'echo "OK";']],
        ];

        $updateReq = new Request(
            [],
            [
                'csrf_token' => 'valid_csrf_token',
                'title'      => 'Aula Atualizada via Blocos',
                'status'     => 'published',
                'blocks'     => $blocksPayload,
            ],
            [
                'REQUEST_METHOD' => 'POST',
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            ]
        );

        $updateResp = $controller->updateContent($updateReq, $lessonId);
        $this->assertSame(200, $updateResp->getStatusCode());

        $savedLesson = $this->structureRepo->getLessonById($lessonId);
        $this->assertNotNull($savedLesson);
        $this->assertSame('Aula Atualizada via Blocos', $savedLesson['title']);
        $this->assertSame('content', $savedLesson['source_type']);
        $this->assertStringContainsString('Novo Título em Bloco', (string)$savedLesson['content']);
    }

    public function testHybridCourseFrontControllerRendersBlockJsonAndThemeFiles(): void
    {
        $courseId = $this->courseRepo->create([
            'title'       => 'Curso Híbrido SOLID',
            'slug'        => 'curso-hibrido-solid',
            'description' => 'Curso com aulas em blocos e em arquivos.',
            'status'      => 'published',
        ]);

        $blocksJson = json_encode([
            'blocks' => [
                ['type' => 'header', 'data' => ['level' => 2, 'text' => 'Cabeçalho Renderizado por Bloco']],
                ['type' => 'callout', 'data' => ['type' => 'info', 'text' => 'Dica importante do bloco']],
            ]
        ]);

        $this->structureRepo->replaceCourseStructure($courseId, [
            [
                'title'    => 'Módulo Híbrido',
                'position' => 1,
                'lessons'  => [
                    [
                        'title'       => 'Aula 01 - Blocos',
                        'slug'        => 'aula-01-blocos',
                        'source_type' => 'content',
                        'content'     => $blocksJson,
                        'status'      => 'published',
                    ],
                ],
            ],
        ]);

        $navService = new CourseNavigationService($this->courseRepo, $this->structureRepo, '');
        $catalogRenderer = new CourseCatalogRenderer($this->courseRepo);
        $seoMock = $this->createMock(SeoManagerInterface::class);
        $seoMock->method('setTitle')->willReturnSelf();
        $seoMock->expects($this->once())
            ->method('setDescription')
            ->with($this->stringContains('Cabeçalho Renderizado por Bloco'));

        $frontController = new CourseFrontController(
            $this->courseRepo,
            $this->structureRepo,
            $navService,
            $catalogRenderer,
            $seoMock
        );

        $response = $frontController->showLesson('curso-hibrido-solid', 'aula-01-blocos');
        $this->assertSame(200, $response->getStatusCode());

        $html = $response->getContent();
        $this->assertStringContainsString('class="ds-lesson-blocks"', $html);
        $this->assertStringContainsString('Cabeçalho Renderizado por Bloco', $html);
        $this->assertStringContainsString('Dica importante do bloco', $html);
    }

    public function testCourseAdminControllerEditRendersHybridControlsAndFolderImport(): void
    {
        $courseId = $this->courseRepo->create([
            'title'       => 'Curso Admin Híbrido',
            'slug'        => 'curso-admin-hibrido',
            'description' => 'Teste do painel',
            'status'      => 'published',
        ]);

        $this->structureRepo->replaceCourseStructure($courseId, [
            [
                'title'    => 'Módulo 1',
                'position' => 1,
                'lessons'  => [
                    [
                        'title'       => 'Aula Teste',
                        'slug'        => 'aula-teste',
                        'source_type' => 'content',
                        'content'     => '',
                        'status'      => 'published',
                    ],
                ],
            ],
        ]);

        $storage = $this->createMock(CourseImageStorageInterface::class);
        $service = new CourseService($this->courseRepo, $storage);
        $session = $this->createMock(SessionManagerInterface::class);
        $session->method('getCsrfToken')->willReturn('csrf_123');

        $themeManager = $this->createMock(ThemeManagerInterface::class);
        $themeManager->method('getAvailablePages')->willReturn([
            'pages/tutoriais/01-instalacao.php',
            'pages/tutoriais/02-a-escotilha-de-emergencia.php',
        ]);

        $pageResolver = new \DomainSystem\Plugins\academy\Services\ThemePageResolver($themeManager);

        $adminController = new CourseAdminController(
            $this->courseRepo,
            $service,
            $session,
            $this->structureRepo,
            $pageResolver
        );

        $response = $adminController->edit(new Request(), $courseId);
        $this->assertSame(200, $response->getStatusCode());
        $html = $response->getContent();

        $this->assertStringContainsString('Importar Pasta do Tema', $html);
        $this->assertStringContainsString('Criar Pasta (ou Módulo)', $html);
        $this->assertStringContainsString('Editor de Blocos', $html);
        $this->assertStringContainsString('pages/tutoriais/01-instalacao.php', $html);
    }

    public function testPluginManualScannerDiscoversAcademyManualModule(): void
    {
        $rootDir = dirname(__DIR__, 2);
        $scanner = new \DomainSystem\Core\Manual\PluginManualScanner($rootDir);
        $modules = $scanner->discover();

        $this->assertNotEmpty($modules, 'PluginManualScanner deve descobrir pelo menos o módulo manual da Academy.');

        $academyModule = null;
        foreach ($modules as $mod) {
            if ($mod->getId() === 'academy') {
                $academyModule = $mod;
                break;
            }
        }

        $this->assertNotNull($academyModule, 'O módulo manual da Academy deve ser auto-descoberto via contrato.');
        $this->assertInstanceOf(\DomainSystem\Core\Contracts\ManualModuleInterface::class, $academyModule);
        $this->assertStringContainsString('Academy', $academyModule->getTitle());

        $checklist = $academyModule->getChecklistItems();
        $this->assertNotEmpty($checklist);
        $this->assertArrayHasKey('id', $checklist[0]);
        $this->assertArrayHasKey('label', $checklist[0]);

        $renderedHtml = $academyModule->renderContent('/domain-system');
        $this->assertStringContainsString('Simulador Mobile/Tablet', $renderedHtml);
        $this->assertStringContainsString('ManualModuleInterface', $renderedHtml);

        // Verifica também os outros 3 manuais auto-descobertos na v2.4.0 (flextheme, pages, seo)
        $discoveredIds = array_map(fn($m) => $m->getId(), $modules);
        $this->assertContains('flextheme', $discoveredIds);
        $this->assertContains('pages', $discoveredIds);
        $this->assertContains('seo', $discoveredIds);
    }

    public function testLessonFileScaffolderFabricatesPhpFileAndDirectCourseSlugRoute(): void
    {
        $tempThemeDir = sys_get_temp_dir() . '/ds_academy_theme_' . uniqid();
        mkdir($tempThemeDir . '/pages', 0775, true);

        try {
            $themeManager = $this->createMock(ThemeManagerInterface::class);
            $themeManager->method('getActiveThemePath')->willReturn($tempThemeDir);

            $pageResolver = new \DomainSystem\Plugins\academy\Services\ThemePageResolver($themeManager);
            $scaffolder = new \DomainSystem\Plugins\academy\Services\ThemeLessonFileScaffolder(
                $pageResolver,
                $this->renderer,
                $tempThemeDir . '/pages'
            );

            // 1. Fabrica arquivo PHP na raiz de pages/ a partir do título "Estrutura de Dados"
            $blocksJson = json_encode([
                'blocks' => [
                    ['type' => 'header', 'data' => ['level' => 2, 'text' => 'Árvores Binárias e Grafos']],
                    ['type' => 'paragraph', 'data' => ['text' => 'Conteúdo pré-configurado para Mobile e Desktop.']],
                ]
            ], JSON_UNESCAPED_UNICODE);

            $res = $scaffolder->fabricateLessonFile(
                'Estrutura de Dados',
                '',
                (string)$blocksJson,
                'cinema'
            );

            $this->assertSame('estrutura-de-dados', $res['slug']);
            $this->assertSame('estrutura-de-dados.php', $res['file_name']);
            $this->assertSame('pages/estrutura-de-dados.php', $res['relative_path']);
            $this->assertFileExists($res['full_path']);

            // Verifica se o arquivo PHP fabricado contém o CSS/JS responsivo e pode extrair os blocos de volta
            $extractedJson = $scaffolder->extractBlocksFromFile('pages/estrutura-de-dados.php');
            $this->assertNotNull($extractedJson);
            $this->assertStringContainsString('Árvores Binárias e Grafos', $extractedJson);

            // 2. Cria curso e aula vinculada ao arquivo fabricado e testa a rota direta /cursos/estrutura-de-dados
            $courseId = $this->courseRepo->create([
                'title'          => 'Ciência da Computação',
                'slug'           => 'ciencia-da-computacao',
                'description'    => 'Curso completo',
                'status'         => 'published',
                'mobile_profile' => 'cinema',
            ]);

            $this->structureRepo->replaceCourseStructure($courseId, [
                [
                    'title'       => 'Módulo 1',
                    'folder_slug' => '',
                    'position'    => 1,
                    'lessons'     => [
                        [
                            'title'          => 'Estrutura de Dados',
                            'slug'           => $res['slug'],
                            'source_type'    => 'theme_file',
                            'file_path'      => $res['relative_path'],
                            'content'        => $blocksJson,
                            'mobile_profile' => 'cinema',
                            'status'         => 'published',
                        ],
                    ],
                ],
            ]);

            $navService = new CourseNavigationService($this->courseRepo, $this->structureRepo, '');
            $catalogRenderer = new CourseCatalogRenderer($this->courseRepo);

            $frontController = new CourseFrontController(
                $this->courseRepo,
                $this->structureRepo,
                $navService,
                $catalogRenderer,
                null,
                $themeManager,
                $this->renderer,
                $pageResolver
            );

            // Rota direta: /cursos/estrutura-de-dados (cai em showCourse('estrutura-de-dados') e resolve a aula!)
            $directResponse = $frontController->showCourse('estrutura-de-dados');
            $this->assertSame(200, $directResponse->getStatusCode());
            $html = $directResponse->getContent();
            $this->assertStringContainsString('Estrutura de Dados', $html);
            $this->assertStringContainsString('Árvores Binárias e Grafos', $html);
            $this->assertStringContainsString('ds-mobile-profile-cinema', $html);
        } finally {
            $this->removeDirectory($tempThemeDir);
        }
    }

    public function testAtomicJsonStoreAndRouteCircuitBreakerThreshold(): void
    {
        $tempDir = sys_get_temp_dir() . '/ds_atomic_test_' . uniqid();
        mkdir($tempDir, 0775, true);

        try {
            $store = new \DomainSystem\Core\Storage\AtomicJsonStore();
            $filePath = $tempDir . '/state.json';

            $this->assertTrue($store->write($filePath, ['count' => 1]));
            $this->assertSame(['count' => 1], $store->read($filePath));

            $updated = $store->update($filePath, function (array $current): array {
                $current['count'] = ($current['count'] ?? 0) + 1;
                return $current;
            });
            $this->assertSame(2, $updated['count']);
            $this->assertSame(2, $store->read($filePath)['count']);

            // Testa CircuitBreaker por rota com limiar 3
            $configPath = $tempDir . '/plugins.json';
            $disarmedPath = $tempDir . '/disarmed.json';
            $store->write($configPath, ['rogue_plugin' => true]);
            $store->write($disarmedPath, []);

            $breaker = new \DomainSystem\Core\Error\CircuitBreaker($configPath, $disarmedPath, $store);
            $ex = new \RuntimeException('Falha simulada em src/Plugins/rogue_plugin/Controller.php');

            $this->assertNull($breaker->recordRouteException($ex, 3));
            $this->assertSame(1, $breaker->getRouteFailureCount('rogue_plugin'));
            $this->assertTrue($store->read($configPath)['rogue_plugin']);

            $this->assertNull($breaker->recordRouteException($ex, 3));
            $this->assertSame(2, $breaker->getRouteFailureCount('rogue_plugin'));
            $this->assertTrue($store->read($configPath)['rogue_plugin']);

            // Na 3ª falha consecutiva, desarma!
            $this->assertSame('rogue_plugin', $breaker->recordRouteException($ex, 3));
            $this->assertFalse($store->read($configPath)['rogue_plugin']);
            $this->assertArrayHasKey('rogue_plugin', $store->read($disarmedPath));
        } finally {
            $this->removeDirectory($tempDir);
        }
    }

    public function testPluginMigrationScannerAndStateSnapshotManager(): void
    {
        $rootDir = dirname(__DIR__, 2);
        $scanner = new \DomainSystem\Core\Database\PluginMigrationScanner($rootDir);

        $discovered = $scanner->discover();
        $this->assertArrayHasKey('academy', $discovered);
        $this->assertCount(2, $discovered['academy']['files']);

        $runner = new \DomainSystem\Core\Database\MigrationRunner($this->pdo);
        $healResult = $scanner->healAll($runner);
        $this->assertEmpty($healResult['errors']);

        $statusReport = $scanner->inspectStatus($runner);
        $this->assertNotEmpty($statusReport);
        $this->assertSame('ok', $statusReport['academy']['status']);
        $this->assertCount(2, $statusReport['academy']['executed']);

        // Testa StateSnapshotManager
        $tempRoot = sys_get_temp_dir() . '/ds_snap_test_' . uniqid();
        mkdir($tempRoot . '/config', 0775, true);
        mkdir($tempRoot . '/storage', 0775, true);

        try {
            $store = new \DomainSystem\Core\Storage\AtomicJsonStore();
            $store->write($tempRoot . '/config/plugins.json', ['academy' => true, 'seo' => true]);

            $snapshotMgr = new \DomainSystem\Core\Storage\StateSnapshotManager($tempRoot, $store);
            $snap = $snapshotMgr->createSnapshot('before-change');

            $this->assertNotEmpty($snap['id']);
            $this->assertCount(1, $snapshotMgr->listSnapshots());

            // Modifica estado atual e restaura snapshot
            $store->write($tempRoot . '/config/plugins.json', ['academy' => false]);
            $this->assertFalse($store->read($tempRoot . '/config/plugins.json')['academy']);

            $this->assertTrue($snapshotMgr->restoreSnapshot($snap['id']));
            $this->assertTrue($store->read($tempRoot . '/config/plugins.json')['academy']);
        } finally {
            $this->removeDirectory($tempRoot);
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}

