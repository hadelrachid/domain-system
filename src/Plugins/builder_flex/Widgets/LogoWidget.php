<?php

namespace DomainSystem\Plugins\builder_flex\Widgets;

use DomainSystem\Plugins\builder_flex\Core\AbstractWidget;

class LogoWidget extends AbstractWidget
{
    public function getName(): string { return 'Logo do Site'; }
    public function getShortcodeTag(): string { return 'flex_logo'; }

    public function render(array $attributes, string $content = ''): string
    {
        // Aqui ele vai ler a logo das configurações globais futuramente
        $logoUrl = $attributes['src'] ?? (defined('BASE_URL') ? BASE_URL . '/assets/img/logo-default.png' : '/logo.png');
        $width = $attributes['width'] ?? '150px';
        $commonAttrs = $this->extractCommonAttributes($attributes);
        
        return sprintf(
            '<a href="%s" %s>
                <img src="%s" alt="Logo" style="max-width: %s; height: auto;" />
            </a>',
            defined('BASE_URL') ? BASE_URL : '/',
            $commonAttrs,
            htmlspecialchars($logoUrl),
            htmlspecialchars($width)
        );
    }
}
