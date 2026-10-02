<?php

namespace DomainSystem\Plugins\builder_flex\Contracts;

interface WidgetInterface
{
    public function getName(): string;
    public function getShortcodeTag(): string;
    public function getControls(): array;
    public function render(array $attributes, string $content = ''): string;
}
