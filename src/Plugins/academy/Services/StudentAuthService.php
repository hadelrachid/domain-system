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
        if ((bool)$this->session->get('academy_logged_out', false)) {
            return null;
        }

        $id = filter_var($this->session->get(self::SESSION_STUDENT_ID), FILTER_VALIDATE_INT);
        return ($id !== false && $id > 0) ? $id : null;
    }

    public function getStudentName(): ?string
    {
        if ((bool)$this->session->get('academy_logged_out', false)) {
            return null;
        }

        $name = trim((string)$this->session->get(self::SESSION_STUDENT_NAME, ''));
        return $name !== '' ? $name : null;
    }

    public function getStudentUsername(): ?string
    {
        if ((bool)$this->session->get('academy_logged_out', false)) {
            return null;
        }

        $user = trim((string)$this->session->get(self::SESSION_STUDENT_USERNAME, ''));
        return $user !== '' ? $user : null;
    }

    public function getStudentEmail(): ?string
    {
        if ((bool)$this->session->get('academy_logged_out', false)) {
            return null;
        }

        $email = trim((string)$this->session->get(self::SESSION_STUDENT_EMAIL, ''));
        return $email !== '' ? $email : null;
    }

    public function login(int $studentId, string $name, string $email, ?string $username = null): void
    {
        if ($studentId <= 0) {
            throw new \InvalidArgumentException('ID de aluno inválido.');
        }

        $this->session->remove('academy_logged_out');
        $this->session->set(self::SESSION_STUDENT_ID, $studentId);
        $this->session->set(self::SESSION_STUDENT_NAME, trim($name));
        $this->session->set(self::SESSION_STUDENT_EMAIL, strtolower(trim($email)));

        if ($username !== null && trim($username) !== '') {
            $this->session->set(self::SESSION_STUDENT_USERNAME, trim($username));
        } else {
            $this->session->remove(self::SESSION_STUDENT_USERNAME);
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

        // Isola completamente: remove EXCLUSIVAMENTE chaves de aluno
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
