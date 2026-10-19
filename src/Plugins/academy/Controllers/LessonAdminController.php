<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Controllers;

use DomainSystem\Core\Contracts\ResponseInterface;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Http\Responses\ViewResponse;
use DomainSystem\Plugins\academy\Contracts\CourseRepositoryInterface;
use DomainSystem\Plugins\academy\Contracts\CourseStructureRepositoryInterface;
use DomainSystem\Plugins\academy\Contracts\LessonFileScaffolderInterface;
use Throwable;

class LessonAdminController
{
    private CourseRepositoryInterface $courseRepo;
    private CourseStructureRepositoryInterface $structureRepo;
    private SessionManagerInterface $session;
    private ?LessonFileScaffolderInterface $scaffolder;

    public function __construct(
        CourseRepositoryInterface $courseRepo,
        CourseStructureRepositoryInterface $structureRepo,
        SessionManagerInterface $session,
        ?LessonFileScaffolderInterface $scaffolder = null
    ) {
        $this->courseRepo = $courseRepo;
        $this->structureRepo = $structureRepo;
        $this->session = $session;
        $this->scaffolder = $scaffolder;
    }

    public function editor(Request $request, $id): ResponseInterface
    {
        $lessonId = (int)$id;
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

        $lesson = $this->structureRepo->getLessonById($lessonId);
        if (!$lesson) {
            $this->session->setFlash('error', "Aula não encontrada.");
            return Response::redirect($baseUrl . '/admin/academy');
        }

        $course = $this->courseRepo->findById((int)$lesson['course_id']);
        if (!$course) {
            $this->session->setFlash('error', "Curso vinculado à aula não encontrado.");
            return Response::redirect($baseUrl . '/admin/academy');
        }

        $moduleTitle = 'Módulo Geral';
        $moduleFolderSlug = null;
        $allModules = $this->structureRepo->getModules((int)$course['id']);
        $allCourseLessons = $this->structureRepo->getLessons((int)$course['id']);
        if (!empty($lesson['module_id'])) {
            foreach ($allModules as $m) {
                if ((int)$m['id'] === (int)$lesson['module_id']) {
                    $moduleTitle = (string)$m['title'];
                    $moduleFolderSlug = !empty($m['folder_slug']) ? (string)$m['folder_slug'] : null;
                    break;
                }
            }
        }

        $prevLessonTitle = null;
        $nextLessonTitle = null;
        $flatLessons = array_values($allCourseLessons);
        foreach ($flatLessons as $idx => $fl) {
            if ((int)$fl['id'] === $lessonId) {
                if ($idx > 0) {
                    $prevLessonTitle = (string)$flatLessons[$idx - 1]['title'];
                }
                if ($idx < count($flatLessons) - 1) {
                    $nextLessonTitle = (string)$flatLessons[$idx + 1]['title'];
                }
                break;
            }
        }

        $rawContent = (string)($lesson['content'] ?? '');
        $initialBlocks = [];

        if ($rawContent !== '') {
            $decoded = json_decode($rawContent, true);
            if (is_array($decoded)) {
                $initialBlocks = $decoded['blocks'] ?? (isset($decoded[0]['type']) ? $decoded : []);
            } else {
                $initialBlocks = [
                    [
                        'id'   => 'b_init_1',
                        'type' => 'paragraph',
                        'data' => ['text' => $rawContent],
                    ],
                ];
            }
        } elseif (!empty($lesson['source']) && $this->scaffolder !== null && method_exists($this->scaffolder, 'extractBlocksFromFile')) {
            $extractedJson = $this->scaffolder->extractBlocksFromFile((string)$lesson['source']);
            if (is_string($extractedJson) && $extractedJson !== '') {
                $decoded = json_decode($extractedJson, true);
                if (is_array($decoded)) {
                    $initialBlocks = $decoded['blocks'] ?? (isset($decoded[0]['type']) ? $decoded : []);
                }
            }
        }

        if (empty($initialBlocks)) {
            $initialBlocks = [
                [
                    'id'   => 'b_init_h',
                    'type' => 'header',
                    'data' => ['level' => 2, 'text' => (string)$lesson['title']],
                ],
                [
                    'id'   => 'b_init_p',
                    'type' => 'paragraph',
                    'data' => ['text' => 'Digite aqui o conteúdo da aula ou adicione novos blocos abaixo...'],
                ],
            ];
        }

        $csrfToken = (string)$this->session->getCsrfToken();
        $siteLessonUrl = $baseUrl . '/cursos/' . urlencode((string)$course['slug']) . '/' . urlencode((string)$lesson['slug']);

        $html = $this->renderView('admin/lessons/editor', [
            'lessonId'         => $lessonId,
            'lesson'           => $lesson,
            'course'           => $course,
            'moduleTitle'      => $moduleTitle,
            'moduleFolderSlug' => $moduleFolderSlug,
            'flatLessons'      => $flatLessons,
            'prevLessonTitle'  => $prevLessonTitle,
            'nextLessonTitle'  => $nextLessonTitle,
            'initialBlocks'    => $initialBlocks,
            'csrfToken'        => $csrfToken,
            'baseUrl'          => $baseUrl,
            'siteLessonUrl'    => $siteLessonUrl,
        ]);

        return new ViewResponse($html);
    }

