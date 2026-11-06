<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Controllers;

use DomainSystem\Core\Contracts\DatabaseConnectionInterface;
use DomainSystem\Core\Contracts\PasswordPolicyInterface;
use DomainSystem\Core\Contracts\ResponseInterface;
use DomainSystem\Core\Contracts\SafeRedirectValidatorInterface;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Security\SafeRedirectValidator;
use DomainSystem\Plugins\academy\Contracts\StudentAuthServiceInterface;
use DomainSystem\Plugins\academy\Contracts\StudentViewRendererInterface;
use DomainSystem\Plugins\academy\Services\StudentAuthService;
use DomainSystem\Plugins\academy\Services\StudentViewRenderer;
use DomainSystem\SystemApps\auth\Contracts\LoginThrottleInterface;
use PDO;
use Throwable;

/**
 * Controlador de Autenticação e Cadastro da Sala de Aula / Alunos (SRP / DIP / Sem Superglobais).
 * Totalmente desacoplado do Painel Administrativo através dos contratos
 * StudentAuthServiceInterface e StudentViewRendererInterface.
 */
class StudentAuthController
{
    private SessionManagerInterface $session;
    private ?PDO $pdo;
    private StudentAuthServiceInterface $studentAuth;
    private StudentViewRendererInterface $viewRenderer;
    private SafeRedirectValidatorInterface $redirectValidator;
    private ?PasswordPolicyInterface $passwordPolicy;
    private ?LoginThrottleInterface $throttle;

    public function __construct(
        SessionManagerInterface $session,
        DatabaseConnectionInterface|PDO|null $db = null,
        ?StudentAuthServiceInterface $studentAuth = null,
        ?StudentViewRendererInterface $viewRenderer = null,
        ?SafeRedirectValidatorInterface $redirectValidator = null,
        ?PasswordPolicyInterface $passwordPolicy = null,
        ?LoginThrottleInterface $throttle = null
    ) {
        $this->session = $session;
        $this->pdo = $db instanceof DatabaseConnectionInterface ? $db->getPdo() : $db;
        $this->studentAuth = $studentAuth ?? new StudentAuthService($session);
        $this->viewRenderer = $viewRenderer ?? new StudentViewRenderer();
        $this->redirectValidator = $redirectValidator ?? new SafeRedirectValidator();
        $this->passwordPolicy = $passwordPolicy;
        $this->throttle = $throttle;
    }

    /**
     * Tela de Login do Aluno: GET /aluno/login
     */
    public function showLoginForm(Request $request): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

        // Se o usuário solicitou logout explícito (?logged_out=1), garante purga do escopo
        if ($request->has('logged_out')) {
            $this->studentAuth->logout();
        } elseif ($this->studentAuth->isLoggedIn()) {
            // Se já houver aluno autenticado no escopo da academia, redireciona para o painel de aulas
            $rawRedirect = (string)$request->input('redirect', $baseUrl . '/aluno/painel');
            $safeRedirect = $this->redirectValidator->sanitize($rawRedirect, $baseUrl . '/aluno/painel');
            return Response::redirect($safeRedirect);
        }

        $csrfToken = (string)$this->session->getCsrfToken();
        $rawRedirect = (string)$request->input('redirect', '');
        $redirect = $this->redirectValidator->sanitize($rawRedirect, '');
        $error = $this->studentAuth->getFlash('error') ?: (is_array($f = $this->session->getFlash()) && ($f['type'] ?? '') === 'error' ? $f['msg'] : null);
        $success = $this->studentAuth->getFlash('success') ?: (isset($f) && is_array($f) && ($f['type'] ?? '') === 'success' ? $f['msg'] : null);

