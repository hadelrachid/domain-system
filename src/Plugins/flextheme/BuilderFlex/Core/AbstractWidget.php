<?php

namespace DomainSystem\Plugins\flextheme\BuilderFlex\Core;

use DomainSystem\Plugins\flextheme\BuilderFlex\Contracts\WidgetInterface;

abstract class AbstractWidget implements WidgetInterface
{
    // Lógicas compartilhadas por TODOS os widgets (ex: margens, padding, IDs dinâmicos)
    protected function extractCommonAttributes(array $attributes): string
    {
        $html = '';
        if (!empty($attributes['id'])) $html .= ' id="' . htmlspecialchars($attributes['id']) . '"';
        
        $classes = ['widget-node'];
        if (!empty($attributes['layout'])) {
            $classes[] = 'layout-' . $attributes['layout'];
        }
        $html .= ' class="' . htmlspecialchars(implode(' ', $classes)) . '"';

        $styles = $this->generateBaseStyles($attributes);
        if (!empty($styles)) {
            $html .= ' style="' . htmlspecialchars($styles) . '"';
        }
        
        return $html;
    }

    protected function generateBaseStyles(array $props): string
    {
        $unit = function($val) {
            if ($val === null || $val === '') return '';
            if (is_numeric($val)) return $val . 'px';
            return $val;
        };

        $styles = '';
        
        // Posição base
        $pos = $props['position'] ?? 'absolute';
        $styles .= "position: {$pos}; ";
        if (isset($props['left'])) $styles .= "left: " . $unit($props['left']) . "; ";
        if (isset($props['top'])) $styles .= "top: " . $unit($props['top']) . "; ";
        if (isset($props['width'])) $styles .= "width: " . $unit($props['width']) . "; ";
        
        $height = $props['height'] ?? 'auto';
        $styles .= "height: " . ($height === 'auto' ? 'auto' : $unit($height)) . "; ";
        if (isset($props['minHeight'])) $styles .= "min-height: " . $unit($props['minHeight']) . "; ";

        // Flex e Box Model
        if (isset($props['flex'])) $styles .= "flex: {$props['flex']}; ";
        if (isset($props['margin'])) $styles .= "margin: {$props['margin']}; ";
        if (isset($props['padding'])) $styles .= "padding: {$props['padding']}; ";
        if (isset($props['display'])) $styles .= "display: {$props['display']}; ";
        if (isset($props['flexDirection'])) $styles .= "flex-direction: {$props['flexDirection']}; ";
        if (isset($props['justifyContent'])) $styles .= "justify-content: {$props['justifyContent']}; ";
        if (isset($props['alignItems'])) $styles .= "align-items: {$props['alignItems']}; ";
        if (isset($props['gap'])) $styles .= "gap: {$props['gap']}; ";
        
        $order = $props['order'] ?? 0;
        $styles .= "order: {$order}; ";

        // Estética
        if (isset($props['backgroundColor'])) $styles .= "background-color: {$props['backgroundColor']}; ";
        if (isset($props['border'])) $styles .= "border: {$props['border']}; ";
        if (isset($props['borderRadius'])) $styles .= "border-radius: " . $unit($props['borderRadius']) . "; ";
        if (isset($props['boxShadow'])) $styles .= "box-shadow: {$props['boxShadow']}; ";
        if (isset($props['opacity'])) $styles .= "opacity: {$props['opacity']}; ";
        if (isset($props['zIndex'])) $styles .= "z-index: {$props['zIndex']}; ";
        if (isset($props['overflow'])) $styles .= "overflow: {$props['overflow']}; ";
        if (isset($props['color'])) $styles .= "color: {$props['color']}; ";
        
        // Esconder
        if (isset($props['hidden']) && ($props['hidden'] === true || $props['hidden'] === 'true')) {
            $styles .= "display: none !important; ";
        }

        return $styles;
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
