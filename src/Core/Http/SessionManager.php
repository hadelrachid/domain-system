<?php

namespace DomainSystem\Core\Http;

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
        // Só inicia a sessão se ela ainda não estiver ativa e se os cabeçalhos
        // HTTP ainda não foram enviados (evita erros "headers already sent").
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            
            // -----------------------------------------------------------------
            // 1. CONFIGURAÇÃO DE SEGURANÇA DOS COOKIES DE SESSÃO
            // -----------------------------------------------------------------
            // Verifica se a conexão é HTTPS para definir a flag 'secure' do cookie.
            // Isso impede que o cookie de sessão seja trafegado em texto puro.
            $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
                        (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
            
            session_set_cookie_params([
                'lifetime' => 0,          // Sessão expira quando o navegador fecha
                'path'     => '/',        // Cookie válido para todo o site
                'domain'   => '',         // Domínio atual
                'secure'   => $isSecure,  // Apenas HTTPS
                'httponly' => true,       // Inacessível via JavaScript (previne XSS)
                'samesite' => 'Strict'    // Previne CSRF (não envia em requisições cross-site)
            ]);
            
            // Ativa o modo de segurança estrito do PHP, que impede a fixação de ID de sessão.
            // Ajuda a mitigar ataques de "Session Fixation".
            ini_set('session.use_strict_mode', 1);

            // -----------------------------------------------------------------
            // 2. ISOLAMENTO DE SESSÃO (Evita conflito com outras cópias do sistema)
            // -----------------------------------------------------------------
            // Isola a sessão por pasta para evitar que diferentes cópias do sistema
            // no mesmo XAMPP compartilhem login.
            // Gera um nome de sessão baseado no hash do diretório da classe.
            session_name('DS_SESS_' . substr(md5(__DIR__), 0, 8));
            
            // Finalmente, inicia a sessão no PHP.
            session_start();
        }

        // ---------------------------------------------------------------------
        // 3. GERAÇÃO DO TOKEN CSRF (Sempre Garantido)
        // ---------------------------------------------------------------------
        // Garante que um token CSRF sempre exista na sessão.
        // Este token é usado pelo `CsrfMiddleware` para validar todas as requisições
        // POST, PUT, PATCH e DELETE, protegendo o sistema contra ataques CSRF.
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
        // Se o token enviado estiver vazio ou não houver token na sessão, falha.
        if (empty($token) || !$this->has('csrf_token')) {
            return false;
        }
        // Compara os tokens de forma segura contra ataques de timing.
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
     * Destrói completamente a sessão atual.
     *
     * Usado no logout, por exemplo.
     */
    public function destroy(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            session_destroy();
        }
    }

    /**
     * Define uma mensagem "flash" (temporária) na sessão.
     *
     * Mensagens flash são exibidas uma vez e depois removidas, sendo
     * muito úteis para feedback após redirecionamentos (ex: "Usuário criado com sucesso!").
     *
     * @param string $type O tipo da mensagem (ex: 'success', 'error', 'warning').
     * @param string $message O conteúdo da mensagem.
     */
    public function setFlash(string $type, string $message): void
    {
        // Armazena a mensagem como um array estruturado.
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
        // Remove a mensagem imediatamente após a leitura para que ela seja exibida apenas uma vez.
        $this->remove('flash_message');
        return $flash;
    }

    /**
     * Regenera o ID da sessão.
     *
     * Essencial para a segurança, deve ser chamado após o login do usuário
     * para prevenir ataques de fixação de sessão.
     */
    public function regenerate(): void
    {
        // O parâmetro 'true' faz com que a sessão antiga seja destruída.
        session_regenerate_id(true);
    }
}

