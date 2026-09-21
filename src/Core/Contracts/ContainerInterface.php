<?php

namespace DomainSystem\Core\Contracts;

/**
 * Interface para o Container de Injeção de Dependências.
 * Compatível com os conceitos do PSR-11.
 */
interface ContainerInterface
{
    /**
     * Registra uma dependência que será criada a cada chamada.
     */
    public function bind(string $abstract, callable|string $concrete): void;

    /**
     * Registra uma dependência como Singleton (mesma instância sempre).
     */
    public function singleton(string $abstract, callable|string $concrete): void;

    /**
     * Resolve e retorna a instância da dependência solicitada.
     */
    public function make(string $abstract): mixed;

    /**
     * Verifica se o container consegue resolver a dependência solicitada.
     */
    public function has(string $abstract): bool;
}
