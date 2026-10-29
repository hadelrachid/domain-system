<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\flextheme\BuilderFlex\Widgets;

use DomainSystem\Plugins\flextheme\BuilderFlex\Core\AbstractWidget;

class TextWidget extends AbstractWidget
{
    public function getName(): string { return 'Texto'; }
    public function getShortcodeTag(): string { return 'flex_text'; }

    public function toDto(array $attributes, array $children = []): array
    {
        $dto = parent::toDto($attributes, $children);
        $dto['tag'] = $attributes['tag'] ?? 'p';
        $dto['type'] = $dto['tag'];
        $dto['content'] = (string)($attributes['content'] ?? ($attributes['text'] ?? ''));
        return $dto;
    }
}
