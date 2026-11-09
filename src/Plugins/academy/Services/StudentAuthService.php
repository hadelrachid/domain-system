<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Services;

use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Plugins\academy\Contracts\StudentAuthServiceInterface;

/**
 * Serviço de Gerenciamento da Sessão e Identidade do Aluno (SRP / DIP).
 * Armazena a identidade do aluno em chaves escopadas ('academy_student_*'),
 * garantindo zero acoplamento e zero colisão com a sessão administrativa do núcleo.
 */
class StudentAuthService implements StudentAuthServiceInterface
{
    public const SESSION_STUDENT_ID       = 'academy_student_id';
    public const SESSION_STUDENT_NAME     = 'academy_student_name';
    public const SESSION_STUDENT_USERNAME = 'academy_student_username';
    public const SESSION_STUDENT_EMAIL    = 'academy_student_email';
    public const SESSION_STUDENT_FLASH    = 'academy_student_flash';

    private SessionManagerInterface $session;

    public function __construct(SessionManagerInterface $session)
    {
        $this->session = $session;
    }

    public function isLoggedIn(): bool
    {
        $id = $this->getStudentId();
        return $id !== null && $id > 0;
    }

    public function getStudentId(): ?int
    {
        // 1. Chave explícita e isolada de aluno
        $id = (int)$this->session->get(self::SESSION_STUDENT_ID, 0);
        if ($id > 0) {
            return $id;
        }

        // Se marcou logout explícito de aluno, não faz fallback
        if ((bool)$this->session->get('academy_logged_out', false)) {
            return null;
        }

        // 2. Proteção de isolamento de privilégios:
        // Apenas usuários comuns (user/student) podem fazer fallback de contexto para o aluno
        $role = strtolower((string)$this->session->get('user_role', ''));
        if (in_array($role, ['user', 'student'], true)) {
            $fallbackId = (int)$this->session->get('user_id', 0);
            return $fallbackId > 0 ? $fallbackId : null;
        }

        return null;
    }

    public function getStudentName(): ?string
    {
        $name = (string)$this->session->get(self::SESSION_STUDENT_NAME, '');
        if ($name !== '') {
            return $name;
        }

        $role = strtolower((string)$this->session->get('user_role', ''));
        if (in_array($role, ['user', 'student'], true)) {
            return (string)$this->session->get('user_name', 'Aluno');
        }

        return null;
    }

    public function getStudentUsername(): ?string
    {
        $user = (string)$this->session->get(self::SESSION_STUDENT_USERNAME, '');
        return $user !== '' ? $user : null;
    }

    public function getStudentEmail(): ?string
    {
        $email = (string)$this->session->get(self::SESSION_STUDENT_EMAIL, '');
        if ($email !== '') {
            return $email;
        }

        $role = strtolower((string)$this->session->get('user_role', ''));
        if (in_array($role, ['user', 'student'], true)) {
            return (string)$this->session->get('user_email', '');
        }

        return null;
    }

    public function login(int $studentId, string $name, string $email, ?string $username = null): void
    {
        $this->session->remove('academy_logged_out');
        $this->session->set(self::SESSION_STUDENT_ID, $studentId);
        $this->session->set(self::SESSION_STUDENT_NAME, $name);
        $this->session->set(self::SESSION_STUDENT_EMAIL, $email);

        if ($username !== null && $username !== '') {
            $this->session->set(self::SESSION_STUDENT_USERNAME, $username);
        }
    }

    public function logout(): void
    {
        $keys = [
            self::SESSION_STUDENT_ID,
            self::SESSION_STUDENT_NAME,
            self::SESSION_STUDENT_USERNAME,
            self::SESSION_STUDENT_EMAIL,
            self::SESSION_STUDENT_FLASH,
            'academy_student_role',
        ];

        // Se o usuário logado NÃO for administrador do OS (ou seja, for apenas student ou user comum),
        // purga também as credenciais comuns para evitar reaproveitamento de sessão
        $role = strtolower((string)$this->session->get('user_role', ''));
        $isAdmin = in_array($role, ['admin', 'manager', 'emergency'], true);
        if (!$isAdmin) {
            $keys[] = 'user_id';
            $keys[] = 'user_name';
            $keys[] = 'user_email';
            $keys[] = 'user_role';
            $keys[] = 'is_authenticated';
        }

        foreach ($keys as $k) {
            $this->session->remove($k);
        }

        $this->session->set('academy_logged_out', true);
        $this->session->regenerate(false);
    }

    public function setFlash(string $type, string $message): void
    {
        $payload = ['type' => $type, 'msg' => $message];
        $this->session->set(self::SESSION_STUDENT_FLASH, $payload);
    }

    public function getFlash(?string $type = null): array|string|null
    {
        $flash = $this->session->get(self::SESSION_STUDENT_FLASH);

        if (!is_array($flash)) {
            return null;
        }

        if ($type !== null) {
            if (($flash['type'] ?? '') === $type) {
                $this->session->remove(self::SESSION_STUDENT_FLASH);
                return (string)($flash['msg'] ?? '');
            }
            return null;
        }

        $this->session->remove(self::SESSION_STUDENT_FLASH);
        return $flash;
    }
}
