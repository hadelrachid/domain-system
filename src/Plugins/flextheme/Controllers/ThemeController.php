<?php

namespace DomainSystem\Plugins\flextheme\Controllers;

use DomainSystem\Core\Contracts\SiteStateInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Theme\EnvSiteState;
use DomainSystem\Core\Theme\Parts\Contracts\PublicUrlResolverInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartRendererInterface;
use DomainSystem\Core\Theme\Parts\Support\BaseUrlResolver;
use DomainSystem\Core\Theme\SafeThemeResolver;
use DomainSystem\Core\Theme\ShortcodeManager;

class ThemeController
{
    private SiteStateInterface $siteState;
    private ?ThemePartRendererInterface $partRenderer;
    private ?ShortcodeManager $shortcodes;
    private PublicUrlResolverInterface $urlResolver;
    private string $baseRoot;

    public function __construct(
        ?SiteStateInterface $siteState = null,
        ?ThemePartRendererInterface $partRenderer = null,
        ?ShortcodeManager $shortcodes = null,
        ?PublicUrlResolverInterface $urlResolver = null,
        ?string $baseRoot = null
    ) {
        $this->siteState = $siteState ?? new EnvSiteState();
        $this->partRenderer = $partRenderer;
        $this->shortcodes = $shortcodes;
        $this->urlResolver = $urlResolver ?? new BaseUrlResolver();
        $this->baseRoot = $baseRoot ?? (defined('DOMAIN_SYSTEM_ROOT') ? DOMAIN_SYSTEM_ROOT : dirname(__DIR__, 4));
    }

    public function renderHome(Request $request): Response
    {
        $siteState = $this->siteState;
        $resolver = new SafeThemeResolver([
            $this->baseRoot . '/themes',
            __DIR__ . '/../themes'
        ]);

        $previewTheme = $request->input('preview_theme');
        if (!empty($previewTheme) && $resolver->themeExists($previewTheme)) {
            $themeDir = $resolver->resolveThemePath($previewTheme);
            $themePath = file_exists($themeDir . '/templates/home.php') 
                ? $themeDir . '/templates/home.php' 
                : $themeDir . '/index.php';
            
            if (file_exists($themePath)) {
                ob_start();
                $baseUrl = $this->urlResolver->getBaseUrl();
                $siteName = 'Domain-System OS';
                require $themePath;
                $html = (string)ob_get_clean();
                if ($this->shortcodes !== null) {
                    $html = $this->shortcodes->parse($html);
                }
                return new Response($html);
            }
        }

        // 1. Estado: Em Breve (Coming Soon)
        if ($siteState->isComingSoon()) {
            $response = new Response($this->renderComingSoonPage(), 200, ['Content-Type' => 'text/html; charset=utf-8']);
            $response->setHeader('X-Robots-Tag', 'noindex, nofollow');
            return $response;
        }

        // 2. Estado: Manutenção (Maintenance)
        if ($siteState->isMaintenance()) {
            $response = new Response($this->renderMaintenancePage(), 503, ['Content-Type' => 'text/html; charset=utf-8']);
            $response->setHeader('Retry-After', '3600');
            return $response;
        }

        // 3. Estado: Live
        $themeName = $siteState->getActiveTheme();
        $themeDir = $resolver->resolveThemePath($themeName);

        $partRenderer = $this->partRenderer;
        $assignedSection = ($partRenderer !== null) ? $partRenderer->renderTemplateSection($themeName, 'home') : '';

        $themePath = file_exists($themeDir . '/templates/home.php') 
            ? $themeDir . '/templates/home.php' 
            : $themeDir . '/index.php';
        
        if (!file_exists($themePath)) {
            if (trim($assignedSection) !== '') {
                return new Response($assignedSection);
            }
            return new Response("Tema não encontrado em: " . htmlspecialchars($themeDir), 404);
        }

        ob_start();
        $baseUrl = $this->urlResolver->getBaseUrl();
        $siteName = 'Domain-System OS';
        require $themePath;
        $html = ob_get_clean();

        // Se uma seção customizada foi atribuída ao template home, concatena abaixo se não tiver sido impressa internamente
        if (trim($assignedSection) !== '' && !str_contains($html, $assignedSection)) {
            $html .= "\n" . $assignedSection;
        }

        // Executa o processador de Shortcodes para que qualquer [section id="..."] na home funcione nativamente
        if ($this->shortcodes !== null) {
            $html = $this->shortcodes->parse($html);
        }

        // O SEO Middleware vai interceptar esta Response e envelopá-la no main.php + Minificar!
        return new Response($html);
    }

