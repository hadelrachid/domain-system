<?php

namespace DomainSystem\Core\Routing;

use DomainSystem\Core\Container\Container;
use Exception;

class Router
{
    private array $routes = [];
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function addRoute(string $method, string $path, callable|array $handler, string $plugin = '', array $roles = []): void
    {
        $method = strtoupper($method);
        $this->routes[$method][$path] = [
            'handler' => $handler,
            'plugin' => $plugin,
            'roles' => $roles
        ];
    }

    public function dispatch(\DomainSystem\Core\Http\Request $request): mixed
    {
        $method = strtoupper($request->method());
        // Remove query string
        $uri = strtok($request->uri(), '?');

        // Dispara o middleware via EventDispatcher (se estiver no Container)
        if ($this->container->has(\DomainSystem\Core\Events\EventDispatcher::class)) {
            $dispatcher = $this->container->make(\DomainSystem\Core\Events\EventDispatcher::class);
            $dispatcher->dispatch('router.before_dispatch', $uri);
        }

        if (!isset($this->routes[$method])) {
            throw new Exception("Rota não encontrada: $uri", 404);
        }

        // Busca rota exata
        if (isset($this->routes[$method][$uri])) {
            $this->checkAuthorization($this->routes[$method][$uri]['roles']);
            return $this->executeHandler($this->routes[$method][$uri]['handler'], [], $request);
        }

        // Busca rota com parâmetros (ex: /pacientes/{id})
        foreach ($this->routes[$method] as $route => $config) {
            $pattern = preg_replace('/\{[a-zA-Z_]+\}/', '([^/]+)', $route);
            if (preg_match('#^' . $pattern . '$#', $uri, $matches)) {
                array_shift($matches);
                $this->checkAuthorization($config['roles']);
                return $this->executeHandler($config['handler'], $matches, $request);
            }
        }

        throw new Exception("Rota não encontrada: $uri", 404);
    }

    private function checkAuthorization(array $roles): void
    {
        if (empty($roles)) {
            return; // Rota pública ou sem restrição declarada
        }

        if (session_status() === PHP_SESSION_NONE) { 
            session_start(); 
        }

        $userRole = $_SESSION['user_role'] ?? '';

        if (!in_array($userRole, $roles)) {
            http_response_code(403);
            die('<div style="padding:20px; text-align:center; font-family:sans-serif;">
                <h2 style="color:#d63638;">Acesso Negado 🛑</h2>
                <p>O seu perfil ('.htmlspecialchars($userRole).') não tem permissão para acessar esta área.</p>
                <a href="javascript:history.back()">Voltar</a>
            </div>');
        }
    }

    private function executeHandler(callable|array $handler, array $params = [], \DomainSystem\Core\Http\Request $request = null): mixed
    {
        if (is_callable($handler)) {
            // Check if closure expects Request
            $reflection = new \ReflectionFunction($handler);
            return $this->invokeReflection($reflection, $handler, null, $params, $request);
        }

        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            $instance = $this->container->make($class);
            $reflection = new \ReflectionMethod($class, $method);
            return $this->invokeReflection($reflection, $method, $instance, $params, $request);
        }

        throw new Exception("Handler inválido.");
    }
    
    private function invokeReflection(\ReflectionFunctionAbstract $reflection, string|callable $methodOrClosure, ?object $instance, array $params, ?\DomainSystem\Core\Http\Request $request)
    {
        $dependencies = [];
        $paramIndex = 0;
        foreach ($reflection->getParameters() as $param) {
            $type = $param->getType();
            if ($type && $type->getName() === \DomainSystem\Core\Http\Request::class) {
                $dependencies[] = $request;
            } else {
                if (isset($params[$paramIndex])) {
                    $dependencies[] = $params[$paramIndex];
                    $paramIndex++;
                } else {
                    $dependencies[] = null;
                }
            }
        }
        
        if ($instance !== null) {
            return $reflection->invokeArgs($instance, $dependencies);
        }
        return $reflection->invokeArgs($dependencies);
    }
}
