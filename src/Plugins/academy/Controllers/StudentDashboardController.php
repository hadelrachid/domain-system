<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Controllers;

use DomainSystem\Core\Contracts\AccountStatusProviderInterface;
use DomainSystem\Core\Contracts\ResponseInterface;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Http\Responses\JsonResponse;
use DomainSystem\Core\Http\Responses\ViewResponse;
use DomainSystem\Plugins\academy\Contracts\CourseEnrollmentInterface;
use DomainSystem\Plugins\academy\Contracts\CourseRepositoryInterface;
use DomainSystem\Plugins\academy\Contracts\CourseStructureRepositoryInterface;
use DomainSystem\Plugins\academy\Contracts\StudentAuthServiceInterface;
use DomainSystem\Plugins\academy\Contracts\StudentProgressInterface;
use DomainSystem\Plugins\academy\Contracts\StudentStorageInterface;
use DomainSystem\Plugins\academy\Contracts\StudentViewRendererInterface;
use DomainSystem\Plugins\academy\Services\StudentAuthService;
use DomainSystem\Plugins\academy\Services\StudentViewRenderer;

/**
 * Controlador do Cockpit / Painel do Aluno (SRP / DIP / Sem Superglobais).
 * Totalmente isolado através de StudentAuthServiceInterface e StudentViewRendererInterface.
 */
class StudentDashboardController
{
    private SessionManagerInterface $session;
    private CourseEnrollmentInterface $enrollmentService;
    private CourseRepositoryInterface $courseRepo;
    private CourseStructureRepositoryInterface $structureRepo;
    private StudentProgressInterface $progressService;
    private StudentStorageInterface $studentStorage;
    private StudentAuthServiceInterface $studentAuth;
    private StudentViewRendererInterface $viewRenderer;
    private ?\PDO $pdo;
    private ?AccountStatusProviderInterface $accountStatus;

    public function __construct(
        SessionManagerInterface $session,
        CourseEnrollmentInterface $enrollmentService,
        CourseRepositoryInterface $courseRepo,
        CourseStructureRepositoryInterface $structureRepo,
        StudentProgressInterface $progressService,
        StudentStorageInterface $studentStorage,
        ?StudentAuthServiceInterface $studentAuth = null,
        ?StudentViewRendererInterface $viewRenderer = null,
        \DomainSystem\Core\Contracts\DatabaseConnectionInterface|\PDO|null $db = null,
        ?AccountStatusProviderInterface $accountStatus = null
    ) {
        $this->session = $session;
        $this->enrollmentService = $enrollmentService;
        $this->courseRepo = $courseRepo;
        $this->structureRepo = $structureRepo;
        $this->progressService = $progressService;
        $this->studentStorage = $studentStorage;
        $this->studentAuth = $studentAuth ?? new StudentAuthService($session);
        $this->viewRenderer = $viewRenderer ?? new StudentViewRenderer();
        $this->pdo = $db instanceof \DomainSystem\Core\Contracts\DatabaseConnectionInterface ? $db->getPdo() : $db;
        $this->accountStatus = $accountStatus;
    }

    /**
     * Painel do Aluno: GET /aluno/painel
     */
    public function index(Request $request): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        if (!$this->studentAuth->isLoggedIn()) {
            $this->session->setFlash('warning', 'Faça login na sua conta de aluno para acessar o painel de aulas.');
            return Response::redirect($baseUrl . '/aluno/login?redirect=' . urlencode($baseUrl . '/aluno/painel'));
        }

        $studentId = (int)$this->studentAuth->getStudentId();
        if ($studentId > 0 && $this->accountStatus !== null && $this->accountStatus->statusOf($studentId) !== 'active') {
            $this->studentAuth->logout();
            $this->session->destroy();
            return Response::redirect($baseUrl . '/aluno/login');
        }
        $studentName = (string)($this->studentAuth->getStudentName() ?? 'Aluno');
        $studentUsername = $this->studentAuth->getStudentUsername();

