<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\flextheme\BuilderFlex\Contracts;

use DomainSystem\Core\Contracts\ShortcodeManagerInterface;

interface WidgetManagerInterface
{
    public function registerWidget(WidgetInterface $widget): void;
    public function getWidget(string $name): ?WidgetInterface;
    public function getWidgets(): array;
    public function bootShortcodes(ShortcodeManagerInterface $shortcodeManager): void;
}

