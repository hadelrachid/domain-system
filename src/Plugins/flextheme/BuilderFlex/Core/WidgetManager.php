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

    public function getWidget(string $name): ?WidgetInterface
    {
        // Busca direta pela tag do shortcode (ex: flex_container)
        if (isset($this->widgets[$name])) {
            return $this->widgets[$name];
        }
        
        // Busca pelo nome da classe/tipo JS (ex: ContainerWidget)
        foreach ($this->widgets as $widget) {
            $classParts = explode('\\', get_class($widget));
            $className = end($classParts);
            if ($className === $name || $className === $name . 'Widget') {
                return $widget;
            }
        }
        return null;
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