        // Sincroniza dados atualizados do banco (nome oficial, username e role)
        if ($studentId > 0 && $this->pdo !== null) {
            try {
                $stmt = $this->pdo->prepare("SELECT name, username, role FROM users WHERE id = ? LIMIT 1");
                $stmt->execute([$studentId]);
                if ($u = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $studentName = (string)$u['name'];
                    $this->session->set('academy_student_name', $studentName);
                    if (!empty($u['username'])) {
                        $studentUsername = (string)$u['username'];
                        $this->session->set('academy_student_username', $studentUsername);
                    }
                    if (!empty($u['role'])) {
                        $this->session->set('academy_student_role', (string)$u['role']);
                        $this->session->set('user_role', (string)$u['role']);
                    }
                }
            } catch (\Throwable $e) {
                // Fallback defensivo caso a coluna username não exista na tabela users
                try {
                    $stmt = $this->pdo->prepare("SELECT name, role FROM users WHERE id = ? LIMIT 1");
                    $stmt->execute([$studentId]);
                    if ($u = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                        $studentName = (string)$u['name'];
                        $this->session->set('academy_student_name', $studentName);
                        if (!empty($u['role'])) {
                            $this->session->set('academy_student_role', (string)$u['role']);
                            $this->session->set('user_role', (string)$u['role']);
                        }
                    }
                } catch (\Throwable $e2) {
                    // Silencioso se não conseguir ler
                }
            }
        }

        $userRole = (string)$this->session->get('academy_student_role', (string)$this->session->get('user_role', ''));
        $isAdmin = in_array($userRole, ['admin', 'manager', 'instructor'], true);

        // Se for administrador/instrutor, tem acesso irrestrito a todas as formações publicadas
        $allPublished = $this->courseRepo->findPublished(['limit' => 100]);

        if ($isAdmin) {
            $activeCourseIds = array_map(fn($c) => (int)$c['id'], $allPublished);
        } else {
            $activeCourseIds = $this->enrollmentService->getActiveCourseIds($studentId);
        }

        $enrolledCourses = [];

        foreach ($activeCourseIds as $cId) {
            $c = $this->courseRepo->findById($cId);
            if ($c && ($c['status'] ?? '') === 'published') {
                $pct = $this->progressService->getCourseProgressPercentage($studentId, $cId);
                $lessons = $this->structureRepo->getLessons($cId);
                $completedIds = $this->progressService->getCompletedLessonIds($studentId, $cId);

                // Identifica a próxima aula pendente para continuar assistindo
                $nextLesson = null;
                foreach ($lessons as $l) {
                    if (($l['status'] ?? 'published') === 'published' && !in_array((int)$l['id'], $completedIds, true)) {
                        $nextLesson = $l;
                        break;
                    }
                }
                if ($nextLesson === null && !empty($lessons)) {
                    $nextLesson = $lessons[0];
                }

                $enrolledCourses[] = [
                    'course'           => $c,
                    'progress_pct'     => $pct,
                    'completed_count'  => count($completedIds),
                    'total_lessons'    => count($lessons),
                    'next_lesson'      => $nextLesson,
                    'course_url'       => $baseUrl . '/cursos/' . urlencode((string)$c['slug']),
                    'resume_url'       => $nextLesson ? ($baseUrl . '/cursos/' . urlencode((string)$c['slug']) . '/' . urlencode((string)$nextLesson['slug'])) : null,
                ];
            }
        }

        // Cursos disponíveis para compra ou novas matrículas no catálogo (alunos comuns)
        $availableCourses = [];
        if (!$isAdmin) {
            foreach ($allPublished as $pub) {
                if (!in_array((int)$pub['id'], $activeCourseIds, true)) {
                    $availableCourses[] = $pub;
                }
            }
        }

        $studentFiles = $this->studentStorage->listStudentFiles($studentId);
        $flash = $this->studentAuth->getFlash();

