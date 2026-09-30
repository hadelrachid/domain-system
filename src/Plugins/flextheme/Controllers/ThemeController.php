<?php

namespace DomainSystem\Plugins\flextheme\Controllers;

use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;

class ThemeController
{
    public function renderHome(Request $request): Response
    {
        // Em arquitetura Single-Tenant, o tema pode vir de um arquivo config.json ou env.
        $themeName = $_ENV['ACTIVE_THEME'] ?? 'default';
        
        $themeDir = __DIR__ . '/../themes/' . $themeName;
        $themePath = file_exists($themeDir . '/templates/home.php') 
            ? $themeDir . '/templates/home.php' 
            : $themeDir . '/index.php';
        
        if (!file_exists($themePath)) {
            return new Response("Tema não encontrado: " . htmlspecialchars($themeName), 404);
        }

        ob_start();
        require $themePath;
        $html = ob_get_clean();

        // O SEO Middleware vai interceptar esta Response e envelopá-la no main.php + Minificar!
        return new Response($html);
    }

    public function renderDoc(Request $request, string $slug): Response
    {
        $themeName = $_ENV['ACTIVE_THEME'] ?? 'default';
        $themeDir = __DIR__ . '/../themes/' . $themeName;
        
        // Evitar directory traversal hacker
        if (strpos($slug, '..') !== false) {
            return new Response("Acesso negado", 403);
        }
        
        $docPath = $themeDir . '/templates/docs/' . $slug . '.php';

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
