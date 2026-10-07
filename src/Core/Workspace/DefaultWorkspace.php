<?php
namespace DomainSystem\Core\Workspace;

use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Core\Contracts\NotificationManagerInterface;

class DefaultWorkspace implements WorkspaceInterface
{
    private ThemeManager $theme;
    private ?NotificationManagerInterface $notifManager;

    public function __construct(ThemeManager $theme, ?NotificationManagerInterface $notifManager = null)
    {
        $this->theme = $theme;
        $this->notifManager = $notifManager;
    }

    public function wrap(string $content): string
    {
        $unreadNotifs = [];
        $allNotifs = [];

        if ($this->notifManager) {
            $unreadNotifs = $this->notifManager->getUnread();
            $allNotifs = $this->notifManager->getAll();
            if (!empty($unreadNotifs)) {
                $this->notifManager->markAsRead();
            }
        }

        return $this->theme->render('layout', [
            'content' => $content,
            'unreadNotifs' => $unreadNotifs,
            'allNotifs' => $allNotifs
        ]);
    }

    public function getThemeName(): string
    {
        return 'admin';
    }
}
