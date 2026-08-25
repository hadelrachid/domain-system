<?php

namespace DomainSystem\Core\Plugin;

interface PluginInterface
{
    /**
     * Registra os hooks, rotas e serviços do plugin.
     * Chamado durante o boot do Kernel. Somente para registro/composição.
     */
    public function register(): void;

    /**
     * Inicialização pós-registro (quando todos os plugins já foram registrados).
     */
    public function boot(): void;

    /**
     * Chamado quando o plugin é ativado no painel (para migrations, etc).
     */
    public function activate(): void;

    /**
     * Chamado quando o plugin é desativado no painel.
     */
    public function deactivate(): void;

    /**
     * Chamado quando o plugin é completamente removido.
     */
    public function uninstall(): void;

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
