<?php

namespace DomainSystem\Plugins\flextheme\BuilderFlex\Core;

class ContainerWidget extends AbstractWidget
{
    public function getName(): string { return 'Container'; }
    public function getShortcodeTag(): string { return 'flex_container'; }

    public function render(array $attributes, string $content = ''): string
    {
        $commonAttrs = $this->extractCommonAttributes($attributes);
        $flexDir = $attributes['direction'] ?? 'column';
        $align = $attributes['align'] ?? 'flex-start';
        
        return sprintf(
            '<div %s style="display:flex; flex-direction:%s; align-items:%s;">%s</div>',
            htmlspecialchars($flexDir),
            htmlspecialchars($align),
            $commonAttrs,
            $content
        );
    }

    public function getControls(): array
    {
        $parentControls = parent::getControls();
        $parentControls['content'] = [
            'title' => 'Flexbox',
            'controls' => [
                'direction' => [
                    'type' => 'select',
                    'label' => 'Direção',
                    'options' => ['row' => 'Linha', 'column' => 'Coluna'],
                    'css_property' => 'flex-direction'
                ],
                'align' => [
                    'type' => 'select',
                    'label' => 'Alinhamento',
                    'options' => ['flex-start' => 'Início', 'center' => 'Centro', 'flex-end' => 'Fim'],
                    'css_property' => 'align-items'
                ]
            ]
        ];
        return $parentControls;
    }

}
