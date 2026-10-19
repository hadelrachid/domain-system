<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Controllers;

use DomainSystem\Core\Contracts\PublicStorageInterface;
use DomainSystem\Core\Contracts\ResponseInterface;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Contracts\ThemeManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Http\Responses\ViewResponse;
use DomainSystem\Plugins\academy\Contracts\CourseRepositoryInterface;
use DomainSystem\Plugins\academy\Contracts\CourseStructureRepositoryInterface;
use DomainSystem\Plugins\academy\Contracts\LessonFileScaffolderInterface;
use DomainSystem\Plugins\academy\Contracts\ThemePageResolverInterface;
use DomainSystem\Plugins\academy\Services\CourseService;
use InvalidArgumentException;
use Throwable;

class CourseAdminController
{
    private CourseRepositoryInterface $repository;
    private CourseService $service;
    private SessionManagerInterface $session;
    private ?CourseStructureRepositoryInterface $structureRepo;
    private ThemePageResolverInterface|ThemeManagerInterface|null $pageResolver;
    private ?PublicStorageInterface $storage;
    private ?LessonFileScaffolderInterface $scaffolder;

    public function __construct(
        CourseRepositoryInterface $repository,
        CourseService $service,
        SessionManagerInterface $session,
        ?CourseStructureRepositoryInterface $structureRepo = null,
        ThemePageResolverInterface|ThemeManagerInterface|null $pageResolver = null,
        ?PublicStorageInterface $storage = null,
        ?LessonFileScaffolderInterface $scaffolder = null
    ) {
        $this->repository = $repository;
        $this->service = $service;
        $this->session = $session;
        $this->structureRepo = $structureRepo;
        $this->pageResolver = $pageResolver;
        $this->storage = $storage;
        $this->scaffolder = $scaffolder;
    }

    public function index(Request $request): ResponseInterface
    {
        $courses = $this->repository->findAll();
        $csrfToken = (string)$this->session->getCsrfToken();
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

        $flash = $this->session->getFlash();
        $flashType = $flash['type'] ?? null;
        $flashMsg = $flash['msg'] ?? null;

        $html = $this->renderView('admin/courses/index', [
            'courses'   => $courses,
            'csrfToken' => $csrfToken,
            'baseUrl'   => $baseUrl,
            'flashType' => $flashType,
            'flashMsg'  => $flashMsg,
            'storage'   => $this->storage,
        ]);

        return new ViewResponse($html);
    }

    public function edit(Request $request, $id): ResponseInterface
    {
        $course = $this->repository->findById((int)$id);
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

        if (!$course) {
            $this->session->setFlash('error', "Curso não encontrado.");
            return Response::redirect($baseUrl . '/admin/academy');
        }

        $csrfToken = (string)$this->session->getCsrfToken();
        $flash = $this->session->getFlash();
        $flashType = $flash['type'] ?? null;
        $flashMsg = $flash['msg'] ?? null;

        $initialStructure = [];
        $availablePages = [];

        if ($this->structureRepo !== null) {
            $modules = $this->structureRepo->getModules((int)$course['id']);
            $allLessons = $this->structureRepo->getLessons((int)$course['id']);
            $availablePages = $this->resolvePublicThemePages();

            $lessonsByMod = [];
            $standaloneLessons = [];
            foreach ($allLessons as $l) {
                $mId = $l['module_id'] !== null ? (int)$l['module_id'] : null;
                if ($mId !== null && $mId > 0) {
                    $lessonsByMod[$mId][] = $l;
                } else {
                    $standaloneLessons[] = $l;
                }
            }

            foreach ($modules as $m) {
                $mId = (int)$m['id'];
                $initialStructure[] = [
                    'id'          => $mId,
                    'title'       => (string)$m['title'],
                    'folder_slug' => (string)($m['folder_slug'] ?? ''),
                    'position'    => (int)$m['position'],
                    'lessons'     => array_map(static function (array $les): array {
                        return [
                            'id'             => (int)$les['id'],
                            'title'          => (string)$les['title'],
                            'slug'           => (string)$les['slug'],
                            'source_type'    => (string)$les['source_type'],
                            'source'         => (string)($les['source'] ?? ''),
                            'content'        => (string)($les['content'] ?? ''),
                            'mobile_profile' => (string)($les['mobile_profile'] ?? 'drawer'),
                            'status'         => (string)($les['status'] ?? 'published'),
                        ];
                    }, $lessonsByMod[$mId] ?? []),
                ];
            }

            if (!empty($standaloneLessons)) {
                $initialStructure[] = [
                    'title'       => 'Módulo Geral',
                    'folder_slug' => '',
                    'position'    => 999,
                    'lessons'     => array_map(static function (array $les): array {
                        return [
                            'id'             => (int)$les['id'],
                            'title'          => (string)$les['title'],
                            'slug'           => (string)$les['slug'],
                            'source_type'    => (string)$les['source_type'],
                            'source'         => (string)($les['source'] ?? ''),
                            'content'        => (string)($les['content'] ?? ''),
                            'mobile_profile' => (string)($les['mobile_profile'] ?? 'drawer'),
                            'status'         => (string)($les['status'] ?? 'published'),
                        ];
                    }, $standaloneLessons),
                ];
            }
        }

        $html = $this->renderView('admin/courses/edit', [
            'course'           => $course,
            'csrfToken'        => $csrfToken,
            'baseUrl'          => $baseUrl,
            'flashType'        => $flashType,
            'flashMsg'         => $flashMsg,
            'storage'          => $this->storage,
            'hasStructureRepo' => $this->structureRepo !== null,
            'initialStructure' => $initialStructure,
            'availablePages'   => $availablePages,
        ]);

        return new ViewResponse($html);
    }

