<?php

/**
 * Domain-System Front Controller
 */

// =========================================================================
// MODO DE MANUTENÇÃO (hPanel / External Panel Integration)
// =========================================================================
// Verifica a existência do arquivo de lock físico sem invocar o framework.
// Isso permite que plataformas como Hostinger hPanel ativem a manutenção 
// apenas criando este arquivo (padrão da indústria).
$maintenanceFile = dirname(__DIR__) . '/.maintenance';
if (file_exists($maintenanceFile)) {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    // Libera a passagem apenas para rotas de sistema crítico (Admin/Login/API/Setup)
    if (strpos($uri, '/admin') === false && strpos($uri, '/login') === false && strpos($uri, '/api') === false && strpos($uri, '/setup') === false) {
        $maintenancePage = __DIR__ . '/maintenance.php';
        if (file_exists($maintenancePage)) {
            header('Retry-After: 3600');
            http_response_code(503);
            require $maintenancePage;
        } else {
            header('Retry-After: 3600');
            http_response_code(503);
            echo "<h1 style='text-align:center; font-family:sans-serif; margin-top:50px;'>Manutenção do Sistema. Voltamos em breve.</h1>";
        }
        exit; // Interrompe a execução antes de encostar no Banco ou Autoloader
    }
}
// =========================================================================

$app = require_once dirname(__DIR__) . '/bootstrap.php';

// Dispatch a pre-boot event
$app->getDispatcher()->dispatch('kernel_pre_boot');

$earlyRequest = \DomainSystem\Core\Http\Request::capture();

// Boot the Kernel

$app->boot();

// Dispatch a post-boot event
$app->getDispatcher()->dispatch('kernel_post_boot');

// Allow plugins to register their routes
$app->getDispatcher()->dispatch('router.register', $app->getRouter());

// Dispatch the request
try {
    $request = $earlyRequest; // Reutiliza o Request já capturado
    // Suporte para subdiretórios no XAMPP (ex: /domain-system/admin)
    $uri = $request->uri();
    $scriptName = dirname($_SERVER['SCRIPT_NAME']); // ex: /domain-system/public
    $scriptName = str_replace('\\', '/', $scriptName);
    
    // 1. Remove o scriptName exato se acessaram diretamente /public/index.php
    if ($scriptName !== '/' && strpos($uri, $scriptName) === 0) {
        $uri = substr($uri, strlen($scriptName));
    }
    
    // 2. Calcula o BASE_URL e limpa a URI
    $baseFolder = '/' . basename(dirname(__DIR__)); // ex: /domain-system
    $scriptDir = rtrim(dirname($scriptName), '/\\'); // ex: /domain-system ou raiz da hostinger
    
    if ($baseFolder !== '/' && strpos($uri, $baseFolder) === 0) {
        // Acesso local XAMPP: a URL começa com a pasta do projeto
        $uri = substr($uri, strlen($baseFolder));
        if (!defined('BASE_URL')) define('BASE_URL', $baseFolder);
    } else {
        // Acesso em Produção (Hostinger)
        if ($scriptDir !== '' && strpos($_SERVER['REQUEST_URI'], $scriptDir) !== 0) {
            // RewriteRule .htaccess ocultou o public
            if (!defined('BASE_URL')) define('BASE_URL', '');
        } else {
            if (!defined('BASE_URL')) define('BASE_URL', rtrim($scriptName, '/'));
        }
    }
    
    if (empty($uri)) {
        $uri = '/';
    }
    
    // Atualiza o Request com a URI limpa para o Router
    $request->server['REQUEST_URI'] = $uri;
    
    $response = $app->getRouter()->dispatch($request);
    
    // Se o controller retornou string em vez de objeto Response, nós o convertemos automaticamente
    if (!$response instanceof \DomainSystem\Core\Http\Response) {
        if (is_array($response) || is_object($response)) {
            $response = \DomainSystem\Core\Http\Response::json($response);
        } else {
            $response = new \DomainSystem\Core\Http\Response((string)$response);
        }
    }
    
    // Injeção Automática de Layout (Workspace) baseada no Cargo (Role)
    // IMPORTANTE: Rotas de API (/api/) e respostas JSON NÃO devem ser embrulhadas no layout!
    $isApiRoute = str_contains($uri, '/api/');
    $isJsonResponse = $response instanceof \DomainSystem\Core\Http\Response && str_contains($response->getHeader('Content-Type') ?? '', 'application/json');
    
    if (strpos($uri, '/admin') === 0 && !in_array($response->getStatusCode(), [301, 302, 303, 307, 308]) && !$isApiRoute && !$isJsonResponse && !isset($_GET['raw']) && !str_starts_with($uri, '/admin/emergency') && !str_starts_with($uri, '/admin/themes/preview') && !str_starts_with($uri, '/admin/ai-hub/test') && !str_starts_with($uri, '/admin/terminal/execute')) {
        $session = $app->getContainer()->make(\DomainSystem\Core\Http\SessionManager::class);
        $role = $session->get('user_role', 'admin');
        $workspace = $app->getWorkspaceManager()->getWorkspace($role);
        // O Workspace envolve a string HTML de dentro do Response
        $wrappedContent = $workspace->wrap($response->getContent());
        $response->setContent($wrappedContent);
    }

    $response->send();
    
} catch (Exception $e) {
    if ($e->getCode() == 404) {
        $errorResponse = new \DomainSystem\Core\Http\Response("404 Not Found: " . $e->getMessage(), 404);
        $errorResponse->send();
    } else {
        // Re-joga a exceção para que o ErrorHandler oficial capture e crie a tela bonita
        throw $e;
    }
}
