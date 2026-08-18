<?php

namespace DomainSystem\Plugins\SystemAdmin\Controllers;

use DomainSystem\Core\Container\Container;
use DomainSystem\Core\Theme\ThemeManager;
use Exception;

class DashboardController
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function index()
    {
        /** @var ThemeManager $theme */
        $theme = $this->container->make(ThemeManager::class);

        try {
            return $theme->render('admin/dashboard');
        } catch (Exception $e) {
            return "Erro ao renderizar dashboard: " . $e->getMessage();
        }
    }
}
