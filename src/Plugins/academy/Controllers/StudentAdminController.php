<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Controllers;

use DomainSystem\Core\Contracts\PasswordPolicyInterface;
use DomainSystem\Core\Contracts\ResponseInterface;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Contracts\ThemeManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Http\Responses\ViewResponse;
use DomainSystem\Plugins\academy\Contracts\CourseRepositoryInterface;
use DomainSystem\Plugins\academy\Contracts\StudentAdminServiceInterface;
use DomainSystem\Plugins\academy\Contracts\StudentViewRendererInterface;
use DomainSystem\Plugins\academy\Services\StudentViewRenderer;

class StudentAdminController
{
    private StudentAdminServiceInterface $studentService;
    private CourseRepositoryInterface $courseRepo;
    private SessionManagerInterface $session;
    private ?ThemeManagerInterface $theme;
    private StudentViewRendererInterface $viewRenderer;
    private ?PasswordPolicyInterface $passwordPolicy;

    public function __construct(
        StudentAdminServiceInterface $studentService,
        CourseRepositoryInterface $courseRepo,
        SessionManagerInterface $session,
        ?ThemeManagerInterface $theme = null,
        ?StudentViewRendererInterface $viewRenderer = null,
        ?PasswordPolicyInterface $passwordPolicy = null
    ) {
        $this->studentService = $studentService;
        $this->courseRepo = $courseRepo;
        $this->session = $session;
        $this->theme = $theme;
        $this->viewRenderer = $viewRenderer ?? new StudentViewRenderer();
        $this->passwordPolicy = $passwordPolicy;
    }

    public function index(Request $request): ResponseInterface
    {
        $search = trim((string)$request->input('q', ''));
        $status = trim((string)$request->input('status', 'all'));
        $page = max(1, (int)$request->input('page', 1));
        $perPage = 15;

        $searchParam = $search !== '' ? $search : null;
        $statusParam = $status !== 'all' ? $status : null;

        $data = $this->studentService->listStudents($page, $perPage, $searchParam, $statusParam);
        $metrics = $this->studentService->getStudentMetrics();
        $allCourses = $this->courseRepo->findAll();

        $totalPages = max(1, (int)ceil($data['total'] / $perPage));
        $csrfToken = (string)$this->session->getCsrfToken();
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

        $flash = $this->session->getFlash();
        $flashType = is_array($flash) ? ($flash['type'] ?? null) : null;
        $flashMsg = is_array($flash) ? ($flash['msg'] ?? null) : null;

        $html = $this->renderView('admin/students/index', [
            'students' => $data['items'],
            'total' => $data['total'],
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
            'currentSearch' => $search,
            'currentStatus' => $status,
            'metrics' => $metrics,
            'allCourses' => $allCourses,
            'csrfToken' => $csrfToken,
            'baseUrl' => $baseUrl,
            'flashType' => $flashType,
            'flashMsg' => $flashMsg,
        ]);

        return new ViewResponse($html);
    }

    public function changeStatus(Request $request, int|string $id): ResponseInterface
    {
        if (!$this->verifyCsrfToken($request)) {
            $this->session->setFlash('error', 'Token de segurança expirado. Tente novamente.');
            return $this->redirectBack();
        }
        $studentId = (int)$id;
        $status = (string)$request->input('status', '');

        if ($this->studentService->setStudentStatus($studentId, $status)) {
            $labels = [
                'active' => 'reativado',
                'suspended' => 'suspenso',
                'banned' => 'banido com sucesso'
            ];
            $this->session->setFlash('success', 'Status do aluno atualizado: ' . ($labels[$status] ?? $status));
        } else {
            $this->session->setFlash('error', 'Falha ao alterar o status do aluno.');
        }

        return $this->redirectBack();
    }

    public function enroll(Request $request, int|string $id): ResponseInterface
    {
        if (!$this->verifyCsrfToken($request)) {
            $this->session->setFlash('error', 'Token de segurança expirado. Tente novamente.');
            return $this->redirectBack();
        }
        $studentId = (int)$id;
        $courseId = (int)$request->input('course_id', 0);

        if ($courseId <= 0) {
            $this->session->setFlash('error', 'Selecione um curso válido para matricular o aluno.');
            return $this->redirectBack();
        }

        if ($this->studentService->enrollStudent($studentId, $courseId)) {
            $this->session->setFlash('success', 'Aluno matriculado no curso com sucesso!');
        } else {
            $this->session->setFlash('error', 'Não foi possível concluir a matrícula.');
        }

        return $this->redirectBack();
    }

    public function revoke(Request $request, int|string $id): ResponseInterface
    {
        if (!$this->verifyCsrfToken($request)) {
            $this->session->setFlash('error', 'Token de segurança expirado. Tente novamente.');
            return $this->redirectBack();
        }
        $studentId = (int)$id;
        $courseId = (int)$request->input('course_id', 0);

        if ($courseId <= 0) {
            $this->session->setFlash('error', 'Identificador de curso inválido para revogação.');
            return $this->redirectBack();
        }

        if ($this->studentService->revokeEnrollment($studentId, $courseId)) {
            $this->session->setFlash('success', 'Matrícula cancelada/revogada com sucesso.');
        } else {
            $this->session->setFlash('error', 'Falha ao revogar a matrícula.');
        }

        return $this->redirectBack();
    }

    public function resetPassword(Request $request, int|string $id): ResponseInterface
    {
        if (!$this->verifyCsrfToken($request)) {
            $this->session->setFlash('error', 'Token de segurança expirado. Tente novamente.');
            return $this->redirectBack();
        }
        $studentId = (int)$id;
        $newPassword = (string)$request->input('new_password', '');

        if ($this->passwordPolicy !== null && !$this->passwordPolicy->isAcceptable($newPassword)) {
            $missing = implode(' ', $this->passwordPolicy->getMissingRequirements($newPassword));
            $this->session->setFlash('error', 'A nova senha não atende aos requisitos: ' . $missing);
            return $this->redirectBack();
        }

        if ($this->passwordPolicy === null && strlen($newPassword) < 8) {
            $this->session->setFlash('error', 'A nova senha deve ter no mínimo 8 caracteres.');
            return $this->redirectBack();
        }

        if ($this->studentService->resetStudentPassword($studentId, $newPassword)) {
            $this->session->setFlash('success', 'Senha do aluno redefinida com sucesso!');
        } else {
            $this->session->setFlash('error', 'Erro ao redefinir senha do aluno.');
        }

        return $this->redirectBack();
    }

    private function verifyCsrfToken(Request $request): bool
    {
        $token = (string)$request->input('csrf_token', '');
        $expected = (string)$this->session->getCsrfToken();
        if ($token === '' || $expected === '') {
            return false;
        }
        return hash_equals($expected, $token);
    }

    private function redirectBack(): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        return Response::redirect($baseUrl . '/admin/academy/students');
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
