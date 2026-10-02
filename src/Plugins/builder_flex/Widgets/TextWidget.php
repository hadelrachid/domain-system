<?php

namespace DomainSystem\Plugins\builder_flex\Widgets;

use DomainSystem\Plugins\builder_flex\Core\AbstractWidget;

class TextWidget extends AbstractWidget
{
    public function getName(): string { return 'Texto'; }
    public function getShortcodeTag(): string { return 'flex_text'; }

    public function render(array $attributes, string $content = ''): string
    {
        $tag = $attributes['tag'] ?? 'p';
        $commonAttrs = $this->extractCommonAttributes($attributes);
        
        return sprintf(
            '<%s %s>%s</%s>',
            htmlspecialchars($tag),
            $commonAttrs,
            $content,
            htmlspecialchars($tag)
        );
    }
}
