<?php

namespace DomainSystem\Plugins\flextheme\BuilderFlex\Contracts;

interface WidgetManagerInterface
{
    public function registerWidget(WidgetInterface $widget): void;
    public function getWidget(string $name): ?WidgetInterface;
    public function getWidgets(): array;
    public function bootShortcodes($shortcodeManager): void;
}

