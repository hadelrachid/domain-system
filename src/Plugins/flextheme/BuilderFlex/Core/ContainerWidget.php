<?php

namespace DomainSystem\Plugins\flextheme\BuilderFlex\Core;

class ContainerWidget extends AbstractWidget
{
    public function getName(): string { return 'Container'; }
    public function getShortcodeTag(): string { return 'flex_container'; }

    public function toDto(array $attributes, array $children = []): array
    {
        $dto = parent::toDto($attributes, $children);
        $dto['styles']['display'] = 'flex';
        $dto['styles']['flex-direction'] = $attributes['direction'] ?? ($attributes['flexDirection'] ?? 'column');
        $dto['styles']['align-items'] = $attributes['align'] ?? ($attributes['alignItems'] ?? 'flex-start');
        return $dto;
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
