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
}
