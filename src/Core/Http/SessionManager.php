<?php

namespace DomainSystem\Core\Http;

class SessionManager
{
    public function start(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            // Isola a sessão por pasta para evitar que diferentes cópias do sistema no mesmo XAMPP compartilhem login
            session_name('DS_SESS_' . substr(md5(__DIR__), 0, 8));
            session_start();
        }

        if (!$this->has('csrf_token')) {
            $this->set('csrf_token', bin2hex(random_bytes(32)));
        }
    }

    public function getCsrfToken(): string
    {
        return $this->get('csrf_token', '');
    }

    public function validateCsrfToken(?string $token): bool
    {
        if (empty($token) || !$this->has('csrf_token')) {
            return false;
        }
        return hash_equals($this->get('csrf_token'), $token);
    }

    public function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function destroy(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            session_destroy();
        }
    }

    public function setFlash(string $type, string $message): void
    {
        $this->set('flash_message', ['type' => $type, 'msg' => $message]);
    }

    public function getFlash(): ?array
    {
        $flash = $this->get('flash_message');
        $this->remove('flash_message');
        return $flash;
    }

    public function regenerate(): void
    {
        session_regenerate_id(true);
    }
}