    public function updateContent(Request $request, $id): ResponseInterface
    {
        $lessonId = (int)$id;
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

        $rawBody = (string)file_get_contents('php://input');
        $jsonData = json_decode($rawBody, true);

        $token = '';
        $title = null;
        $status = null;
        $mobileProfile = null;
        $fabricatePhp = false;
        $blocks = null;

        if (is_array($jsonData)) {
            $token = (string)($jsonData['csrf_token'] ?? '');
            $title = isset($jsonData['title']) ? (string)$jsonData['title'] : null;
            $status = isset($jsonData['status']) ? (string)$jsonData['status'] : null;
            $mobileProfile = isset($jsonData['mobile_profile']) ? (string)$jsonData['mobile_profile'] : null;
            $fabricatePhp = !empty($jsonData['fabricate_php']);
            $blocks = $jsonData['blocks'] ?? null;
        } else {
            $token = (string)($request->input('csrf_token') ?? '');
            $title = $request->input('title');
            $status = $request->input('status');
            $mobileProfile = $request->input('mobile_profile');
            $fabricatePhp = !empty($request->input('fabricate_php'));
            $rawBlocks = $request->input('blocks_json');
            if ($rawBlocks) {
                $blocks = is_array($rawBlocks) ? $rawBlocks : json_decode((string)$rawBlocks, true);
            } elseif (is_array($request->input('blocks'))) {
                $blocks = $request->input('blocks');
            }
        }

        $expectedToken = (string)$this->session->getCsrfToken();
        if ($token === '' || !hash_equals($expectedToken, $token)) {
            if ($this->isAjax($request)) {
                return new Response(json_encode(['success' => false, 'message' => 'Token CSRF inválido']), 403, ['Content-Type' => 'application/json']);
            }
            $this->session->setFlash('error', "Token CSRF inválido.");
            return Response::redirect($baseUrl . '/admin/academy/lessons/' . $lessonId . '/editor');
        }

        $lesson = $this->structureRepo->getLessonById($lessonId);
        if (!$lesson) {
            if ($this->isAjax($request)) {
                return new Response(json_encode(['success' => false, 'message' => 'Aula não encontrada']), 404, ['Content-Type' => 'application/json']);
            }
            $this->session->setFlash('error', "Aula não encontrada.");
            return Response::redirect($baseUrl . '/admin/academy');
        }

        $contentToSave = is_array($blocks)
            ? (string)json_encode(['time' => time(), 'blocks' => $blocks], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : '';

        $effectiveTitle = ($title !== null && trim($title) !== '') ? trim($title) : (string)$lesson['title'];
        $effectiveProfile = ($mobileProfile !== null && in_array($mobileProfile, ['drawer', 'bottom_bar', 'cinema'], true))
            ? $mobileProfile
            : (string)($lesson['mobile_profile'] ?? 'drawer');

        $updateData = [
            'source_type'    => 'content',
            'content'        => $contentToSave,
            'mobile_profile' => $effectiveProfile,
        ];

        if ($title !== null && trim($title) !== '') {
            $updateData['title'] = $effectiveTitle;
        }
        if ($status !== null && in_array($status, ['published', 'draft'], true)) {
            $updateData['status'] = $status;
        }

        try {
            $fabricatedPath = null;
            if ($fabricatePhp && $this->scaffolder !== null) {
                $folderSlug = null;
                if (!empty($lesson['module_id'])) {
                    foreach ($this->structureRepo->getModules((int)$lesson['course_id']) as $m) {
                        if ((int)$m['id'] === (int)$lesson['module_id'] && !empty($m['folder_slug'])) {
                            $folderSlug = (string)$m['folder_slug'];
                            break;
                        }
                    }
                }

                $fabricated = $this->scaffolder->fabricateLessonFile(
                    $effectiveTitle,
                    $folderSlug,
                    $contentToSave !== '' ? $contentToSave : null,
                    $effectiveProfile
                );
                $updateData['slug'] = $fabricated['slug'];
                $updateData['source'] = $fabricated['relative_path'];
                $fabricatedPath = $fabricated['relative_path'];
            }

            $this->structureRepo->updateLesson($lessonId, $updateData);

            if ($this->isAjax($request)) {
                return new Response(json_encode([
                    'success'         => true,
                    'message'         => $fabricatedPath ? "Aula salva e arquivo '{$fabricatedPath}' fabricado!" : 'Aula salva com sucesso!',
                    'fabricated_path' => $fabricatedPath,
                ]), 200, ['Content-Type' => 'application/json']);
            }

            $this->session->setFlash('success', "Conteúdo da aula atualizado com sucesso!");
            return Response::redirect($baseUrl . '/admin/academy/lessons/' . $lessonId . '/editor');
        } catch (Throwable $e) {
            if ($this->isAjax($request)) {
                return new Response(json_encode([
                    'success' => false,
                    'message' => 'Erro ao salvar: ' . $e->getMessage(),
                ]), 500, ['Content-Type' => 'application/json']);
            }

            $this->session->setFlash('error', "Erro ao salvar aula: " . $e->getMessage());
            return Response::redirect($baseUrl . '/admin/academy/lessons/' . $lessonId . '/editor');
        }
    }

    private function isAjax(Request $request): bool
    {
        $accept = (string)($request->server['HTTP_ACCEPT'] ?? '');
        $xrw = (string)($request->server['HTTP_X_REQUESTED_WITH'] ?? '');
        $ct = (string)($request->server['CONTENT_TYPE'] ?? '');

        return str_contains($accept, 'application/json')
            || str_contains($xrw, 'XMLHttpRequest')
            || str_contains($ct, 'application/json');
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
