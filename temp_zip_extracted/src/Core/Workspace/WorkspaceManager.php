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
            $workspace = $this->workspaces[$role];
        } else {
            $workspace = new DefaultWorkspace($this->theme);
        }

        // Troca automaticamente a fiação do motor de renderização para o CockPIT atual!
        $basePath = dirname(__DIR__, 3);
        $themeName = $workspace->getThemeName();
        if (is_dir($themeName)) {
            $this->theme->setActiveThemePath($themeName);
        } else {
            $this->theme->setActiveThemePath($basePath . '/themes/' . $themeName);
        }

        return $workspace;
    }
}
