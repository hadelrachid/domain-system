<?php

namespace DomainSystem\Plugins\legal_cases\Workspace;

use DomainSystem\Core\Workspace\WorkspaceInterface;
use DomainSystem\Core\Contracts\ThemeManagerInterface;

class LawyerWorkspace implements WorkspaceInterface
{
    private ThemeManagerInterface $theme;

    public function __construct(ThemeManagerInterface $theme)
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
