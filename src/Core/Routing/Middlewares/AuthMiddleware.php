<?php

namespace DomainSystem\Core\Routing\Middlewares;

use DomainSystem\Core\Contracts\MiddlewareInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\SessionManager;
use Closure;

/**
 * ────────────────────────────────────────────────────────────────────────────
 * CLASSE: AuthMiddleware
 * ────────────────────────────────────────────────────────────────────────────
 * SRP: Sua ÚNICA responsabilidade é verificar se o usuário tem a "Role" certa
 * para acessar a rota.
 */
class AuthMiddleware implements MiddlewareInterface
{
    private SessionManager $session;

    public function __construct(SessionManager $session)
    {
        $this->session = $session;
    }

    public function handle(Request $request, Closure $next, array $routeConfig = []): mixed
    {
        $roles = $routeConfig['roles'] ?? [];

        // Se a rota não exige nenhuma role (pública), deixa passar livremente
        if (empty($roles)) {
            return $next($request);
        }

        $userRole = $this->session->get('user_role', '');

        // Se a role do usuário não está na lista de permitidas para esta rota
        if (!in_array($userRole, $roles)) {
            http_response_code(403);
            $html = '<div style="padding:20px; text-align:center; font-family:sans-serif;">'
                  . '<h2 style="color:#d63638;">Acesso Negado 🛑</h2>'
                  . '<p>O seu perfil (' . htmlspecialchars($userRole ?: 'Visitante') . ') não tem permissão para acessar esta área.</p>'
                  . '<a href="javascript:history.back()" style="display:inline-block; margin-top:10px; padding:10px 20px; background:#2271b1; color:#fff; text-decoration:none; border-radius:3px;">Voltar</a>'
                  . '</div>';
            echo $html;
            exit;
        }

        // Se tem permissão, passa a bola para o próximo.
        return $next($request);
    }
}
