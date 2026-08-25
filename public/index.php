<?php

/**
 * Domain-System Front Controller
 */

$app = require_once dirname(__DIR__) . '/bootstrap.php';

// Dispatch a pre-boot event
$app->getDispatcher()->dispatch('kernel_pre_boot');

// Boot the Kernel
$app->boot();

// Dispatch a post-boot event
$app->getDispatcher()->dispatch('kernel_post_boot');

// Allow plugins to register their routes
$app->getDispatcher()->dispatch('router.register', $app->getRouter());

// Dispatch the request
try {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    
    // Suporte para subdiretórios no XAMPP (ex: /domain-system/admin)
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $scriptName = dirname($_SERVER['SCRIPT_NAME']); // ex: /domain-system/public
    $scriptName = str_replace('\\', '/', $scriptName);
    
    // Remove o scriptName da URI se existir
    if ($scriptName !== '/' && strpos($uri, $scriptName) === 0) {
        $uri = substr($uri, strlen($scriptName));
    }
    // Caso tenham acessado /domain-system/admin diretamente pela regra raiz
    $baseFolder = '/' . basename(dirname(__DIR__)); // ex: /domain-system
    if ($baseFolder !== '/' && strpos($uri, $baseFolder) === 0) {
        $uri = substr($uri, strlen($baseFolder));
        define('BASE_URL', $baseFolder);
    } else {
        define('BASE_URL', rtrim($scriptName, '/'));
    }
    
    if (empty($uri)) {
        $uri = '/';
    }
    
    $response = $app->getRouter()->dispatch($method, $uri);
    
    // Injeção Automática de Layout (Workspace) baseada no Cargo (Role)
    if (is_string($response) && strpos($uri, '/admin') === 0 && !isset($_GET['raw'])) {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        $role = $_SESSION['user_role'] ?? 'admin';
        $workspace = $app->getWorkspaceManager()->getWorkspace($role);
        $response = $workspace->wrap($response);
    }

    if (is_array($response) || is_object($response)) {
        header('Content-Type: application/json');
        echo json_encode($response);
    } else {
        echo $response;
    }
} catch (Exception $e) {
    if ($e->getCode() == 404) {
        http_response_code(404);
        echo "404 Not Found: " . $e->getMessage();
    } else {
        // Re-joga a exceção para que o ErrorHandler oficial capture e crie a tela bonita
        throw $e;
    }
}
