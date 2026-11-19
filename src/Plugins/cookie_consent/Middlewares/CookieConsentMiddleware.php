<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\cookie_consent\Middlewares;

use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Http\Responses\JsonResponse;
use DomainSystem\Plugins\cookie_consent\Services\CookieBannerRenderer;

/**
 * Middleware que injeta automaticamente o Banner de Cookies e o Google Consent Mode v2 nas páginas públicas.
 */
class CookieConsentMiddleware
{
    private CookieBannerRenderer $renderer;

    public function __construct(?CookieBannerRenderer $renderer = null)
    {
        $this->renderer = $renderer ?? new CookieBannerRenderer();
    }

    public function handle(Request $request, callable $next, array $routeConfig = [])
    {
        $response = $next($request);

        $uri = strtok($request->uri(), '?') ?: '/';
        $contentType = strtolower((string)($response instanceof Response ? $response->getHeader('Content-Type') : ''));

        // Ignora rotas administrativas, APIs, webhooks, pagamentos e respostas puramente JSON
        if (
            $response instanceof JsonResponse ||
            str_starts_with($uri, '/admin') ||
            str_starts_with($uri, '/api') ||
            str_starts_with($uri, '/webhook') ||
            str_starts_with($uri, '/checkout/process') ||
            str_contains($contentType, 'application/json')
        ) {
            return $response;
        }

        if ($response instanceof Response) {
            $content = $response->getContent();
            if (empty(trim($content))) {
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
