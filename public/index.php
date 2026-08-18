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
    }
    
    if (empty($uri)) {
        $uri = '/';
    }
    
    $response = $app->getRouter()->dispatch($method, $uri);
    
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
        http_response_code(500);
        echo "500 Internal Server Error: " . $e->getMessage();
    }
}
