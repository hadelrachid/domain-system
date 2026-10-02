<?php

namespace DomainSystem\Plugins\flextheme\BuilderFlex\Core;

use DomainSystem\Plugins\flextheme\BuilderFlex\Contracts\WidgetManagerInterface;
use DomainSystem\Plugins\flextheme\BuilderFlex\Contracts\WidgetInterface;

class WidgetManager implements WidgetManagerInterface
{
    protected array $widgets = [];

    public function registerWidget(WidgetInterface $widget): void
    {
        $this->widgets[$widget->getShortcodeTag()] = $widget;
    }

    public function getWidgets(): array
    {
        return $this->widgets;
    }

    // Acopla todos os widgets registrados dinamicamente ao motor de Shortcodes do Kernel
    public function bootShortcodes($shortcodeManager): void
    {
        foreach ($this->widgets as $tag => $widget) {
            $shortcodeManager->add($tag, function(array $attrs, string $content = '') use ($widget) {
                return $widget->render($attrs, $content);
            });
        }
    }
}

