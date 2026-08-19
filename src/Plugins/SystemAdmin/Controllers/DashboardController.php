<?php

namespace DomainSystem\Plugins\SystemAdmin\Controllers;

use DomainSystem\Core\Theme\ThemeManager;
use Exception;

class DashboardController
{
    private ThemeManager $theme;

    public function __construct(ThemeManager $theme)
    {
        $this->theme = $theme;
    }

    public function index()
    {
        try {
            return $this->theme->render('admin/dashboard');
        } catch (Exception $e) {
            return "Erro ao renderizar dashboard: " . $e->getMessage();
        }
    }
}
