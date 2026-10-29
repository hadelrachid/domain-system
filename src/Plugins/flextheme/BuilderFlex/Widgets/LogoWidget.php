<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\flextheme\BuilderFlex\Widgets;

use DomainSystem\Plugins\flextheme\BuilderFlex\Core\AbstractWidget;

class LogoWidget extends AbstractWidget
{
    public function getName(): string { return 'Logo do Site'; }
    public function getShortcodeTag(): string { return 'flex_logo'; }

    public function toDto(array $attributes, array $children = []): array
    {
        $dto = parent::toDto($attributes, $children);
        $logoUrl = $attributes['src'] ?? (defined('BASE_URL') ? BASE_URL . '/assets/img/logo-default.png' : '/logo.png');
        $width = $attributes['width'] ?? '150px';

        $dto['tag'] = 'a';
        $dto['type'] = 'a';
        $dto['attrs']['href'] = defined('BASE_URL') && BASE_URL !== '' ? BASE_URL : '/';
        $dto['children'] = [
            [
                'type' => 'img',
                'tag' => 'img',
                'attrs' => [
                    'src' => $logoUrl,
                    'alt' => 'Logo',
                ],
                'styles' => [
                    'max-width' => is_numeric($width) ? $width . 'px' : $width,
                    'height' => 'auto',
                ],
                'content' => '',
                'children' => [],
            ],
        ];
        return $dto;
    }
}
