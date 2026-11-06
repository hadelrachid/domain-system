<?php

declare(strict_types=1);

namespace DomainSystem\Core\Routing\Middlewares;

use Closure;
use DomainSystem\Core\Contracts\AccountStatusProviderInterface;
use DomainSystem\Core\Contracts\MiddlewareInterface;
use DomainSystem\Core\Contracts\SafeRedirectValidatorInterface;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Http\Responses\JsonResponse;
use DomainSystem\Core\Security\IdentityManager;
use DomainSystem\Core\Security\SafeRedirectValidator;
use DomainSystem\Core\Security\SessionManager;

/**
 * Motor de Interceptação e Segurança (Catraca do Web OS)
 * Aplica:
 * 1. Princípio do Default-Deny para rotas administrativas (/admin/*).
 * 2. Bloqueio automático de visitantes anônimos e redirecionamento seguro anti-phishing.
 * 3. Barreira inviolável de privilégios impedindo que alunos/usuários acessem o admin.
 * 4. Validação granular de Capabilities (ACL) e Roles.
 */
class AuthMiddleware implements MiddlewareInterface
{
    private SessionManagerInterface|SessionManager $session;
    private IdentityManager $identity;
    private SafeRedirectValidatorInterface $redirectValidator;
    private ?AccountStatusProviderInterface $accountStatus;

    public function __construct(
        SessionManagerInterface|SessionManager $session,
        IdentityManager $identity,
        ?SafeRedirectValidatorInterface $redirectValidator = null,
        ?AccountStatusProviderInterface $accountStatus = null
    ) {
        $this->session = $session;
        $this->identity = $identity;
        $this->redirectValidator = $redirectValidator ?? new SafeRedirectValidator();
        $this->accountStatus = $accountStatus;
    }

    public function handle(Request $request, Closure $next, array $routeConfig = []): mixed
    {
        $uri = strtok((string)$request->uri(), '?') ?: '/';
        $isAdminRoute = str_starts_with($uri, '/admin');

        // Allowlist estrita de rotas públicas de infraestrutura de emergência
        if ($uri === '/admin/emergency') {
            return $next($request);
        }

        // Aceita 'capabilities' ou 'roles' (legado de plugins)
        $capabilities = $routeConfig['capabilities'] ?? $routeConfig['roles'] ?? [];

        // PRINCÍPIO DO DEFAULT-DENY PARA /admin (Blindagem contra erros de desenvolvedores de plugins):
        // Se a rota for administrativa e nenhuma capability foi exigida explicitamente no registro,
        // o sistema assume obrigatoriamente que ela exige privilégios de 'admin' ou 'manager'.
        if ($isAdminRoute && empty($capabilities)) {
            $capabilities = ['admin', 'manager'];
        }

        // Se a rota for pública (sem capabilities exigidas), passa a bola
        if (empty($capabilities)) {
            return $next($request);
        }

        $userId = $this->session->get('user_id');

        // Se não estiver logado
        if (!$userId) {
            if ($isAdminRoute) {
                $isAjax = (!empty($request->server['HTTP_X_REQUESTED_WITH']) && strtolower($request->server['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                    || str_starts_with($uri, '/api/');

                if ($isAjax) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'Autenticação administrativa necessária.',
                    ], 401);
                }

                $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
                $safeTarget = $this->redirectValidator->sanitize($uri, '/admin');
                return Response::redirect($baseUrl . '/login?redirect=' . urlencode($safeTarget));
            }

            return $this->blockAccess('Visitante Anônimo');
        }

        // T12: Se o usuário foi banido ou suspenso, invalida a sessão ativa imediatamente
        if ($this->accountStatus !== null && $this->accountStatus->statusOf((int)$userId) !== 'active') {
            $this->session->destroy();
            $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
            return Response::redirect($baseUrl . '/login');
        }

        // Se estiver logado, valida o papel do usuário
        $userRole = (string)$this->session->get('user_role', '');

        // Barreira estrita: Papéis de nível comum (user, student, subscriber)
        // JAMAIS podem acessar qualquer rota administrativa do painel!
        if ($isAdminRoute && in_array(strtolower($userRole), ['user', 'student', 'subscriber'], true)) {
            $userName = (string)$this->session->get('user_name', 'Usuário ' . $userId);
            return $this->blockAccess($userName, 'Usuários comuns e alunos não possuem permissão de acesso ao ambiente administrativo.');
        }

        $hasAccess = false;
        foreach ($capabilities as $cap) {
            // Verifica se a identity possui a capability exata ou a role autorizada
            if ($this->identity->userCan((int)$userId, $cap) || $this->identity->hasRole((int)$userId, $cap)) {
                $hasAccess = true;
                break;
            }
        }

        if (!$hasAccess) {
            $userName = (string)$this->session->get('user_name', 'Usuário ' . $userId);
            return $this->blockAccess($userName);
        }

        return $next($request);
    }

    private function blockAccess(string $identityName, string $customMessage = ''): Response
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $msg = $customMessage !== ''
            ? $customMessage
            : 'Sua identidade (' . htmlspecialchars($identityName, ENT_QUOTES, 'UTF-8') . ') não possui os privilégios necessários para executar esta ação no sistema.';

        $html = '<div style="padding:40px 20px; text-align:center; font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif; max-width:540px; margin:40px auto; background:#0f141c; color:#f8fafc; border:1px solid rgba(239,68,68,0.3); border-radius:12px; box-shadow:0 12px 32px rgba(0,0,0,0.5);">'
            . '<h2 style="color:#ef4444; margin-bottom:12px; font-size:1.5rem;">Acesso Negado 🛑</h2>'
            . '<p style="color:#94a3b8; font-size:0.95rem; line-height:1.6; margin-bottom:24px;">' . $msg . '</p>'
            . '<div style="display:flex; justify-content:center; gap:12px;">'
            . '<a href="' . ($baseUrl !== '' ? $baseUrl : '/') . '" style="display:inline-block; padding:10px 20px; background:#45f3ff; color:#0b0c10; font-weight:700; text-decoration:none; border-radius:6px;">Página Inicial</a>'
            . '<a href="javascript:history.back()" style="display:inline-block; padding:10px 20px; background:rgba(255,255,255,0.08); color:#cbd5e1; text-decoration:none; border-radius:6px;">Voltar com Segurança</a>'
            . '</div>'
            . '</div>';

        return new Response($html, 403);
    }
}