        return $this->viewRenderer->render('front/student/dashboard', [
            'baseUrl'          => $baseUrl,
            'studentId'        => $studentId,
            'studentName'      => $studentName,
            'studentUsername'  => $studentUsername,
            'isAdmin'          => $isAdmin,
            'enrolledCourses'  => $enrolledCourses,
            'availableCourses' => $availableCourses,
            'studentFiles'     => $studentFiles,
            'flash'            => $flash,
            'csrfToken'        => (string)$this->session->getCsrfToken(),
        ]);
    }

    /**
     * Alterna a conclusão de uma aula: POST /aluno/aulas/{id}/toggle-complete
     */
    public function toggleLessonProgress(Request $request, $id): ResponseInterface
    {
        $studentId = (int)$this->studentAuth->getStudentId();
        if ($studentId <= 0) {
            return new JsonResponse(['success' => false, 'message' => 'Usuário não autenticado.'], 401);
        }

        $token = (string)($request->input('csrf_token') ?? ($request->server['HTTP_X_CSRF_TOKEN'] ?? ''));
        $expectedToken = (string)$this->session->getCsrfToken();
        if ($token === '' || !hash_equals($expectedToken, $token)) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF inválido.'], 403);
        }

        $lessonId = (int)$id;
        $lesson = $this->structureRepo->getLessonById($lessonId);
        if (!$lesson) {
            return new JsonResponse(['success' => false, 'message' => 'Aula não encontrada.'], 404);
        }

        $courseId = (int)$lesson['course_id'];
        $isCompleted = $this->progressService->isLessonCompleted($studentId, $lessonId);

        if ($isCompleted) {
            $this->progressService->markLessonIncomplete($studentId, $courseId, $lessonId);
            $newStatus = false;
        } else {
            $this->progressService->markLessonCompleted($studentId, $courseId, $lessonId);
            $newStatus = true;
        }

        $newPct = $this->progressService->getCourseProgressPercentage($studentId, $courseId);

        return new JsonResponse([
            'success'      => true,
            'completed'    => $newStatus,
            'progress_pct' => $newPct,
            'message'      => $newStatus ? 'Aula marcada como concluída!' : 'Aula desmarcada.',
        ]);
    }

    /**
     * Salva anotação pessoal do aluno: POST /aluno/aulas/{id}/notes
     */
    public function saveLessonNote(Request $request, $id): ResponseInterface
    {
        $studentId = (int)$this->studentAuth->getStudentId();
        if ($studentId <= 0) {
            return new JsonResponse(['success' => false, 'message' => 'Usuário não autenticado.'], 401);
        }

        $token = (string)($request->input('csrf_token') ?? ($request->server['HTTP_X_CSRF_TOKEN'] ?? ''));
        $expectedToken = (string)$this->session->getCsrfToken();
        if ($token === '' || !hash_equals($expectedToken, $token)) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF inválido.'], 403);
        }

        $lessonId = (int)$id;
        $lesson = $this->structureRepo->getLessonById($lessonId);
        if (!$lesson) {
            return new JsonResponse(['success' => false, 'message' => 'Aula não encontrada.'], 404);
        }

        $content = (string)($request->input('content') ?? '');
        $saved = $this->studentStorage->saveStudentNote($studentId, (int)$lesson['course_id'], $lessonId, $content);

        return new JsonResponse([
            'success' => $saved,
            'message' => $saved ? 'Anotação salva com sucesso!' : 'Falha ao salvar anotação.',
        ]);
    }

    /**
     * Carrega anotação pessoal do aluno: GET /aluno/aulas/{id}/notes
     */
    public function getLessonNote(Request $request, $id): ResponseInterface
    {
        $studentId = (int)$this->studentAuth->getStudentId();
        if ($studentId <= 0) {
            return new JsonResponse(['success' => false, 'message' => 'Usuário não autenticado.'], 401);
        }

        $lessonId = (int)$id;
        $lesson = $this->structureRepo->getLessonById($lessonId);
        if (!$lesson) {
            return new JsonResponse(['success' => false, 'message' => 'Aula não encontrada.'], 404);
        }

        $note = $this->studentStorage->getStudentNote($studentId, (int)$lesson['course_id'], $lessonId);

        return new JsonResponse([
            'success' => true,
            'content' => $note,
        ]);
    }
}
