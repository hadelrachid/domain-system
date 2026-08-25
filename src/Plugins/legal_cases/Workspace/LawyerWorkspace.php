<?php
namespace DomainSystem\Plugins\legal_cases\Workspace;

use DomainSystem\Core\Workspace\WorkspaceInterface;
use DomainSystem\Core\Theme\ThemeManager;

class LawyerWorkspace implements WorkspaceInterface
{
    private ThemeManager $theme;

    public function __construct(ThemeManager $theme)
    {
        $this->theme = $theme;
    }

    public function wrap(string $content): string
    {
        return $this->theme->render('layout', ['content' => $content]);
    }

    public function getThemeName(): string
    {
        return 'lawyer';
    }
}
