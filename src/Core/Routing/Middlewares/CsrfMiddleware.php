<?php

namespace DomainSystem\Core\Routing\Middlewares;

use DomainSystem\Core\Contracts\MiddlewareInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\SessionManager;
use Closure;

/**
 * ────────────────────────────────────────────────────────────────────────────
 * CLASSE: CsrfMiddleware
 * ────────────────────────────────────────────────────────────────────────────
 * SRP: Sua ÚNICA responsabilidade é evitar ataques de formulários cruzados (CSRF).
 */
class CsrfMiddleware implements MiddlewareInterface
{
    private SessionManager $session;

    public function __construct(SessionManager $session)
    {
        $this->session = $session;
    }

    public function handle(Request $request, Closure $next, array $routeConfig = []): mixed
    {
        // Apenas aplica validação CSRF para requisições de mutação
        if (!in_array(strtoupper($request->method()), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $next($request); // Deixa passar GET, HEAD, OPTIONS
        }

        // Ignora CSRF para rotas de API públicas ou webhooks
        $uri = strtok($request->uri(), '?');
        if (str_starts_with($uri, '/api/')) {
            return $next($request); // Deixa passar
        }

        $token = $request->input('csrf_token') ?? '';

        if (!$this->session->validateCsrfToken($token)) {
            // Log failed CSRF
            file_put_contents(DOMAIN_SYSTEM_ROOT . '/temp/csrf_debug.log', date('Y-m-d H:i:s') . " - CSRF Failed. Passed: $token, Expected: " . $this->session->get('csrf_token') . "\n", FILE_APPEND);
            
            http_response_code(403);
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
            
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'csrf', 'message' => 'Sessão de segurança expirada. Recarregue a tela.']);
                exit;
            } else {
                echo "<div style='font-family: sans-serif; padding: 40px; max-width: 600px; margin: 0 auto; text-align: center;'>";
                echo "<h2 style='color: #d63638;'><i class='fas fa-shield-alt'></i> Acesso Negado 🛑 (CSRF)</h2>";
                echo "<p style='font-size: 16px; color: #3c434a;'>Sua requisição foi bloqueada por motivos de segurança (Token Inválido ou Expirado).</p>";
                echo "<div style='background: #f0f6fc; border-left: 4px solid #72aee6; padding: 15px; margin: 20px 0; text-align: left;'>";
                echo "<strong>Por que isso aconteceu?</strong><br>Sua sessão pode ter expirado por inatividade ou você fez login em outra aba. O formulário que você tentou enviar continha uma credencial de segurança desatualizada.";
                echo "</div>";
                echo "<p style='font-weight: bold; color: #d63638;'>⚠️ IMPORTANTE: Após clicar em voltar, você DEVE recarregar a página (F5) para obter um novo token de segurança antes de tentar novamente!</p>";
                echo "<button onclick='window.history.back()' style='background: #2271b1; color: white; border: none; padding: 10px 20px; font-size: 16px; border-radius: 4px; cursor: pointer; margin-top: 15px;'>⬅️ Voltar</button>";
                echo "</div>";
                exit;
            }
        }

        // Se chegou até aqui, o token é válido! Passa a bola para o próximo.
        return $next($request);
    }
}
