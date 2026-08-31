<?php

namespace DomainSystem\Plugins\ai_hub\Controllers;

use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Core\Plugin\PluginManager;

class BuilderController
{
    private ThemeManager $theme;
    private PluginManager $pluginManager;

    public function __construct(ThemeManager $theme, PluginManager $pluginManager)
    {
        $this->theme = $theme;
        $this->pluginManager = $pluginManager;
    }

    public function index(Request $request): Response
    {
        $plugins = $this->pluginManager->getPlugins();
        $pluginList = [];
        
        foreach ($plugins as $p) {
            // We only show custom generated plugins or allow creating new ones
            // Actually, we'll list everything but distinguish them.
            $pluginList[] = [
                'name' => $p->getName(),
                'active' => $p->isActive()
            ];
        }

        $html = $this->theme->render('builder', ['plugins' => $pluginList], __DIR__ . '/../views');
        return new Response($html);
    }
}
