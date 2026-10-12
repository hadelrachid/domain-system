<?php

namespace DomainSystem\Core\Routing\Middlewares;

use DomainSystem\Core\Contracts\MiddlewareInterface;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Contracts\ResponseInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Http\Responses\JsonResponse;
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
    private SessionManagerInterface $session;

    public function __construct(SessionManagerInterface $session)
    {
        $this->session = $session;
    }

    public function handle(Request $request, Closure $next, array $routeConfig = []): mixed
    {
        // Apenas aplica validação CSRF para requisições de mutação
        if (!in_array(strtoupper($request->method()), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $next($request); // Deixa passar GET, HEAD, OPTIONS
        }

        // API Routing e CSRF: O Escudo Anti-CSRF
        $uri = strtok($request->uri(), '?');
        if (str_starts_with($uri, '/api/')) {
            // Bloqueia o bypass cego de API! 
            // Só libera sem token se for uma rota PÚBLICA (webhook, analytics)
            $authRequirements = $routeConfig['capabilities'] ?? $routeConfig['roles'] ?? [];
            if (empty($authRequirements)) {
                return $next($request);
            }
        }

        // Tenta recuperar do corpo da requisição ou do Cabeçalho (Ajax/Fetch)
        $token = $request->input('csrf_token');
        if (empty($token) && isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
        }
        
        $token = $token ?? '';

        if (!$this->session->validateCsrfToken($token)) {
            // Log failed CSRF
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $uri = $request->uri();
            $method = $request->method();
            $tokenPresent = empty($token) ? 'no' : 'yes';
            
            $logDir = DOMAIN_SYSTEM_ROOT . '/temp';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            file_put_contents($logDir . '/csrf_debug.log', date('Y-m-d H:i:s') . " - CSRF Failed. route=$uri method=$method IP=$ip token_present=$tokenPresent\n", FILE_APPEND);
            
            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || str_starts_with($uri, '/api/');
            
            if ($isAjax) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'csrf',
                    'message' => 'Sessão de segurança expirada. Recarregue a tela.'
                ], 403);
            }

            $html = "<div style='font-family: sans-serif; padding: 40px; max-width: 600px; margin: 0 auto; text-align: center;'>"
                . "<h2 style='color: #d63638;'><i class='fas fa-shield-alt'></i> Acesso Negado 🛑 (CSRF)</h2>"
                . "<p style='font-size: 16px; color: #3c434a;'>Sua requisição foi bloqueada por motivos de segurança (Token Inválido ou Expirado).</p>"
                . "<div style='background: #f0f6fc; border-left: 4px solid #72aee6; padding: 15px; margin: 20px 0; text-align: left;'>"
                . "<strong>Por que isso aconteceu?</strong><br>Sua sessão pode ter expirado por inatividade ou você fez login em outra aba. O formulário que você tentou enviar continha uma credencial de segurança desatualizada."
                . "</div>"
                . "<p style='font-weight: bold; color: #d63638;'>⚠️ IMPORTANTE: Após clicar em voltar, você DEVE recarregar a página (F5) para obter um novo token de segurança antes de tentar novamente!</p>"
                . "<button onclick='window.history.back()' style='background: #2271b1; color: white; border: none; padding: 10px 20px; font-size: 16px; border-radius: 4px; cursor: pointer; margin-top: 15px;'>⬅️ Voltar</button>"
                . "</div>";

            return new Response($html, 403);
        }

        // Se chegou até aqui, o token é válido! Passa a bola para o próximo.
        return $next($request);
    }
}
