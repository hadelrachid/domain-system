<?php

namespace DomainSystem\Plugins\FlexTheme\Controllers;

use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Tenant\TenantContext;

class ThemeController
{
    private TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        // Injeção de dependência do Tenant (Sabemos qual é a vitrine que o visitante abriu)
        $this->tenantContext = $tenantContext;
    }

    public function renderHome(Request $request): Response
    {
        $tenantId = $this->tenantContext->getTenantId();
        
        // No futuro, isso virá do banco de dados (Qual tema o tenant escolheu?)
        // Por enquanto, hardcoded para 'rachidd' se for o tenant rachidd, senão 'default'.
        $themeName = ($tenantId === 'rachidd') ? 'rachidd' : 'default';
        
        $themePath = __DIR__ . '/../themes/' . $themeName . '/index.php';
        
        if (!file_exists($themePath)) {
            return new Response("Tema não encontrado para o tenant: " . htmlspecialchars($tenantId), 404);
        }

        // Variáveis injetadas na View
        $viewData = [
            'tenantName' => $this->tenantContext->getTenantName(),
            'tenantId' => $tenantId,
        ];

        // Output Buffer para ler o HTML
        ob_start();
        extract($viewData);
        require $themePath;
        $html = ob_get_clean();

        return new Response($html);
    }
}
