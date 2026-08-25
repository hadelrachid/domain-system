<?php
namespace DomainSystem\Core\Workspace;

use DomainSystem\Core\Container\Container;
use DomainSystem\Core\Theme\ThemeManager;

/**
 * O Gerenciador de Workspaces
 * Funciona como um Registro de Tomadas. Não sabe nada sobre médicos ou advogados.
 */
class WorkspaceManager
{
    private Container $container;
    private ThemeManager $theme;
    private array $workspaces = [];

    public function __construct(Container $container, ThemeManager $theme)
    {
        $this->container = $container;
        $this->theme = $theme;
    }

    /**
     * Registra um novo layout/workspace para um cargo específico.
     */
    public function registerWorkspace(string $role, WorkspaceInterface $workspace): void
    {
        $this->workspaces[strtolower($role)] = $workspace;
    }

    /**
     * Retorna a implementação correta da interface baseada no cargo
     */
    public function getWorkspace(string $role): WorkspaceInterface
    {
        $role = strtolower($role);
        
        if (isset($this->workspaces[$role])) {
            return $this->workspaces[$role];
        }

        // Fallback genérico do Core (se nenhum plugin assumir)
        return new DefaultWorkspace($this->theme);
    }
}
