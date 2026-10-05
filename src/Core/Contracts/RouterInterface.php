<?php

namespace DomainSystem\Core\Contracts;

use DomainSystem\Core\Http\Request;

interface RouterInterface
{
    /**
     * Registra uma nova rota no sistema.
     */
    public function addRoute(string $method, string $path, callable|array $handler, string $plugin = '', array $capabilities = []): void;

    /**
     * Processa a requisição atual e executa o handler da rota correspondente.
     */
    public function dispatch(Request $request): mixed;
    
    /**
     * Adiciona um middleware global ao roteador.
     */
    public function addGlobalMiddleware(string $middlewareClass): void;
}
