<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\cookie_consent\Middlewares;

use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Http\Responses\JsonResponse;
use DomainSystem\Plugins\cookie_consent\Services\CookieBannerRenderer;

/**
 * Middleware que injeta automaticamente o Banner de Cookies e o Google Consent Mode v2 exclusivamente nas páginas públicas.
 * Garante isolamento estrito contra a tela de login do admin (/login), cockpits e áreas administrativas internas.
 */
class CookieConsentMiddleware
{
    private CookieBannerRenderer $renderer;
    private ?SessionManagerInterface $session;

    public function __construct(?CookieBannerRenderer $renderer = null, ?SessionManagerInterface $session = null)
    {
        $this->renderer = $renderer ?? new CookieBannerRenderer();
        $this->session = $session;
    }

    public function handle(Request $request, callable $next, array $routeConfig = [])
    {
        $response = $next($request);

        $uri = strtok($request->uri(), '?') ?: '/';
        $normalizedUri = '/' . trim($uri, '/');
        $contentType = strtolower((string)($response instanceof Response ? $response->getHeader('Content-Type') : ''));

        // 1. Ignora respostas técnicas JSON
        if ($response instanceof JsonResponse || str_contains($contentType, 'application/json')) {
            return $response;
        }

        // 2. Ignora tela de login do admin, logout, painel administrativo, cockpits, APIs e webhooks
        if (
            $normalizedUri === '/login' ||
            $normalizedUri === '/logout' ||
            str_starts_with($normalizedUri, '/admin') ||
            str_starts_with($normalizedUri, '/cockpit') ||
            str_starts_with($normalizedUri, '/installer') ||
            str_starts_with($normalizedUri, '/api') ||
            str_starts_with($normalizedUri, '/webhook') ||
            str_starts_with($normalizedUri, '/checkout/process')
        ) {
            return $response;
        }

        // 3. Ignora se o usuário ativo for um administrador/gestor autenticado no sistema
        if ($this->session !== null) {
            $userRole = (string)$this->session->get('user_role', '');
            if ($this->session->has('user_id') && in_array($userRole, ['admin', 'manager', 'editor', 'support'], true)) {
                return $response;
            }
        }

        // 4. Injeta apenas em páginas HTML públicas válidas
        if ($response instanceof Response) {
            $content = $response->getContent();
            if (empty(trim($content))) {
                return $response;
            }

            // Trava de segurança extra: não injeta caso a view renderizada seja o formulário do Cockpit/Admin
            if (stripos($content, 'Cockpit - Autenticação') !== false || stripos($content, "class='login-box'") !== false || stripos($content, 'class="login-box"') !== false) {
                return $response;
            }

            $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
            $bannerHtml = $this->renderer->render($baseUrl);

            if (stripos($content, '</body>') !== false) {
                // Injeta imediatamente antes de </body>
                $content = str_ireplace('</body>', $bannerHtml . "\n</body>", $content);
                $response->setContent($content);
            } elseif (stripos($content, '</html>') !== false) {
                $content = str_ireplace('</html>', $bannerHtml . "\n</html>", $content);
                $response->setContent($content);
            }
        }

        return $response;
    }
}
