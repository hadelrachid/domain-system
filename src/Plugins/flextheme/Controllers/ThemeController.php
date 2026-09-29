<?php

namespace DomainSystem\Plugins\FlexTheme\Controllers;

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

        // Output Buffer para ler o HTML
        ob_start();
        $siteName = $_ENV['SITE_NAME'] ?? 'Domain System';
        require $themePath;
        $html = ob_get_clean();

        // 🚨 O SEO Middleware vai interceptar esta Response e envelopá-la no main.php + Minificar!
        return new Response($html);
    }
}
