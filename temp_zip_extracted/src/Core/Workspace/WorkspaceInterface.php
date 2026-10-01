<?php
namespace DomainSystem\Core\Workspace;

/**
 * A "Tomada" (Interface / Contrato)
 * Define o padrão que todos os workspaces devem seguir.
 */
interface WorkspaceInterface
{
    /**
     * Envelopa o conteúdo bruto no layout do workspace.
     */
    public function wrap(string $content): string;

    /**
     * Retorna o nome da pasta do tema (CockPIT) que este workspace utiliza.
     */
    public function getThemeName(): string;
}
