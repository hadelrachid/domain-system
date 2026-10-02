<?php

namespace DomainSystem\Plugins\builder_flex\Core;

use DomainSystem\Plugins\builder_flex\Contracts\WidgetInterface;

abstract class AbstractWidget implements WidgetInterface
{
    // Lógicas compartilhadas por TODOS os widgets (ex: margens, padding, IDs dinâmicos)
    protected function extractCommonAttributes(array $attributes): string
    {
        $html = '';
        if (!empty($attributes['id_css'])) $html .= ' id="' . htmlspecialchars($attributes['id_css']) . '"';
        
        $classes = ['flex-widget'];
        if (!empty($attributes['class_css'])) $classes[] = $attributes['class_css'];
        $html .= ' class="' . htmlspecialchars(implode(' ', $classes)) . '"';

        $styles = '';
        if (!empty($attributes['width'])) $styles .= 'width:' . $attributes['width'] . ';';
        if (!empty($attributes['margin'])) $styles .= 'margin:' . $attributes['margin'] . ';';
        if (!empty($attributes['padding'])) $styles .= 'padding:' . $attributes['padding'] . ';';
        
        if (!empty($styles)) $html .= ' style="' . htmlspecialchars($styles) . '"';
        
        return $html;
    }

    protected function extractCommonStyles(array $attributes): string
    {
        $styles = '';
        if (!empty($attributes['margin'])) $styles .= 'margin:' . $attributes['margin'] . ';';
        if (!empty($attributes['padding'])) $styles .= 'padding:' . $attributes['padding'] . ';';
        return $styles;
    }

    public function getControls(): array
    {
        return [
            'layout' => [
                'title' => 'Layout e Espaçamento',
                'controls' => [
                    'width' => [
                        'type' => 'dimension',
                        'label' => 'Largura',
                        'units' => ['px', '%', 'vw', 'auto'],
                        // A mágica do Live Preview: O JS vai saber exatamente qual CSS atualizar no DOM!
                        'css_property' => 'width'
                    ],
                    'margin' => [
                        'type' => 'spacing',
                        'label' => 'Margem',
                        'css_property' => 'margin'
                    ],
                    'padding' => [
                        'type' => 'spacing',
                        'label' => 'Preenchimento',
                        'css_property' => 'padding'
                    ]
                ]
            ]
        ];
    }

}