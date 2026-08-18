<?php

namespace DomainSystem\Core\Plugin;

interface PluginInterface
{
    /**
     * Registra os hooks, rotas e serviços do plugin.
     * Chamado durante o boot do Kernel.
     */
    public function register(): void;

    /**
     * Retorna o nome do plugin (para identificação).
     */
    public function getName(): string;

    /**
     * Retorna a versão do plugin.
     */
    public function getVersion(): string;

    /**
     * Retorna as dependências (nomes de outros plugins) que este plugin precisa.
     */
    public function getDependencies(): array;

    /**
     * Define se o plugin está ativo.
     */
    public function isActive(): bool;
}
