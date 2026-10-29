<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\flextheme\BuilderFlex\Core;

use DomainSystem\Plugins\flextheme\BuilderFlex\Contracts\WidgetManagerInterface;
use DomainSystem\Plugins\flextheme\BuilderFlex\Contracts\WidgetInterface;

class WidgetManager implements WidgetManagerInterface
{
    /** @var array<string, WidgetInterface> */
    protected array $widgets = [];

    /** @var array<string, WidgetInterface> Índice O(1) por nome curto e nome da classe */
    protected array $shortNameIndex = [];

    public function registerWidget(WidgetInterface $widget): void
    {
        $tag = $widget->getShortcodeTag();
        $this->widgets[$tag] = $widget;

        $classParts = explode('\\', get_class($widget));
        $className = (string)end($classParts);
        $this->shortNameIndex[$className] = $widget;

        if (str_ends_with($className, 'Widget')) {
            $shortName = substr($className, 0, -6);
            if ($shortName !== '') {
                $this->shortNameIndex[$shortName] = $widget;
            }
        }
    }

    public function getWidgets(): array
    {
        return $this->widgets;
    }

    public function getWidget(string $name): ?WidgetInterface
    {
        // Busca O(1) direta pela tag do shortcode (ex: flex_container)
        if (isset($this->widgets[$name])) {
            return $this->widgets[$name];
        }

        // Busca O(1) pelo índice de nomes curtos / classes (ex: Container ou ContainerWidget)
        if (isset($this->shortNameIndex[$name])) {
            return $this->shortNameIndex[$name];
        }

        return null;
    }

    // Acopla todos os widgets registrados dinamicamente ao motor de Shortcodes do Kernel
    public function bootShortcodes($shortcodeManager): void
    {
        foreach ($this->widgets as $tag => $widget) {
            $shortcodeManager->add($tag, function (array $attrs, string $content = '') use ($widget) {
                return $widget->render($attrs, $content);
            });
        }
    }
}
