<?php

declare(strict_types=1);

namespace DomainSystem\Core\Security;

use DomainSystem\Core\Contracts\SessionManagerInterface;

/**
 * ════════════════════════════════════════════════════════════════════════════
 * CLASSE: SessionManager (O Gerenciador de Sessões do Kernel)
 * ════════════════════════════════════════════════════════════════════════════
 *
 * OBJETIVO ARQUITETURAL:
 * ──────────────────────
 * Esta classe é o coração do estado do usuário. Ela implementa o contrato
 * `SessionManagerInterface` e é responsável por encapsular toda a interação
 * com a sessão nativa do PHP (a superglobal `$_SESSION`).
 *
 * Por que isso é importante?
 * - A arquitetura do DomainSystem (Ring 0 / Ring 3) proíbe que controladores
 *   e plugins acessem `$_SESSION` diretamente. Isso evita acoplamento e
 *   centraliza a lógica de segurança (como geração de CSRF).
 * - Testes de arquitetura (`NoSuperglobalsInControllersTest`) garantem que
 *   este contrato seja respeitado em todo o projeto.
 *
 * PADRÃO DE PROJETO: Facade / Wrapper
 * PRINCÍPIO SOLID:   SRP (Single Responsibility) — apenas gerencia a sessão.
 */
class SessionManager implements SessionManagerInterface
{
    /**
     * Inicia a sessão de forma segura e garante a existência de um token CSRF.
     *
     * Este método é chamado pelo Kernel no início do boot (`Application::boot()`),
     * antes de qualquer plugin ser carregado.
     */
    public function start(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            $isSecure = $this->isSecureRequest();

            session_set_cookie_params([
                'lifetime' => 0,          // Sessão expira quando o navegador fecha
                'path'     => '/',        // Cookie válido para todo o site
                'domain'   => '',         // Domínio atual
                'secure'   => $isSecure,  // Apenas HTTPS
                'httponly' => true,       // Inacessível via JavaScript (previne XSS)
                'samesite' => 'Strict'    // Previne CSRF (não envia em requisições cross-site)
            ]);

            ini_set('session.use_strict_mode', 1);

            // Mantém o mesmo hash de sessão para não invalidar sessões em andamento
            $legacySessionSeed = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Http';
            session_name('DS_SESS_' . substr(md5($legacySessionSeed), 0, 8));

            session_start();
        }

        if (!$this->has('csrf_token')) {
            $this->set('csrf_token', bin2hex(random_bytes(32)));
        }
    }

    /**
     * Retorna o token CSRF armazenado na sessão.
     */
    public function getCsrfToken(): string
    {
        return $this->get('csrf_token', '');
    }

    /**
     * Valida um token CSRF recebido contra o token armazenado na sessão.
     *
     * Usa `hash_equals` para uma comparação de tempo constante (timing attack safe).
     *
     * @param string|null $token O token a ser validado.
     * @return bool True se o token for válido, False caso contrário.
     */
    public function validateCsrfToken(?string $token): bool
    {
        if (empty($token) || !$this->has('csrf_token')) {
            return false;
        }
        return hash_equals($this->get('csrf_token'), $token);
    }

    /**
     * Obtém um valor da sessão.
     *
     * @param string $key A chave a ser buscada.
     * @param mixed $default O valor padrão a ser retornado se a chave não existir.
     * @return mixed O valor da sessão ou o padrão.
     */
    public function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Define um valor na sessão.
     *
     * @param string $key A chave a ser definida.
     * @param mixed $value O valor a ser armazenado.
     */
    public function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Remove um valor da sessão.
     *
     * @param string $key A chave a ser removida.
     */
    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Verifica se uma chave existe na sessão.
     *
     * @param string $key A chave a ser verificada.
     * @return bool True se a chave existe, False caso contrário.
     */
    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Destrói completamente a sessão atual: limpa a memória, expira o cookie no cliente e destrói os dados no servidor.
     */
    public function destroy(): void
    {
        // 1. Limpa o array em memória para a requisição corrente
        $_SESSION = [];

        // 2. Remove o cookie do navegador no cliente
        if (ini_get('session.use_cookies') && !headers_sent()) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'] ?? '/',
                $params['domain'] ?? '',
                (bool)($params['secure'] ?? false),
                (bool)($params['httponly'] ?? true)
            );
        }

        // 3. Destrói o arquivo/registro no servidor se a sessão estiver ativa
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    /**
     * Detecta se a requisição é HTTPS, com suporte a proxies reversos e CDNs (Cloudflare / Hostinger).
     */
    public function isSecureRequest(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }

        if (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) {
            return true;
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
            return true;
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_SSL']) === 'on') {
            return true;
        }

        return false;
    }

    /**
     * Define uma mensagem "flash" (temporária) na sessão.
     *
     * @param string $type O tipo da mensagem (ex: 'success', 'error', 'warning').
     * @param string $message O conteúdo da mensagem.
     */
    public function setFlash(string $type, string $message): void
    {
        $this->set('flash_message', ['type' => $type, 'msg' => $message]);
    }

    /**
     * Obtém e remove a mensagem flash da sessão.
     *
     * @return array|null Retorna um array com 'type' e 'msg', ou null se não houver.
     */
    public function getFlash(): ?array
    {
        $flash = $this->get('flash_message');
        $this->remove('flash_message');
        return $flash;
    }

    /**
     * Verifica se existe uma mensagem flash pendente (opcionalmente de um tipo específico).
     */
    public function hasFlash(?string $type = null): bool
    {
        $flash = $this->get('flash_message');
        if (!is_array($flash) || empty($flash['msg'])) {
            return false;
        }
        if ($type !== null) {
            return ($flash['type'] ?? '') === $type;
        }
        return true;
    }

    /**
     * Regenera o ID da sessão.
     */
    public function regenerate(): void
    {
        session_regenerate_id(true);
    }
}
