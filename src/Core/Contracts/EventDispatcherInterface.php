<?php

namespace DomainSystem\Core\Contracts;

interface EventDispatcherInterface
{
    /**
     * Adiciona um ouvinte (listener) para um evento ou filtro.
     */
    public function addListener(string $eventName, callable $listener, int $priority = 10): void;

    /**
     * Remove um ouvinte previamente registrado.
     */
    public function removeListener(string $eventName, callable $listener): void;

    /**
     * Dispara um evento, chamando todos os ouvintes registrados.
     */
    public function dispatch(string $eventName, ...$args): void;

    /**
     * Aplica filtros a um valor, permitindo que plugins o modifiquem sequencialmente.
     */
    public function applyFilters(string $filterName, mixed $value, ...$args): mixed;
}