    /**
     * Cria uma pasta física dentro de pages/ do tema ativo e adiciona o módulo correspondente ao curso.
     */
    public function createThemeFolder(Request $request, $id): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $courseId = (int)$id;

        if (!$this->verifyCsrfToken($request)) {
            $this->session->setFlash('error', "Token de segurança CSRF inválido.");
            return Response::redirect($baseUrl . '/admin/academy/edit/' . $courseId);
        }

        $course = $this->repository->findById($courseId);
        if (!$course) {
            $this->session->setFlash('error', "Curso não encontrado.");
            return Response::redirect($baseUrl . '/admin/academy');
        }

        $folderName = trim((string)($request->input('folder_name') ?? ''));
        if ($folderName === '') {
            $this->session->setFlash('error', "O nome da pasta/módulo é obrigatório.");
            return Response::redirect($baseUrl . '/admin/academy/edit/' . $courseId);
        }

        try {
            $folderSlug = null;
            $relDir = '';
            if ($this->scaffolder !== null) {
                $folderInfo = $this->scaffolder->createFolder($folderName);
                $relDir = (string)($folderInfo['relative_path'] ?? '');
                $folderSlug = (string)($folderInfo['slug'] ?? $this->scaffolder->slugify($folderName));
            }

            if ($this->structureRepo !== null) {
                $existingModules = $this->structureRepo->getModules($courseId);
                $nextPos = count($existingModules) + 1;
                if (method_exists($this->structureRepo, 'createModule')) {
                    $this->structureRepo->createModule($courseId, $folderName, $nextPos, $folderSlug);
                }
            }

            $msg = $relDir !== ''
                ? "Pasta '{$relDir}' e módulo '{$folderName}' criados com sucesso!"
                : "Módulo '{$folderName}' criado com sucesso!";
            $this->session->setFlash('success', $msg);
        } catch (Throwable $e) {
            $this->session->setFlash('error', "Erro ao criar pasta/módulo: " . $e->getMessage());
        }

