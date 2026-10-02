<?php

namespace DomainSystem\Plugins\builder_flex\Contracts;

interface WidgetManagerInterface
{
    public function registerWidget(WidgetInterface $widget): void;
    public function getWidgets(): array;
    public function bootShortcodes($shortcodeManager): void;
}
