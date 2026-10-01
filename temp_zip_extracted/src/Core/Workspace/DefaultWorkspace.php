<?php
namespace DomainSystem\Core\Workspace;

use DomainSystem\Core\Theme\ThemeManager;

class DefaultWorkspace implements WorkspaceInterface
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
        return 'admin';
    }
}
