<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\seo\Middlewares;

use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Plugins\seo\Contracts\HtmlOptimizerInterface;
use DomainSystem\Plugins\seo\Contracts\SeoManagerInterface;

class SeoMiddleware
{
    private HtmlOptimizerInterface $htmlOptimizer;
    private SeoManagerInterface $seo;

    public function __construct(HtmlOptimizerInterface $htmlOptimizer, SeoManagerInterface $seo)
    {
        $this->htmlOptimizer = $htmlOptimizer;
        $this->seo = $seo;
    }

    public function handle(Request $request, callable $next, array $routeConfig)
    {
        // 1. Executa a rota (Controller)
        $response = $next($request);

        // Se for JsonResponse, rota admin, API, webhook ou se a resposta contiver cabeçalho JSON, ignora minificação/envelopamento
        $uri = strtok($request->uri(), '?') ?: '/';
        $contentType = strtolower((string)($response instanceof Response ? $response->getHeader('Content-Type') : ''));
        if (
            $response instanceof \DomainSystem\Core\Http\Responses\JsonResponse ||
            strpos($uri, '/admin') === 0 ||
            strpos($uri, '/api') === 0 ||
            strpos($uri, '/webhook') === 0 ||
            strpos($uri, '/checkout/process') === 0 ||
            str_contains($contentType, 'application/json')
        ) {
            return $response;
        }

        // Se retornou uma Response, aplica a mágica
        if ($response instanceof Response) {
            $content = $response->getContent();
            
            // 2. Verifica se o HTML retornado é apenas um fragmento (não tem tag <html>)
            if (stripos($content, '<html') === false && !empty(trim($content))) {
                
                $siteState = new \DomainSystem\Core\Theme\EnvSiteState();
                $themeName = $siteState->getActiveTheme();
                $baseRoot = dirname(__DIR__, 4);
                $resolver = new \DomainSystem\Core\Theme\SafeThemeResolver([
                    $baseRoot . '/themes',
                    $baseRoot . '/src/Plugins/flextheme/themes'
                ]);
                $themeDir = $resolver->resolveThemePath($themeName);
                
                $mainLayout = file_exists($themeDir . '/layouts/main.php')
                    ? $themeDir . '/layouts/main.php'
                    : ($baseRoot . '/src/Plugins/flextheme/themes/default/layouts/main.php');

                if (file_exists($mainLayout)) {
                    $seo = $this->seo;
                    // Envelopa o fragmento dentro do layout
                    ob_start();
                    require $mainLayout;
                    $content = ob_get_clean();
                }
            }

            // 3. Minifica tudo (Motor SEO)
            $optimizedHtml = $this->htmlOptimizer->compressHtml($content);
            $response->setContent($optimizedHtml);
        }

        return $response;
    }
}
