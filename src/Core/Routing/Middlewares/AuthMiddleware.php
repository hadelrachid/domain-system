<?php

namespace DomainSystem\Core\Routing\Middlewares;

use DomainSystem\Core\Contracts\MiddlewareInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\SessionManager;
use DomainSystem\Core\Security\IdentityManager;
use Closure;

/**
 * Motor de Interceptação (Catraca) do Web OS
 * Verifica se a Identidade logada possui a Capability (Privilégio) exigida para a rota.
 */
class AuthMiddleware implements MiddlewareInterface
{
    private SessionManager $session;
    private IdentityManager $identity;

    public function __construct(SessionManager $session, IdentityManager $identity)
    {
        $this->session = $session;
        $this->identity = $identity;
    }

    public function handle(Request $request, Closure $next, array $routeConfig = []): mixed
    {
        // Aceita 'capabilities' ou cai pra 'roles' (legado de plugins ainda não atualizados)
        $capabilities = $routeConfig['capabilities'] ?? $routeConfig['roles'] ?? [];

        // Se a rota for pública (sem capabilities exigidas), passa a bola
        if (empty($capabilities)) {
            return $next($request);
        }

        $userId = $this->session->get('user_id');

        // Se não estiver logado
        if (!$userId) {
            return $this->blockAccess('Visitante Anônimo');
        }

        $hasAccess = false;
        foreach ($capabilities as $cap) {
            // Verifica se a identity possui a capability exata (ex: 'core.plugin.install') 
            // OU se é a string de uma role legada autorizada (ex: 'admin')
            if ($this->identity->userCan($userId, $cap) || $this->identity->hasRole($userId, $cap)) {
                $hasAccess = true;
                break;
            }
        }

        if (!$hasAccess) {
            $userName = $this->session->get('user_name', 'Usuário ' . $userId);
            return $this->blockAccess($userName);
        }

        return $next($request);
    }
    
    private function blockAccess(string $identityName): \DomainSystem\Core\Http\Response
    {
        $html = '<div style="padding:20px; text-align:center; font-family:sans-serif;"><h2 style="color:#d63638;">Acesso Negado 🛑</h2><p>Sua identidade (' . htmlspecialchars($identityName) . ') não possui os privilégios necessários para executar esta ação no sistema.</p><a href="javascript:history.back()" style="display:inline-block; margin-top:10px; padding:10px 20px; background:#2271b1; color:#fff; text-decoration:none; border-radius:3px;">Voltar com Segurança</a></div>';
        return new \DomainSystem\Core\Http\Response($html, 403);
    }
}
