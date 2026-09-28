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
        
        $themePath = __DIR__ . '/../themes/' . $themeName . '/index.php';
        
        if (!file_exists($themePath)) {
            return new Response("Tema não encontrado: " . htmlspecialchars($themeName), 404);
        }

        // Variáveis injetadas na View
        $viewData = [
            'siteName' => $_ENV['SITE_NAME'] ?? 'Domain System',
        ];

        // Output Buffer para ler o HTML
        ob_start();
        extract($viewData);
        require $themePath;
        $html = ob_get_clean();

        return new Response($html);
    }
}
