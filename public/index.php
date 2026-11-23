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

// Normalização 100% agnóstica de BASE_URL e Rota (Garante BASE_URL disponível antes de qualquer serviço ou plugin carregar)
[$baseUrl, $routeUri] = $resolveAgnosticPath($earlyRequest->uri());
if (!defined('BASE_URL')) {
    define('BASE_URL', $baseUrl);
}
$earlyRequest->server['REQUEST_URI'] = $routeUri;

// Boot the Kernel
$app->boot();

// Dispatch a post-boot event
$app->getDispatcher()->dispatch('kernel_post_boot');

// Allow plugins to register their routes
$app->getDispatcher()->dispatch('router.register', $app->getRouter());

// Dispatch the request
try {
    $request = $earlyRequest;

    $httpKernel = new \DomainSystem\Core\Http\HttpKernel(
        $app->getRouter(),
        $app->getContainer(),
        $app->getWorkspaceManager()
    );
    $response = $httpKernel->handle($request, file_exists($maintenanceFile), __DIR__ . '/maintenance.php');
    $response->send();
    
} catch (Exception $e) {
    if ($e->getCode() == 404) {
        $acceptHeader = (string)($_SERVER['HTTP_ACCEPT'] ?? '');
        $remoteIp = (string)($_SERVER['REMOTE_ADDR'] ?? '');
        $isLocalhost = in_array($remoteIp, ['127.0.0.1', '::1'], true);
        $isAdminSession = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
        $isAdminPath = str_starts_with($routeUri ?? '', '/admin');
        $canShowSafeMode = $isAdminPath && ($isAdminSession || $isLocalhost);

        if (str_contains($acceptHeader, 'application/json')) {
            $payload = [
                'error'   => 'Not Found',
                'message' => $e->getMessage(),
            ];
            if ($canShowSafeMode) {
                $payload['safe_mode_url'] = (defined('BASE_URL') ? rtrim(BASE_URL, '/') : '') . '/admin/emergency';
            }
            $errorResponse = new \DomainSystem\Core\Http\Responses\JsonResponse($payload, 404);
            $errorResponse->send();
        } else {
            $safeBase = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
            $homeUrl = htmlspecialchars($safeBase !== '' ? $safeBase . '/' : '/', ENT_QUOTES, 'UTF-8');
            $errMsg = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');

            $adminActionsHtml = '';
            if ($canShowSafeMode) {
                $safeModeUrl = htmlspecialchars($safeBase . '/admin/emergency', ENT_QUOTES, 'UTF-8');
                $adminUrl = htmlspecialchars($safeBase . '/admin', ENT_QUOTES, 'UTF-8');
                $adminActionsHtml = <<<ACTIONS
            <a href="{$safeModeUrl}" class="btn btn-safe">🛡️ Acessar no Modo Seguro</a>
            <a href="{$adminUrl}" class="btn btn-admin">⚙️ Painel Admin</a>
ACTIONS;
            }

            $html404 = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 Not Found — Rota não encontrada</title>
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0b0f17; color: #e2e8f0; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; box-sizing: border-box; }
        .card { max-width: 540px; width: 100%; background: #151b26; border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 32px; box-shadow: 0 20px 50px rgba(0,0,0,0.5); text-align: center; }
        .badge { display: inline-block; background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.35); padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 700; letter-spacing: 0.5px; margin-bottom: 14px; }
        h1 { margin: 0 0 10px 0; font-size: 24px; color: #fff; }
        p { margin: 0 0 24px 0; color: #94a3b8; font-size: 14px; line-height: 1.6; word-break: break-word; }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 11px 18px; border-radius: 8px; font-size: 13.5px; font-weight: 700; text-decoration: none; transition: opacity 0.2s; }
        .btn:hover { opacity: 0.9; }
        .btn-safe { background: linear-gradient(135deg, #f59e0b, #d97706); color: #0b0f17; }
        .btn-admin { background: rgba(69,243,255,0.12); border: 1px solid rgba(69,243,255,0.35); color: #45f3ff; }
        .btn-home { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.15); color: #cbd5e1; }
    </style>
</head>
<body>
    <div class="card">
        <div class="badge">404 NOT FOUND</div>
        <h1>Rota não encontrada</h1>
        <p>{$errMsg}</p>
        <div class="actions">
{$adminActionsHtml}
            <a href="{$homeUrl}" class="btn btn-home">← Voltar ao Início</a>
        </div>
    </div>
</body>
</html>
HTML;
            $errorResponse = new \DomainSystem\Core\Http\Response($html404, 404, ['Content-Type' => 'text/html; charset=utf-8']);
            $errorResponse->send();
        }
    } else {
        // Re-joga a exceção para que o ErrorHandler oficial capture e crie a tela bonita
        throw $e;
    }
}
