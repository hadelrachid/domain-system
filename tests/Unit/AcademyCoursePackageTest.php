<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Plugins\academy\Contracts\ThemePageResolverInterface;
use DomainSystem\Plugins\academy\Repositories\PdoCourseRepository;
use DomainSystem\Plugins\academy\Repositories\PdoCourseStructureRepository;
use DomainSystem\Plugins\academy\Services\CoursePackageService;
use DomainSystem\Plugins\academy\Services\CourseService;
use DomainSystem\Plugins\academy\Services\LocalCourseImageStorage;
use PDO;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class AcademyCoursePackageTest extends TestCase
{
    private PDO $pdo;
    private PdoCourseRepository $courseRepo;
    private PdoCourseStructureRepository $structureRepo;
    private CourseService $courseService;
    private string $tempThemeDir;
    private string $tempTargetDir;

    protected function setUp(): void
    {
        parent::setUp();

        if (!class_exists(ZipArchive::class)) {
            $this->markTestSkipped('Extensão ZipArchive não disponível no ambiente.');
        }

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->courseRepo = new PdoCourseRepository($this->pdo);
        $this->structureRepo = new PdoCourseStructureRepository($this->pdo);
        $this->structureRepo->ensureTables();

        $storage = new class implements \DomainSystem\Plugins\academy\Contracts\CourseImageStorageInterface {
            public function storeUpload(array $fileInfo): string { return ''; }
            public function deleteFile(string $relativePath): void {}
        };

        $this->courseService = new CourseService($this->courseRepo, $storage);

        $this->tempThemeDir = sys_get_temp_dir() . '/ds_test_theme_' . bin2hex(random_bytes(4));
        mkdir($this->tempThemeDir . '/pages/solid/modulo-1', 0755, true);

        $this->tempTargetDir = sys_get_temp_dir() . '/ds_test_target_theme_' . bin2hex(random_bytes(4));
        mkdir($this->tempTargetDir . '/pages', 0755, true);
    }

    protected function tearDown(): void
    {
        $this->cleanupDirectory($this->tempThemeDir);
        $this->cleanupDirectory($this->tempTargetDir);
        parent::tearDown();
    }

    private function cleanupDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                $this->cleanupDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    public function testExportCourseZipCreatesValidArchiveWithManifestAndPhpFiles(): void
    {
        // 1. Cria Curso SOLID
        $courseId = $this->courseRepo->create([
            'title'       => 'Curso Completo de SOLID',
            'slug'        => 'solid',
            'description' => 'Domine os 5 princípios de design orientado a objetos.',
            'price'       => 197.00,
            'access_type' => 'paid',
            'category'    => 'Desenvolvimento',
            'status'      => 'published',
        ]);

        // 2. Cria arquivo de aula no tema
        $lessonFile1 = $this->tempThemeDir . '/pages/solid/01-introducao.php';
        file_put_contents($lessonFile1, "<?php\n/**\n * Título: Introdução ao SOLID\n */\necho '<h1>Introdução ao SOLID</h1>';\n");

        $lessonFile2 = $this->tempThemeDir . '/pages/solid/modulo-1/02-srp.php';
        file_put_contents($lessonFile2, "<?php\n/**\n * Título: Princípio da Responsabilidade Única (SRP)\n */\necho '<h1>SRP</h1>';\n");

        // 3. Cadastra estrutura do curso
        $this->structureRepo->replaceCourseStructure($courseId, [
            [
                'title'       => 'Módulo 1 - Fundamentos',
                'folder_slug' => 'solid',
                'position'    => 1,
                'lessons'     => [
                    [
                        'title'          => 'Introdução ao SOLID',
                        'slug'           => '01-introducao',
                        'source_type'    => 'theme_file',
                        'source'         => 'pages/solid/01-introducao.php',
                        'mobile_profile' => 'drawer',
                        'status'         => 'published',
                    ],
                    [
                        'title'          => 'Princípio da Responsabilidade Única (SRP)',
                        'slug'           => '02-srp',
                        'source_type'    => 'theme_file',
                        'source'         => 'pages/solid/modulo-1/02-srp.php',
                        'mobile_profile' => 'drawer',
                        'status'         => 'published',
                    ],
                ],
            ],
        ]);

        // Mock do ThemePageResolver apontando para $this->tempThemeDir
        $themeResolver = $this->createMock(ThemePageResolverInterface::class);
        $themeResolver->method('resolveActiveThemePath')->willReturn($this->tempThemeDir);

        $packageService = new CoursePackageService(
            $this->courseRepo,
            $this->structureRepo,
            $themeResolver,
            $this->courseService
        );

        // Exporta ZIP
        $zipPath = $packageService->exportCourseZip($courseId);

        $this->assertFileExists($zipPath);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($zipPath));

        // Verifica manifesto
        $manifestJson = $zip->getFromName('course.json');
        $this->assertNotEmpty($manifestJson);

        $manifest = json_decode($manifestJson, true);
        $this->assertSame('Curso Completo de SOLID', $manifest['course']['title']);
        $this->assertSame('solid', $manifest['course']['slug']);
        $this->assertCount(1, $manifest['structure']);
        $this->assertCount(2, $manifest['structure'][0]['lessons']);

        // Verifica se os arquivos PHP foram incluídos no ZIP
        $this->assertNotFalse($zip->getFromName('pages/solid/01-introducao.php'));
        $this->assertNotFalse($zip->getFromName('pages/solid/modulo-1/02-srp.php'));

        $zip->close();
        @unlink($zipPath);
    }

    public function testImportCourseZipRestoresCourseAndExtractsPhpFiles(): void
    {
        // 1. Prepara um ZIP válido de curso
        $zipPath = sys_get_temp_dir() . '/test_import_course_' . uniqid() . '.zip';
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $manifest = [
            'schema_version' => '2.6.0',
            'generator'      => 'DomainSystem Academy LMS',
            'course'         => [
                'title'       => 'Curso Avançado de Python',
                'slug'        => 'python-avancado',
                'description' => 'Aprenda decoradores, geradores e metaprogramação.',
                'price'       => 0.00,
                'access_type' => 'free',
                'category'    => 'Programação',
                'status'      => 'published',
            ],
            'structure'      => [
                [
                    'title'       => 'Módulo 1 - Metaprogramação',
                    'folder_slug' => 'python-avancado/m1',
                    'position'    => 1,
                    'lessons'     => [
                        [
                            'title'          => 'Decoradores na Prática',
                            'slug'           => 'decoradores',
                            'source_type'    => 'theme_file',
                            'source'         => 'pages/python-avancado/m1/decoradores.php',
                            'mobile_profile' => 'bottom_sheet',
                            'status'         => 'published',
                        ],
                    ],
                ],
            ],
        ];

        $zip->addFromString('course.json', json_encode($manifest));
        $zip->addFromString('pages/python-avancado/m1/decoradores.php', "<?php echo 'Decoradores Python'; ?>");
        $zip->close();

        // 2. Importa o ZIP no tema de destino
        $themeResolver = $this->createMock(ThemePageResolverInterface::class);
        $themeResolver->method('resolveActiveThemePath')->willReturn($this->tempTargetDir);

        $packageService = new CoursePackageService(
            $this->courseRepo,
            $this->structureRepo,
            $themeResolver,
            $this->courseService
        );

        $result = $packageService->importCourseZip($zipPath, $this->tempTargetDir);

        // 3. Valida resultado retornado
        $this->assertArrayHasKey('course_id', $result);
        $this->assertSame('Curso Avançado de Python', $result['title']);
        $this->assertSame(1, $result['modules_count']);
        $this->assertSame(1, $result['lessons_count']);

        // 4. Valida se o arquivo PHP foi extraído fisicamente
        $extractedFile = $this->tempTargetDir . '/pages/python-avancado/m1/decoradores.php';
        $this->assertFileExists($extractedFile);
        $this->assertSame("<?php echo 'Decoradores Python'; ?>", file_get_contents($extractedFile));

        // 5. Valida se o curso e as aulas foram gravados no banco
        $importedCourse = $this->courseRepo->findById((int)$result['course_id']);
        $this->assertNotNull($importedCourse);
        $this->assertSame('Curso Avançado de Python', $importedCourse['title']);

        $modules = $this->structureRepo->getModules((int)$result['course_id']);
        $this->assertCount(1, $modules);
        $this->assertSame('Módulo 1 - Metaprogramação', $modules[0]['title']);

        $lessons = $this->structureRepo->getLessons((int)$result['course_id']);
        $this->assertCount(1, $lessons);
        $this->assertSame('Decoradores na Prática', $lessons[0]['title']);
        $this->assertSame('pages/python-avancado/m1/decoradores.php', $lessons[0]['source']);

        @unlink($zipPath);
    }

    public function testImportCourseZipBlocksPathTraversalZipSlip(): void
    {
        $zipPath = sys_get_temp_dir() . '/test_slip_course_' . uniqid() . '.zip';
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $manifest = [
            'course'    => ['title' => 'Malicious Course', 'slug' => 'malicious'],
            'structure' => [],
        ];

        $zip->addFromString('course.json', json_encode($manifest));
        $zip->addFromString('../../../evil.php', "<?php // hack");
        $zip->addFromString('pages/../../evil2.php', "<?php // hack");
        $zip->close();

        $themeResolver = $this->createMock(ThemePageResolverInterface::class);
        $themeResolver->method('resolveActiveThemePath')->willReturn($this->tempTargetDir);

        $packageService = new CoursePackageService(
            $this->courseRepo,
            $this->structureRepo,
            $themeResolver,
            $this->courseService
        );

        $result = $packageService->importCourseZip($zipPath, $this->tempTargetDir);

        $this->assertNotEmpty($result['course_id']);
        $this->assertFileDoesNotExist($this->tempTargetDir . '/evil.php');
        $this->assertFileDoesNotExist($this->tempTargetDir . '/evil2.php');
        $this->assertFileDoesNotExist(dirname($this->tempTargetDir) . '/evil.php');

        @unlink($zipPath);
    }
}