    private function renderComingSoonPage(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Em Breve - Novo Site</title>
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; background: #0f172a; color: #f8fafc; text-align: center; }
        .card { max-width: 500px; padding: 2.5rem; background: #1e293b; border-radius: 1rem; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.3); border: 1px solid #334155; margin: 1rem; }
        h1 { margin-top: 0; color: #38bdf8; font-size: 2rem; }
        p { color: #94a3b8; font-size: 1.1rem; line-height: 1.6; }
        .badge { display: inline-block; padding: 0.35rem 0.75rem; background: #0369a1; color: #e0f2fe; border-radius: 9999px; font-size: 0.85rem; font-weight: 600; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="card">
        <span class="badge">Site em Preparação</span>
        <h1>Em Breve na Web</h1>
        <p>Este domínio foi configurado com sucesso e uma nova experiência está sendo preparada para publicação.</p>
        <p><small style="color: #64748b;">Administradores podem gerenciar e ativar o site oficial através do painel /admin.</small></p>
    </div>
</body>
</html>
HTML;
    }

    private function renderMaintenancePage(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"><title>Manutenção</title>
    <style>body { font-family: sans-serif; text-align: center; padding: 50px; background: #f8fafc; color: #334155; }</style>
</head>
<body>
    <h1>Estamos em Manutenção Programada</h1>
    <p>Retornaremos em instantes. Por favor tente novamente mais tarde.</p>
</body>
</html>
HTML;
    }

    public function renderDoc(Request $request, string $slug): Response
    {
        $siteState = $this->siteState;
        $themeName = $siteState->getActiveTheme();
        $baseRoot = $this->baseRoot;
        $resolver = new SafeThemeResolver([
            $baseRoot . '/themes',
            __DIR__ . '/../themes'
        ]);
        $themeDir = $resolver->resolveThemePath($themeName);
        
        // Evitar directory traversal hacker
        if (strpos($slug, '..') !== false) {
            return new Response("Acesso negado", 403);
        }
        
        $docPath = $themeDir . '/pages/' . $slug . '.php';

        if (!file_exists($docPath)) {
            return new Response("Documentação não encontrada: " . htmlspecialchars($slug), 404);
        }

        ob_start();
        require $docPath;
        $innerHtml = ob_get_clean();

        // Envelopa o conteúdo para isolar o CSS e consertar o visual anos 90
        $html = <<<HTML
<style>
.docs-wrapper {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    line-height: 1.6;
    color: #cbd5e1;
    max-width: 900px;
    margin: 40px auto;
}
.docs-wrapper header {
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    padding-bottom: 20px;
    margin-bottom: 30px;
    text-align: center;
    position: relative;
}
.docs-wrapper header h1 {
    color: #60a5fa;
    margin: 0;
    font-weight: 300;
    font-size: 2rem;
}
.docs-wrapper .logo-docs {
    max-width: 180px;
    margin: 0 auto 15px auto;
    display: block;
    filter: drop-shadow(0 0 15px rgba(59, 130, 246, 0.4));
}
.docs-wrapper .content {
    background: rgba(30, 41, 59, 0.6);
    backdrop-filter: blur(12px);
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    border: 1px solid rgba(255, 255, 255, 0.05);
}
.docs-wrapper nav {
    margin-bottom: 30px;
    background: rgba(30, 41, 59, 0.6);
    backdrop-filter: blur(12px);
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5);
    border: 1px solid rgba(255, 255, 255, 0.05);
}
.docs-wrapper nav ul {
    list-style: none; padding: 0; margin: 0; display: flex; gap: 15px; flex-wrap: wrap; justify-content: center;
}
.docs-wrapper nav ul li a { color: #60a5fa; font-weight: 600; text-transform: uppercase; font-size: 0.85em; }
.docs-wrapper pre {
    background: rgba(15, 23, 42, 0.8);
    color: #f8f8f2;
    padding: 20px;
    border-radius: 8px;
    overflow-x: auto;
    border: 1px solid rgba(255, 255, 255, 0.05);
}
.docs-wrapper .diagram {
    background: #000;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #1f2937;
    color: #10b981;
    font-family: 'Consolas', 'Monaco', monospace;
    white-space: pre;
    overflow-x: auto;
    margin: 20px 0;
    font-size: 0.9rem;
    line-height: 1.4;
}
.docs-wrapper code {
    background: rgba(15, 23, 42, 0.8);
    padding: 3px 6px;
    border-radius: 4px;
    font-family: Consolas, monospace;
    color: #fca5a5;
    font-size: 0.9em;
}
.docs-wrapper pre code {
    background: transparent;
    color: #60a5fa;
    padding: 0;
}
.docs-wrapper a { color: #3b82f6; text-decoration: none; }
.docs-wrapper a:hover { color: #60a5fa; text-shadow: 0 0 5px rgba(59, 130, 246, 0.3); }
.docs-wrapper footer { margin-top: 40px; text-align: center; color: #64748b; font-size: 0.9em; }
</style>
<div class="docs-wrapper">
    {$innerHtml}
</div>
HTML;

        // O SEO Middleware fará o wrap em main.php
        return new Response($html);
    }
}
