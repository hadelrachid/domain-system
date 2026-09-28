<?php

namespace DomainSystem\Core\Contracts;

use DomainSystem\Core\Http\Request;
use Closure;

/**
 * ────────────────────────────────────────────────────────────────────────────
 * INTERFACE: MiddlewareInterface
 * ────────────────────────────────────────────────────────────────────────────
 * Define o contrato para os "Seguranças da Porta". Qualquer classe que queira
 * interceptar uma requisição antes dela chegar ao Controller deve assinar
 * e cumprir este contrato.
 */
interface MiddlewareInterface
{
    /**
     * @param Request $request Os dados da requisição atual.
     * @param Closure $next O próximo Middleware da fila (ou o destino final).
     * @param array $routeConfig Configurações da rota (ex: roles exigidas).
     * @return mixed A resposta final a ser enviada ao navegador.
     */
    public function handle(Request $request, Closure $next, array $routeConfig = []): mixed;
}