        return Response::redirect($baseUrl . '/admin/academy/edit/' . $courseId);
    }

    public function updateStructure(Request $request, $id): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $courseId = (int)$id;

        if (!$this->verifyCsrfToken($request)) {
            $this->session->setFlash('error', "Token de segurança CSRF inválido.");
            return Response::redirect($baseUrl . '/admin/academy/edit/' . $courseId);
        }

        $course = $this->repository->findById($courseId);
        if (!$course) {
            $this->session->setFlash('error', "Curso não encontrado.");
            return Response::redirect($baseUrl . '/admin/academy');
        }

        if ($this->structureRepo === null) {
            $this->session->setFlash('error', "Repositório de estrutura não disponível.");
            return Response::redirect($baseUrl . '/admin/academy/edit/' . $courseId);
        }

        $rawJson = $request->input('structure_json');
        if (!empty($rawJson)) {
            $structure = json_decode((string)$rawJson, true);
            if (!is_array($structure)) {
                $this->session->setFlash('error', "Formato da estrutura inválido.");
                return Response::redirect($baseUrl . '/admin/academy/edit/' . $courseId);
            }
        } else {
            $structure = $request->input('structure', []);
            if (!is_array($structure)) {
                $structure = [];
            }
        }

        $autoFabricate = (string)($request->input('auto_fabricate_php') ?? '') === '1';

        try {
            $fabricatedFilesCount = 0;

            if ($this->scaffolder !== null) {
                foreach ($structure as &$modData) {
                    $folderSlug = isset($modData['folder_slug']) && trim((string)$modData['folder_slug']) !== ''
                        ? trim((string)$modData['folder_slug'])
                        : null;

                    if ($folderSlug !== null && $autoFabricate) {
                        $this->scaffolder->createFolder($folderSlug);
                    }

                    if (!isset($modData['lessons']) || !is_array($modData['lessons'])) {
                        continue;
                    }

                    foreach ($modData['lessons'] as &$lesson) {
                        $title = trim((string)($lesson['title'] ?? ''));
                        if ($title === '') {
                            continue;
                        }

                        $srcType = (string)($lesson['source_type'] ?? 'theme_file');
                        $source = trim((string)($lesson['source'] ?? ''));
                        $shouldFabricate = !empty($lesson['auto_fabricate'])
                            || ($autoFabricate && ($source === '' || $srcType === 'content'));

                        if ($shouldFabricate) {
                            $rawContent = (string)($lesson['content'] ?? '');

                            $fabricated = $this->scaffolder->fabricateLessonFile(
                                $title,
                                $folderSlug,
                                $rawContent !== '' ? $rawContent : null,
                                (string)($lesson['mobile_profile'] ?? 'drawer')
                            );

                            if (empty($lesson['slug']) || preg_match('/^aula-\d+$/', (string)$lesson['slug'])) {
                                $lesson['slug'] = $fabricated['slug'];
                            }
                            if ($source === '') {
                                $lesson['source'] = $fabricated['relative_path'];
                            }
                            $fabricatedFilesCount++;
                        }
                    }
                    unset($lesson);
                }
                unset($modData);
            }

            $this->structureRepo->replaceCourseStructure($courseId, $structure);
            $extraMsg = $fabricatedFilesCount > 0 ? " ({$fabricatedFilesCount} arquivo(s) .php fabricado(s) em pages/)" : '';
            $this->session->setFlash('success', "Estrutura do curso atualizada com sucesso!{$extraMsg}");
        } catch (Throwable $e) {
            $this->session->setFlash('error', "Erro ao atualizar estrutura: " . $e->getMessage());
        }

        return Response::redirect($baseUrl . '/admin/academy/edit/' . $courseId);
    }

    public function store(Request $request): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        if (!$this->verifyCsrfToken($request)) {
            $this->session->setFlash('error', "Token de segurança CSRF inválido.");
            return Response::redirect($baseUrl . '/admin/academy');
        }

        try {
            $file = $this->extractUploadedThumbnail($request);
            $data = [
                'title'       => $request->input('title'),
                'description' => $request->input('description'),
                'status'      => $request->input('status'),
                'link'        => $request->input('link'),
            ];

            $this->service->createCourse($data, $file);
            $this->session->setFlash('success', "Curso cadastrado com sucesso!");
        } catch (Throwable $e) {
            $this->session->setFlash('error', "Erro ao cadastrar curso: " . $e->getMessage());
        }

        return Response::redirect($baseUrl . '/admin/academy');
    }

    public function update(Request $request, $id): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        if (!$this->verifyCsrfToken($request)) {
            $this->session->setFlash('error', "Token de segurança CSRF inválido.");
            return Response::redirect($baseUrl . '/admin/academy');
        }

        try {
            $file = $this->extractUploadedThumbnail($request);
            $data = [
                'title'       => $request->input('title'),
                'description' => $request->input('description'),
                'status'      => $request->input('status'),
                'link'        => $request->input('link'),
            ];

            $this->service->updateCourse((int)$id, $data, $file);
            $this->session->setFlash('success', "Curso atualizado com sucesso!");
        } catch (Throwable $e) {
            $this->session->setFlash('error', "Erro ao atualizar curso: " . $e->getMessage());
            return Response::redirect($baseUrl . '/admin/academy/edit/' . $id);
        }

        return Response::redirect($baseUrl . '/admin/academy');
    }

    public function delete(Request $request, $id): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        if (!$this->verifyCsrfToken($request)) {
            $this->session->setFlash('error', "Token de segurança CSRF inválido.");
            return Response::redirect($baseUrl . '/admin/academy');
        }

        try {
            $this->service->deleteCourse((int)$id);
            $this->session->setFlash('success', "Curso excluído com sucesso!");
        } catch (Throwable $e) {
            $this->session->setFlash('error', "Erro ao excluir curso: " . $e->getMessage());
        }

        return Response::redirect($baseUrl . '/admin/academy');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function extractUploadedThumbnail(Request $request): ?array
    {
        $file = $request->file('thumbnail');
        if ($file && (empty($file['tmp_name']) || ($file['error'] ?? UPLOAD_ERR_OK) === UPLOAD_ERR_NO_FILE)) {
            return null;
        }
        if ($file && ($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
                throw new InvalidArgumentException("O arquivo de imagem enviado excedeu o tamanho máximo permitido pelo servidor.");
            }
            throw new InvalidArgumentException("Erro no envio do arquivo de imagem (Código: " . ($file['error'] ?? 'desconhecido') . ").");
        }
        return $file;
    }

    private function verifyCsrfToken(Request $request): bool
    {
        $token = (string)($request->input('csrf_token') ?? '');
        $expected = (string)$this->session->getCsrfToken();

        if ($token === '' || $expected === '') {
            return false;
        }

        return hash_equals($expected, $token);
    }

    /**
     * @return array<int, string>
     */
    private function resolvePublicThemePages(): array
    {
        if ($this->pageResolver instanceof ThemePageResolverInterface) {
            return $this->pageResolver->resolvePublicThemePages();
        }

        if ($this->pageResolver instanceof ThemeManagerInterface) {
            $pages = $this->pageResolver->getAvailablePages();
            $pages = array_values(array_unique($pages));
            natsort($pages);
            return array_values($pages);
        }

        return [];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderView(string $viewName, array $data = []): string
    {
        $viewFile = dirname(__DIR__) . '/views/' . ltrim($viewName, '/\\') . '.php';
        if (!is_file($viewFile)) {
            return '';
        }

        ob_start();
        extract($data, EXTR_SKIP);
        include $viewFile;
        return (string)ob_get_clean();
    }
}
