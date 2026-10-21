<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Contracts;

/**
 * Registro e Gerenciador de Blocos do Motor Visual.
 */
interface BlockRegistryInterface
{
    /**
     * Registra um novo bloco no motor.
     */
    public function register(BlockInterface $block): void;

    /**
     * Retorna a instância de um bloco pelo seu tipo único.
     */
    public function get(string $type): ?BlockInterface;

    /**
     * Retorna todos os blocos registrados.
     * @return array<string, BlockInterface>
     */
    public function all(): array;

    /**
     * Retorna todos os blocos agrupados por categoria.
     * @return array<string, array<string, BlockInterface>>
     */
    public function allByCategory(): array;
}