        return $this->viewRenderer->render('front/student/login', [
            'baseUrl'   => $baseUrl,
            'csrfToken' => $csrfToken,
            'redirect'  => $redirect,
            'error'     => $error,
            'success'   => $success,
        ]);
    }

    /**
     * Processa a Autenticação do Aluno: POST /aluno/login
     */
    public function authenticate(Request $request): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $rawRedirect = (string)$request->input('redirect', '');
        $redirect = $this->redirectValidator->sanitize($rawRedirect, '');
        $targetUrl = $redirect !== '' ? $redirect : ($baseUrl . '/aluno/painel');

        $token = (string)$request->input('csrf_token', '');
        $expected = (string)$this->session->getCsrfToken();
        if ($token === '' || !hash_equals($expected, $token)) {
            $this->studentAuth->setFlash('error', 'Token de segurança expirado. Tente novamente.');
            return Response::redirect($baseUrl . '/aluno/login' . ($redirect !== '' ? '?redirect=' . urlencode($redirect) : ''));
        }

        $email = strtolower(trim((string)$request->input('email', '')));
        $password = (string)$request->input('password', '');

        if ($email === '' || $password === '') {
            $this->studentAuth->setFlash('error', 'Informe seu e-mail e sua senha de acesso.');
            return Response::redirect($baseUrl . '/aluno/login' . ($redirect !== '' ? '?redirect=' . urlencode($redirect) : ''));
        }

        // T14: Throttle anti-força bruta por chave de e-mail e IP
        $clientIp = (string)($request->server['REMOTE_ADDR'] ?? '127.0.0.1');
        $throttleKey = $email . '|' . $clientIp;

        if ($this->throttle !== null && $this->throttle->tooManyAttempts($throttleKey)) {
            $this->studentAuth->setFlash('error', 'Muitas tentativas de login incorretas. Aguarde alguns minutos antes de tentar novamente.');
            return Response::redirect($baseUrl . '/aluno/login' . ($redirect !== '' ? '?redirect=' . urlencode($redirect) : ''));
        }

        if ($this->pdo === null) {
            $this->studentAuth->setFlash('error', 'Serviço de autenticação temporariamente indisponível.');
            return Response::redirect($baseUrl . '/aluno/login');
        }

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE LOWER(email) = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // T21: Mitigação de timing attack via dummy hash (CWE-208)
        $dummyHash = '$2y$10$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUVWXYZ012';
        $hashToVerify = ($user && !empty($user['password'])) ? (string)$user['password'] : $dummyHash;
        $passwordValid = password_verify($password, $hashToVerify);

        if (!$user || !$passwordValid) {
            $this->throttle?->hit($throttleKey);
            $this->studentAuth->setFlash('error', 'E-mail ou senha incorretos.');
            return Response::redirect($baseUrl . '/aluno/login' . ($redirect !== '' ? '?redirect=' . urlencode($redirect) : ''));
        }

        $this->throttle?->clear($throttleKey);

        $userStatus = (string)($user['status'] ?? 'active');
        if ($userStatus === 'banned') {
            $this->studentAuth->setFlash('error', 'Sua conta de aluno foi banida por violação das diretrizes da plataforma.');
            return Response::redirect($baseUrl . '/aluno/login' . ($redirect !== '' ? '?redirect=' . urlencode($redirect) : ''));
        }
        if ($userStatus === 'suspended') {
            $this->studentAuth->setFlash('error', 'Sua conta de aluno está temporariamente suspensa. Entre em contato com o suporte.');
            return Response::redirect($baseUrl . '/aluno/login' . ($redirect !== '' ? '?redirect=' . urlencode($redirect) : ''));
        }

        // Regenera ID de sessão para mitigar Session Fixation
        $this->session->regenerate();

        // Login bem sucedido no escopo isolado do Aluno
        $username = isset($user['username']) && !empty($user['username']) ? (string)$user['username'] : null;
        $this->studentAuth->login((int)$user['id'], (string)$user['name'], (string)$user['email'], $username);

        $role = (string)($user['role'] ?? 'student');
        $this->session->set('academy_student_role', $role);
        $this->session->set('user_role', $role);
        $this->session->set('user_id', (int)$user['id']);
        $this->session->set('user_name', (string)$user['name']);

        // Define mensagem no escopo exclusivo do aluno e elimina resquícios de flashes administrativos
        $this->studentAuth->setFlash('success', 'Bem-vindo de volta, ' . htmlspecialchars((string)$user['name']) . '!');
        $this->session->remove('flash_message');
        return Response::redirect($targetUrl);
    }

    /**
     * Tela de Cadastro do Novo Aluno: GET /aluno/cadastro
     */
    public function showRegisterForm(Request $request): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

        if ($this->studentAuth->isLoggedIn()) {
            return Response::redirect($baseUrl . '/aluno/painel');
        }

        $csrfToken = (string)$this->session->getCsrfToken();
        $redirect = (string)$request->input('redirect', '');
        $error = $this->studentAuth->getFlash('error') ?: (is_array($f = $this->session->getFlash()) && ($f['type'] ?? '') === 'error' ? $f['msg'] : null);

        return $this->viewRenderer->render('front/student/register', [
            'baseUrl'   => $baseUrl,
            'csrfToken' => $csrfToken,
            'redirect'  => $redirect,
            'error'     => $error,
        ]);
    }

    /**
     * Processa a Criação de Conta do Aluno: POST /aluno/cadastro
     */
    public function register(Request $request): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $rawRedirect = (string)$request->input('redirect', '');
        $redirect = $this->redirectValidator->sanitize($rawRedirect, '');
        $targetUrl = $redirect !== '' ? $redirect : ($baseUrl . '/aluno/painel');

        $token = (string)$request->input('csrf_token', '');
        $expected = (string)$this->session->getCsrfToken();
        if ($token === '' || !hash_equals($expected, $token)) {
            $this->studentAuth->setFlash('error', 'Token de segurança expirado. Tente novamente.');
            return Response::redirect($baseUrl . '/aluno/cadastro');
        }

        $name = trim((string)$request->input('name', ''));
        $username = strtolower(trim((string)$request->input('username', '')));
        $email = strtolower(trim((string)$request->input('email', '')));
        $password = (string)$request->input('password', '');
        $passwordConfirm = (string)$request->input('password_confirmation', '');

        if ($name === '' || $email === '' || $password === '') {
            $this->studentAuth->setFlash('error', 'Preencha todos os campos obrigatórios.');
            return Response::redirect($baseUrl . '/aluno/cadastro');
        }

        // Se o username não foi preenchido, sugere a partir do nome
        if ($username === '') {
            $username = preg_replace('/[^a-z0-9_.]/', '', strtolower(str_replace(' ', '', $name)));
        }

        // Se ainda for muito curto
        if (strlen($username) < 3) {
            $username = 'aluno' . substr(md5($email . (string)microtime(true)), 0, 6);
        }

        // Validação de formato de username
        if (!preg_match('/^[a-z0-9_.-]{3,40}$/', $username)) {
            $this->studentAuth->setFlash('error', 'O nome de usuário deve conter apenas letras minúsculas, números, ponto ou traço (de 3 a 40 caracteres).');
            return Response::redirect($baseUrl . '/aluno/cadastro');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->studentAuth->setFlash('error', 'Por favor, informe um endereço de e-mail válido.');
            return Response::redirect($baseUrl . '/aluno/cadastro');
        }

        if ($this->passwordPolicy !== null && !$this->passwordPolicy->isAcceptable($password)) {
            $missing = implode(' ', $this->passwordPolicy->getMissingRequirements($password));
            $this->studentAuth->setFlash('error', 'A senha não atende aos requisitos: ' . $missing);
            return Response::redirect($baseUrl . '/aluno/cadastro');
        }
        if ($this->passwordPolicy === null && strlen($password) < 8) {
            $this->studentAuth->setFlash('error', 'A senha de acesso deve ter pelo menos 8 caracteres.');
            return Response::redirect($baseUrl . '/aluno/cadastro');
        }

        if ($password !== $passwordConfirm) {
            $this->studentAuth->setFlash('error', 'A confirmação de senha não confere.');
            return Response::redirect($baseUrl . '/aluno/cadastro');
        }

        if ($this->pdo === null) {
            $this->studentAuth->setFlash('error', 'Serviço de cadastro temporariamente indisponível.');
            return Response::redirect($baseUrl . '/aluno/cadastro');
        }

        // Verifica duplicidade de e-mail
        $stmtCheck = $this->pdo->prepare("SELECT id FROM users WHERE LOWER(email) = ? LIMIT 1");
        $stmtCheck->execute([$email]);
        if ($stmtCheck->fetchColumn()) {
            $this->studentAuth->setFlash('error', 'Este e-mail já possui cadastro. Faça login para continuar.');
            return Response::redirect($baseUrl . '/aluno/login');
        }

        // Verifica duplicidade de nome de usuário
        try {
            $stmtUserCheck = $this->pdo->prepare("SELECT id FROM users WHERE LOWER(username) = ? LIMIT 1");
            $stmtUserCheck->execute([$username]);
            if ($stmtUserCheck->fetchColumn()) {
                $this->studentAuth->setFlash('error', "O nome de usuário '@{$username}' já está em uso. Por favor, escolha outro.");
                return Response::redirect($baseUrl . '/aluno/cadastro');
            }
        } catch (Throwable $e) {
            // Caso a tabela ainda não contenha o campo username no ambiente
        }

        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            try {
                $stmt = $this->pdo->prepare("
                    INSERT INTO users (name, username, email, password, role, created_at, updated_at)
                    VALUES (?, ?, ?, ?, 'user', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ");
                $stmt->execute([$name, $username, $email, $hash]);
            } catch (\PDOException $e) {
                // Se o erro for de coluna ausente (ex: username), tenta fallback sem a coluna
                if (str_contains(strtolower($e->getMessage()), 'no such column') || str_contains(strtolower($e->getMessage()), 'unknown column')) {
                    $stmt = $this->pdo->prepare("
                        INSERT INTO users (name, email, password, role, created_at, updated_at)
                        VALUES (?, ?, ?, 'user', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                    ");
                    $stmt->execute([$name, $email, $hash]);
                } else {
                    // Propaga para tratamento específico de constraint ou erro geral
                    throw $e;
                }
            }

            $newId = (int)$this->pdo->lastInsertId();

            // Regenera ID de sessão para mitigar Session Fixation
            $this->session->regenerate();

            // Auto-login do novo aluno na sessão isolada da academia
            $this->studentAuth->login($newId, $name, $email, $username);

            $this->studentAuth->setFlash('success', 'Conta de aluno criada com sucesso! Seja bem-vindo à sala de aula.');
            $this->session->remove('flash_message');
            return Response::redirect($targetUrl);

        } catch (\PDOException $e) {
            // Código 23000 (ANSI SQL) / 1062 (MySQL) / SQLITE_CONSTRAINT
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'UNIQUE') || str_contains($e->getMessage(), 'Integrity constraint')) {
                $this->studentAuth->setFlash('error', 'Este e-mail ou nome de usuário já possui cadastro. Faça login para continuar.');
                return Response::redirect($baseUrl . '/aluno/login');
            }

            error_log('[StudentAuthController] Erro interno no cadastro: ' . $e->getMessage());
            $this->studentAuth->setFlash('error', 'Não foi possível concluir seu cadastro no momento. Por favor, tente novamente mais tarde.');
            return Response::redirect($baseUrl . '/aluno/cadastro');
        } catch (\Throwable $e) {
            error_log('[StudentAuthController] Falha inesperada no cadastro: ' . $e->getMessage());
            $this->studentAuth->setFlash('error', 'Ocorreu uma falha no processamento do cadastro. Tente novamente.');
            return Response::redirect($baseUrl . '/aluno/cadastro');
        }
    }

    /**
     * Logout do Aluno: GET|POST /aluno/logout
     */
    public function logout(Request $request): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $this->studentAuth->logout();
        $this->studentAuth->setFlash('success', 'Você saiu da sua conta.');
        $this->session->setFlash('success', 'Você saiu da sua conta.');

        $homeUrl = $baseUrl !== '' ? $baseUrl . '/' : '/';
        return Response::redirect($homeUrl);
    }
}
