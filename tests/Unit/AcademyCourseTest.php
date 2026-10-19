<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Plugins\academy\Controllers\CourseAdminController;
use DomainSystem\Plugins\academy\Repositories\PdoCourseRepository;
use DomainSystem\Plugins\academy\Services\CourseCatalogRenderer;
use DomainSystem\Plugins\academy\Services\CourseService;
use DomainSystem\Plugins\academy\Services\LocalCourseImageStorage;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;

class AcademyCourseTest extends TestCase
{
    private PDO $pdo;
    private string $tempUploadDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->tempUploadDir = sys_get_temp_dir() . '/academy_test_' . bin2hex(random_bytes(4));
        if (!is_dir($this->tempUploadDir)) {
            mkdir($this->tempUploadDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        $this->cleanupDirectory($this->tempUploadDir);
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

    /**
     * Gera um arquivo PNG binário válido com largura/altura específicas, sem depender da extensão GD.
     */
    private function createDummyImage(int $width, int $height): string
    {
        $filePath = $this->tempUploadDir . '/dummy_' . bin2hex(random_bytes(4)) . '.png';
        $sig = "\x89PNG\r\n\x1a\n";
        $ihdrData = pack('NN', $width, $height) . "\x08\x02\x00\x00\x00";
        $ihdrChunk = pack('N', 13) . 'IHDR' . $ihdrData . pack('N', crc32('IHDR' . $ihdrData));
        $iendChunk = pack('N', 0) . 'IEND' . pack('N', crc32('IEND'));

        file_put_contents($filePath, $sig . $ihdrChunk . $iendChunk);
        return $filePath;
    }

    public function testRepositoryCrudOperations(): void
    {
        $repo = new PdoCourseRepository($this->pdo);

        $this->assertEmpty($repo->findAll());

        $id = $repo->create([
            'title'       => 'Curso de Arquitetura Limpa',
            'slug'        => 'curso-de-arquitetura-limpa',
            'thumbnail'   => 'uploads/academy/clean.png',
            'description' => 'Domine SOLID e Micro-Kernels em PHP.',
            'status'      => 'published',
            'link'        => 'https://exemplo.com/curso',
        ]);

        $this->assertGreaterThan(0, $id);

        $found = $repo->findById($id);
        $this->assertNotNull($found);
        $this->assertSame('Curso de Arquitetura Limpa', $found['title']);
        $this->assertSame('curso-de-arquitetura-limpa', $found['slug']);
        $this->assertSame('published', $found['status']);

        $bySlug = $repo->findBySlug('curso-de-arquitetura-limpa');
        $this->assertNotNull($bySlug);
        $this->assertSame($id, (int)$bySlug['id']);

        $published = $repo->findPublished();
        $this->assertCount(1, $published);

        // Update
        $updated = $repo->update($id, [
            'title'       => 'Curso Avançado de Arquitetura Limpa',
            'slug'        => 'curso-avancado-de-arquitetura-limpa',
            'thumbnail'   => 'uploads/academy/clean-v2.png',
            'description' => 'Versão 2.0 revisada.',
            'status'      => 'soon',
            'link'        => 'https://exemplo.com/curso-v2',
        ]);
        $this->assertTrue($updated);

        $foundUpdated = $repo->findById($id);
        $this->assertSame('Curso Avançado de Arquitetura Limpa', $foundUpdated['title']);
        $this->assertSame('soon', $foundUpdated['status']);

        // Delete
        $deleted = $repo->delete($id);
        $this->assertTrue($deleted);
        $this->assertNull($repo->findById($id));
    }

    public function testCourseServiceValidation(): void
    {
        $repo = new PdoCourseRepository($this->pdo);
        $storage = new LocalCourseImageStorage($this->tempUploadDir);
        $service = new CourseService($repo, $storage);

        // Title cannot be empty
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('título do curso é obrigatório');
        $service->createCourse(['title' => '   ', 'status' => 'published']);
    }

    public function testCourseServiceRejectsInvalidStatus(): void
    {
        $repo = new PdoCourseRepository($this->pdo);
        $storage = new LocalCourseImageStorage($this->tempUploadDir);
        $service = new CourseService($repo, $storage);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Status inválido');
        $service->createCourse(['title' => 'Curso Teste', 'status' => 'invalid_status']);
    }

    public function testAccentSafeSlugGenerationAndCollisionHandling(): void
    {
        $repo = new PdoCourseRepository($this->pdo);
        $storage = new LocalCourseImageStorage($this->tempUploadDir);
        $service = new CourseService($repo, $storage);

        // 1. Slugs com acentos devem ser transliterados
        $id1 = $service->createCourse([
            'title' => 'Introdução à Programação & Inteligência Artificial',
            'status' => 'published'
        ]);
        $course1 = $repo->findById($id1);
        $this->assertSame('introducao-a-programacao-inteligencia-artificial', $course1['slug']);

        // 2. Títulos repetidos devem gerar sufixo numérico sem erro de colisão no BD
        $id2 = $service->createCourse([
            'title' => 'Introdução à Programação & Inteligência Artificial',
            'status' => 'published'
        ]);
        $course2 = $repo->findById($id2);
        $this->assertSame('introducao-a-programacao-inteligencia-artificial-1', $course2['slug']);

        $id3 = $service->createCourse([
            'title' => 'Introdução à Programação & Inteligência Artificial',
            'status' => 'published'
        ]);
        $course3 = $repo->findById($id3);
        $this->assertSame('introducao-a-programacao-inteligencia-artificial-2', $course3['slug']);
    }

    public function testImageStorageValidates16By9AspectRatio(): void
    {
        $storage = new LocalCourseImageStorage($this->tempUploadDir);

        // 1. Imagem quadrada (1:1 -> 640x640) deve ser rejeitada
        $squareImg = $this->createDummyImage(640, 640);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('proporção 16:9');
        $storage->storeUpload([
            'tmp_name' => $squareImg,
            'size'     => filesize($squareImg),
        ]);
    }

    public function testImageStorageRejectsImagesSmallerThanMinimum(): void
    {
        $storage = new LocalCourseImageStorage($this->tempUploadDir);

        // Imagem 16:9 mas menor que 640x360 (ex: 320x180) deve ser rejeitada
        $smallImg = $this->createDummyImage(320, 180);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Dimensão mínima exigida: 640x360 pixels');
        $storage->storeUpload([
            'tmp_name' => $smallImg,
            'size'     => filesize($smallImg),
        ]);
    }

    public function testImageStorageAcceptsCompliant16By9Image(): void
    {
        $storage = new LocalCourseImageStorage($this->tempUploadDir);

        // Imagem 640x360 (16:9 perfeito)
        $validImg = $this->createDummyImage(640, 360);
        $savedPath = $storage->storeUpload([
            'tmp_name' => $validImg,
            'size'     => filesize($validImg),
        ]);

        $this->assertStringStartsWith('uploads/academy/course_', $savedPath);
        $this->assertStringEndsWith('.png', $savedPath);

        // Verifica que o arquivo físico existe no diretório
        $fullPath = $storage->getUploadDir() . DIRECTORY_SEPARATOR . basename($savedPath);
        $this->assertFileExists($fullPath);

        // Remove
        $storage->deleteFile($savedPath);
        $this->assertFileDoesNotExist($fullPath);
    }

    public function testCatalogRendererGeneratesResponsiveCards(): void
    {
        $repo = new PdoCourseRepository($this->pdo);
        $renderer = new CourseCatalogRenderer($repo);

        // Quando vazio
        $emptyHtml = $renderer->render();
        $this->assertStringContainsString('Nenhum curso disponível no momento', $emptyHtml);

        // Insere cursos
        $repo->create([
            'title'       => 'Curso de APIs RESTful',
            'slug'        => 'curso-de-apis-restful',
            'description' => 'Aprenda a construir APIs com padrões PSR.',
            'thumbnail'   => 'uploads/academy/course_sample.png',
            'status'      => 'published',
            'link'        => '/tutoriais/apis',
        ]);
        $repo->create([
            'title'       => 'Curso de Docker e DevOps',
            'slug'        => 'curso-de-docker-e-devops',
            'description' => 'Deploy containerizado sem dor de cabeça.',
            'thumbnail'   => null,
            'status'      => 'soon',
            'link'        => null,
        ]);

        $html = $renderer->render();
        $this->assertStringContainsString('academy-grid', $html);
        $this->assertStringContainsString('Curso de APIs RESTful', $html);
        $this->assertStringContainsString('Aprenda a construir APIs com padrões PSR.', $html);
        $this->assertStringContainsString('EM BREVE', $html);
        $this->assertStringContainsString('Curso de Docker e DevOps', $html);
        $this->assertStringContainsString('href="/tutoriais/apis"', $html);
        $this->assertStringContainsString('href="/cursos/curso-de-docker-e-devops"', $html);
        $this->assertStringContainsString('aspect-ratio:16/9', $html);
    }

    public function testCourseAdminControllerWorkflow(): void
    {
        $repo = new PdoCourseRepository($this->pdo);
        $storage = new LocalCourseImageStorage($this->tempUploadDir);
        $service = new CourseService($repo, $storage);

        $sessionMock = $this->createMock(SessionManagerInterface::class);
        $sessionMock->method('getCsrfToken')->willReturn('valid_csrf_token');
        $sessionMock->method('getFlash')->willReturn(null);

        $controller = new CourseAdminController($repo, $service, $sessionMock);

        // 1. Index
        $req = new Request();
        $indexResponse = $controller->index($req);
        $this->assertSame(200, $indexResponse->getStatusCode());
        $this->assertStringContainsString('Academy (Cursos)', $indexResponse->getContent());
        $this->assertStringContainsString('[hub_de_cursos]', $indexResponse->getContent());

        // 2. Store via POST
        $storeReq = new Request([], [
            'csrf_token'  => 'valid_csrf_token',
            'title'       => 'Dominando TDD no PHP',
            'description' => 'Testes automatizados com PHPUnit.',
            'status'      => 'published',
            'link'        => 'https://rachidd.com/cursos/tdd',
        ]);
        $storeResponse = $controller->store($storeReq);
        $this->assertSame(302, $storeResponse->getStatusCode());

        $courses = $repo->findAll();
        $this->assertCount(1, $courses);
        $courseId = (int)$courses[0]['id'];
        $this->assertSame('Dominando TDD no PHP', $courses[0]['title']);

        // 3. Edit view
        $editResponse = $controller->edit($req, $courseId);
        $this->assertSame(200, $editResponse->getStatusCode());
        $this->assertStringContainsString('Editar Curso #' . $courseId, $editResponse->getContent());

        // 4. Update
        $updateReq = new Request([], [
            'csrf_token'  => 'valid_csrf_token',
            'title'       => 'Dominando TDD no PHP 8.2',
            'description' => 'Testes automatizados atualizados.',
            'status'      => 'soon',
            'link'        => 'https://rachidd.com/cursos/tdd-v2',
        ]);
        $updateResponse = $controller->update($updateReq, $courseId);
        $this->assertSame(302, $updateResponse->getStatusCode());

        $updated = $repo->findById($courseId);
        $this->assertSame('Dominando TDD no PHP 8.2', $updated['title']);
        $this->assertSame('soon', $updated['status']);

        // 5. Delete
        $deleteReq = new Request([], ['csrf_token' => 'valid_csrf_token']);
        $deleteResponse = $controller->delete($deleteReq, $courseId);
        $this->assertSame(302, $deleteResponse->getStatusCode());
        $this->assertNull($repo->findById($courseId));
    }

    public function testCourseAdminControllerCanManageCourseStructure(): void
    {
        $repo = new PdoCourseRepository($this->pdo);
        $storage = new LocalCourseImageStorage($this->tempUploadDir);
        $service = new CourseService($repo, $storage);
        $structureRepo = new \DomainSystem\Plugins\academy\Repositories\PdoCourseStructureRepository($this->pdo);
        $themeMock = $this->createMock(\DomainSystem\Core\Contracts\ThemeManagerInterface::class);
        $themeMock->method('getAvailablePages')->willReturn(['pages/tutoriais/01-instalacao.php']);

        $sessionMock = $this->createMock(SessionManagerInterface::class);
        $sessionMock->method('getCsrfToken')->willReturn('valid_csrf_token');

        $controller = new CourseAdminController($repo, $service, $sessionMock, $structureRepo, $themeMock);

        $courseId = $repo->create([
            'title'       => 'Curso Arquitetura de Microsserviços',
            'slug'        => 'curso-microsservicos',
            'status'      => 'published',
        ]);

        // 1. Edit exibe a seção de estrutura e os arquivos de tema disponíveis
        $editResponse = $controller->edit(new Request(), $courseId);
        $this->assertSame(200, $editResponse->getStatusCode());
        $this->assertStringContainsString('Estrutura do Curso (Módulos &amp; Aulas)', $editResponse->getContent());
        $this->assertStringContainsString('pages/tutoriais/01-instalacao.php', $editResponse->getContent());

        // 2. Salva estrutura via updateStructure
        $structurePayload = [
            [
                'title'   => 'Módulo 1: Setup do Ambiente',
                'lessons' => [
                    [
                        'title'       => '01 - Instalação do Ambiente',
                        'slug'        => '01-instalacao',
                        'source_type' => 'theme_file',
                        'source'      => 'pages/tutoriais/01-instalacao.php',
                    ],
                ],
            ],
        ];

        $updateReq = new Request([], [
            'csrf_token'     => 'valid_csrf_token',
            'structure_json' => json_encode($structurePayload),
        ]);

        $response = $controller->updateStructure($updateReq, $courseId);
        $this->assertSame(302, $response->getStatusCode());

        $modules = $structureRepo->getModules($courseId);
        $this->assertCount(1, $modules);
        $this->assertSame('Módulo 1: Setup do Ambiente', $modules[0]['title']);

        $lessons = $structureRepo->getLessons($courseId);
        $this->assertCount(1, $lessons);
        $this->assertSame('01 - Instalação do Ambiente', $lessons[0]['title']);
        $this->assertSame('01-instalacao', $lessons[0]['slug']);
        $this->assertSame('pages/tutoriais/01-instalacao.php', $lessons[0]['source']);
    }

    public function testStorageWithPublicStorageInterface(): void
    {
        $publicStorage = new \DomainSystem\Core\Storage\LocalPublicStorage($this->tempUploadDir, '/base-site');
        $courseStorage = new LocalCourseImageStorage($publicStorage);

        $imgPath = $this->createDummyImage(1280, 720);
        $fileInfo = [
            'name'     => 'test.png',
            'type'     => 'image/png',
            'tmp_name' => $imgPath,
            'error'    => UPLOAD_ERR_OK,
            'size'     => filesize($imgPath),
        ];

        $relativePath = $courseStorage->storeUpload($fileInfo);
        $this->assertStringStartsWith('uploads/academy/course_', $relativePath);

        $uploadDir = $courseStorage->getUploadDir();
        $this->assertDirectoryExists($uploadDir);

        $courseStorage->deleteFile($relativePath);
        $this->assertFalse($publicStorage->exists($relativePath));
    }

    public function testDraftAndSoonLessonsOrCoursesAreBlockedPublicly(): void
    {
        $courseRepo = new PdoCourseRepository($this->pdo);
        $structureRepo = new \DomainSystem\Plugins\academy\Repositories\PdoCourseStructureRepository($this->pdo);
        $navService = new \DomainSystem\Plugins\academy\Services\CourseNavigationService($courseRepo, $structureRepo, '');
        $catalogRenderer = new CourseCatalogRenderer($courseRepo);
        $frontController = new \DomainSystem\Plugins\academy\Controllers\CourseFrontController(
            $courseRepo,
            $structureRepo,
            $navService,
            $catalogRenderer
        );

        $courseId = $courseRepo->create([
            'title'       => 'Curso de Segurança Web',
            'slug'        => 'curso-seguranca-web',
            'description' => 'Segurança em aplicações PHP.',
            'status'      => 'published',
        ]);

        $modId = $structureRepo->createModule($courseId, 'Módulo 1', 1);
        $structureRepo->createLesson([
            'course_id'   => $courseId,
            'module_id'   => $modId,
            'title'       => 'Aula 01 Publicada',
            'slug'        => 'aula-01-publicada',
            'source_type' => 'content',
            'content'     => '<p>Conteúdo publicado</p>',
            'position'    => 1,
            'status'      => 'published',
        ]);
        $structureRepo->createLesson([
            'course_id'   => $courseId,
            'module_id'   => $modId,
            'title'       => 'Aula 02 Secreta em Rascunho',
            'slug'        => 'aula-02-rascunho',
            'source_type' => 'content',
            'content'     => '<p>Conteúdo confidencial não publicado</p>',
            'position'    => 2,
            'status'      => 'draft',
        ]);

        // 1. Aula publicada em curso publicado retorna 200
        $resPublished = $frontController->showLesson('curso-seguranca-web', 'aula-01-publicada');
        $this->assertSame(200, $resPublished->getStatusCode());
        $this->assertStringContainsString('Conteúdo publicado', $resPublished->getContent());

        // 2. Aula em rascunho NUNCA deve abrir no front público (deve retornar 404)
        $resDraft = $frontController->showLesson('curso-seguranca-web', 'aula-02-rascunho');
        $this->assertSame(404, $resDraft->getStatusCode());
        $this->assertStringNotContainsString('Conteúdo confidencial não publicado', $resDraft->getContent());

        // 3. Navegação lateral e adjacência não devem listar a aula em rascunho
        $navHtml = $navService->renderCourseNav('curso-seguranca-web', 'aula-01-publicada');
        $this->assertStringContainsString('Aula 01 Publicada', $navHtml);
        $this->assertStringNotContainsString('Aula 02 Secreta em Rascunho', $navHtml);

        $adjacent = $navService->getAdjacentLessons('curso-seguranca-web', 'aula-01-publicada');
        $this->assertNull($adjacent['next']);

        // 4. Se o curso estiver 'soon' (Em breve), as aulas não devem abrir no front público
        $courseRepo->update($courseId, [
            'title'       => 'Curso de Segurança Web',
            'slug'        => 'curso-seguranca-web',
            'description' => 'Segurança em aplicações PHP.',
            'thumbnail'   => null,
            'status'      => 'soon',
            'link'        => null,
        ]);

        $resSoonLesson = $frontController->showLesson('curso-seguranca-web', 'aula-01-publicada');
        $this->assertSame(404, $resSoonLesson->getStatusCode());

        $resSoonCourse = $frontController->showCourse('curso-seguranca-web');
        $this->assertSame(200, $resSoonCourse->getStatusCode());
        $this->assertStringNotContainsString('Começar Agora', $resSoonCourse->getContent());
    }

    public function testUpdateCoursePreservesOldThumbnailAndCleansNewWhenDbFails(): void
    {
        $storage = new LocalCourseImageStorage($this->tempUploadDir);

        $oldImg = $this->createDummyImage(640, 360);
        $oldThumbnailRel = $storage->storeUpload([
            'tmp_name' => $oldImg,
            'size'     => filesize($oldImg),
        ]);
        $oldFullPath = $storage->getUploadDir() . DIRECTORY_SEPARATOR . basename($oldThumbnailRel);
        $this->assertFileExists($oldFullPath);

        $repoMock = $this->createMock(\DomainSystem\Plugins\academy\Contracts\CourseRepositoryInterface::class);
        $repoMock->method('findById')->willReturn([
            'id'          => 10,
            'title'       => 'Curso Original',
            'slug'        => 'curso-original',
            'description' => 'Desc',
            'thumbnail'   => $oldThumbnailRel,
            'status'      => 'published',
            'link'        => '',
        ]);
        $repoMock->method('update')->willReturn(false);

        $service = new CourseService($repoMock, $storage);

        $newImg = $this->createDummyImage(1280, 720);
        $updated = $service->updateCourse(10, [
            'title'  => 'Curso Original',
            'status' => 'published',
        ], [
            'tmp_name' => $newImg,
            'size'     => filesize($newImg),
        ]);

        $this->assertFalse($updated);
        // A imagem antiga NÃO pode ter sido apagada quando o banco falhou
        $this->assertFileExists($oldFullPath);
        // E não deve sobrar a imagem nova órfã no diretório de uploads
        $filesInUploadDir = array_values(array_diff(scandir($storage->getUploadDir()), ['.', '..']));
        $this->assertSame([basename($oldThumbnailRel)], $filesInUploadDir);
    }

    public function testCreateCourseCleansUpThumbnailWhenDbThrowsException(): void
    {
        $storage = new LocalCourseImageStorage($this->tempUploadDir);
        $repoMock = $this->createMock(\DomainSystem\Plugins\academy\Contracts\CourseRepositoryInterface::class);
        $repoMock->method('findBySlug')->willReturn(null);
        $repoMock->method('create')->willThrowException(new \RuntimeException('DB insert failed'));

        $service = new CourseService($repoMock, $storage);
        $newImg = $this->createDummyImage(640, 360);

        try {
            $service->createCourse([
                'title'  => 'Curso Falho',
                'status' => 'draft',
            ], [
                'tmp_name' => $newImg,
                'size'     => filesize($newImg),
            ]);
            $this->fail('Deveria ter lançado RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertSame('DB insert failed', $e->getMessage());
        }

        $filesInUploadDir = array_values(array_diff(scandir($storage->getUploadDir()), ['.', '..']));
        $this->assertEmpty($filesInUploadDir, 'Arquivo de upload órfão deve ser removido se create() falhar');
    }

    public function testDeleteCourseDoesNotDeleteThumbnailIfDbDeleteFails(): void
    {
        $storage = new LocalCourseImageStorage($this->tempUploadDir);
        $img = $this->createDummyImage(640, 360);
        $thumbRel = $storage->storeUpload([
            'tmp_name' => $img,
            'size'     => filesize($img),
        ]);
        $fullPath = $storage->getUploadDir() . DIRECTORY_SEPARATOR . basename($thumbRel);

        $repoMock = $this->createMock(\DomainSystem\Plugins\academy\Contracts\CourseRepositoryInterface::class);
        $repoMock->method('findById')->willReturn([
            'id'        => 5,
            'title'     => 'Curso X',
            'thumbnail' => $thumbRel,
        ]);
        $repoMock->method('delete')->willReturn(false);

        $service = new CourseService($repoMock, $storage);
        $this->assertFalse($service->deleteCourse(5));
        $this->assertFileExists($fullPath, 'A capa não deve ser apagada se o DELETE no banco falhar');
    }
}
