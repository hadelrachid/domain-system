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

    public function addRoute(string $method, string $path, callable|array $handler, string $plugin = ''): void
    {
        $method = strtoupper($method);
        $this->routes[$method][$path] = [
            'handler' => $handler,
            'plugin' => $plugin
        ];
    }

    public function dispatch(string $method, string $uri): mixed
    {
        $method = strtoupper($method);
        // Remove query string
        $uri = strtok($uri, '?');

        if (!isset($this->routes[$method])) {
            throw new Exception("Rota não encontrada: $uri", 404);
        }

        // Busca rota exata
        if (isset($this->routes[$method][$uri])) {
            return $this->executeHandler($this->routes[$method][$uri]['handler']);
        }

        // Busca rota com parâmetros (ex: /pacientes/{id})
        foreach ($this->routes[$method] as $route => $config) {
            $pattern = preg_replace('/\{[a-zA-Z_]+\}/', '([^/]+)', $route);
            if (preg_match('#^' . $pattern . '$#', $uri, $matches)) {
                array_shift($matches);
                return $this->executeHandler($config['handler'], $matches);
            }
        }

        throw new Exception("Rota não encontrada: $uri", 404);
    }

    private function executeHandler(callable|array $handler, array $params = []): mixed
    {
        if (is_callable($handler)) {
            return call_user_func_array($handler, $params);
        }

        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            $instance = $this->container->make($class);
            return call_user_func_array([$instance, $method], $params);
        }

        throw new Exception("Handler inválido.");
    }
}
