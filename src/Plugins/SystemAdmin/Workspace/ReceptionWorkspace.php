<?php
namespace DomainSystem\Plugins\SystemAdmin\Workspace;

use DomainSystem\Core\Workspace\WorkspaceInterface;
use DomainSystem\Core\Theme\ThemeManager;

class ReceptionWorkspace implements WorkspaceInterface
{
    private ThemeManager $theme;

    public function __construct(ThemeManager $theme)
    {
        $this->theme = $theme;
    }

    public function wrap(string $content): string
    {
        return $this->theme->render('reception/layout', ['content' => $content]);
    }
}
