<?php

declare(strict_types=1);

namespace DomainSystem\SystemApps\auth\Contracts;

interface LoginThrottleInterface
{
    /**
     * Verifica se a chave excedeu o limite tolerado de tentativas de login.
     */
    public function tooManyAttempts(string $key): bool;

    /**
     * Registra uma falha de autenticação para a chave.
     */
    public function hit(string $key): void;

    /**
     * Limpa o histórico de tentativas após sucesso na autenticação.
     */
    public function clear(string $key): void;
}
