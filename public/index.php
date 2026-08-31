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
    $request = \DomainSystem\Core\Http\Request::capture();
    
    // Suporte para subdiretórios no XAMPP (ex: /domain-system/admin)
    $uri = $request->uri();
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
        if (!defined('BASE_URL')) define('BASE_URL', $baseFolder);
    } else {
        if (!defined('BASE_URL')) define('BASE_URL', rtrim($scriptName, '/'));
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
    if (strpos($uri, '/admin') === 0 && !isset($_GET['raw']) && !str_starts_with($uri, '/admin/emergency')) {
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
