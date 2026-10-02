<?php

namespace DomainSystem\Plugins\builder_flex\Controllers;

use DomainSystem\Core\Contracts\ThemeManagerInterface;
use DomainSystem\Plugins\builder_flex\Contracts\WidgetManagerInterface;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Application;

class BuilderAdminController
{
    private ThemeManagerInterface $theme;

    public function __construct(ThemeManagerInterface $theme)
    {
        $this->theme = $theme;
    }

    public function index()
    {
        // Renderiza a view do editor visual
        include dirname(__DIR__) . '/views/builder_editor.php';
    }

    public function getSchema()
    {
        $manager = Application::getInstance()->getContainer()->make(WidgetManagerInterface::class);
        $schema = [];
        foreach ($manager->getWidgets() as $tag => $widget) {
            $schema[$tag] = [
                'name' => $widget->getName(),
                'controls' => $widget->getControls()
            ];
        }
        return new Response(json_encode($schema), 200, ['Content-Type' => 'application/json']);
    }

}