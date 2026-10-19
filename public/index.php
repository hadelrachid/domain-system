<?php

/**
 * Domain-System Front Controller
 */

// =========================================================================
// RESOLUÇÃO 100% AGNÓSTICA DE BASE_URL E ROTA (Zero Acoplamento a Servidor)
// =========================================================================
$resolveAgnosticPath = function(?string $explicitRequestUri = null): array {
    $rawUri = $explicitRequestUri ?? ($_SERVER['REQUEST_URI'] ?? '/');
    $path = parse_url($rawUri, PHP_URL_PATH);
    $path = is_string($path) && $path !== '' ? str_replace('\\', '/', $path) : '/';

    // 1. Respeita sobreposição explícita configurada no .env (BASE_URL ou APP_URL)
    $envBase = getenv('BASE_URL') ?: (getenv('APP_URL') ? parse_url(getenv('APP_URL'), PHP_URL_PATH) : null);
    if ($envBase !== null && $envBase !== false) {
        $baseUrl = rtrim((string)$envBase, '/');
        $route = $path;
        if ($baseUrl !== '' && str_starts_with($route, $baseUrl)) {
            $route = substr($route, strlen($baseUrl));
        }
        $route = '/' . ltrim($route, '/');
        return [$baseUrl, $route];
    }

    // 2. Resolução matemática agnóstica entre SCRIPT_NAME e REQUEST_URI
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $scriptDir = rtrim($scriptDir, '/'); // Ex: "/domain-system/public", "/public", ou ""

    if (str_ends_with($scriptDir, '/public') && !str_starts_with($path, $scriptDir)) {
        // Rewrite do servidor ou .htaccess ocultou o /public na requisição web
        $baseUrl = substr($scriptDir, 0, -strlen('/public'));
    } else {
        $baseUrl = $scriptDir;
    }

    $baseUrl = rtrim($baseUrl, '/');
    $route = $path;

    if ($baseUrl !== '' && str_starts_with($route, $baseUrl)) {
        $route = substr($route, strlen($baseUrl));
    }

    // Se o acesso foi feito diretamente com /public no caminho
    if (str_starts_with($route, '/public/')) {
        $route = substr($route, strlen('/public'));
    } elseif ($route === '/public') {
        $route = '/';
    }

    $route = '/' . ltrim($route, '/');
    return [$baseUrl, $route];
};

// =========================================================================
// MODO DE MANUTENÇÃO (Lock Físico sem invocar o Kernel)
// =========================================================================
$maintenanceFile = dirname(__DIR__) . '/.maintenance';
if (file_exists($maintenanceFile)) {
    [$earlyBase, $cleanPath] = $resolveAgnosticPath();

    // Libera a passagem apenas para rotas de sistema crítico (Admin/Login/Logout/API/Setup)
    if (!preg_match('#^/(admin|login|logout|api|setup)(/|$)#', $cleanPath)) {
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
        exit;
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
    $request = $earlyRequest;
    
    // Normalização 100% agnóstica de BASE_URL e Rota
    [$baseUrl, $routeUri] = $resolveAgnosticPath($request->uri());
    
    if (!defined('BASE_URL')) {
        define('BASE_URL', $baseUrl);
    }
    
    // Atualiza o Request com a URI limpa para o Router
    $request->server['REQUEST_URI'] = $routeUri;

    $httpKernel = new \DomainSystem\Core\Http\HttpKernel(
        $app->getRouter(),
        $app->getContainer(),
        $app->getWorkspaceManager()
    );
    $response = $httpKernel->handle($request, file_exists($maintenanceFile), __DIR__ . '/maintenance.php');
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
