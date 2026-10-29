<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\flextheme\BuilderFlex\Core;

use DomainSystem\Plugins\flextheme\BuilderFlex\Contracts\WidgetInterface;

abstract class AbstractWidget implements WidgetInterface
{
    /**
     * Constrói o mapa estruturado de estilos CSS a partir das propriedades do widget.
     */
    protected function buildStyles(array $props): array
    {
        $unit = fn($val) => (is_numeric($val) ? $val . 'px' : $val);
        $styles = [];

        $map = [
            'position' => 'position', 'left' => 'left', 'top' => 'top',
            'width' => 'width', 'height' => 'height', 'minHeight' => 'min-height',
            'flex' => 'flex', 'margin' => 'margin', 'padding' => 'padding',
            'display' => 'display', 'flexDirection' => 'flex-direction',
            'justifyContent' => 'justify-content', 'alignItems' => 'align-items',
            'gap' => 'gap', 'order' => 'order', 'backgroundColor' => 'background-color',
            'border' => 'border', 'borderRadius' => 'border-radius',
            'boxShadow' => 'box-shadow', 'opacity' => 'opacity',
            'zIndex' => 'z-index', 'overflow' => 'overflow', 'color' => 'color',
        ];

        foreach ($map as $key => $cssProperty) {
            if (isset($props[$key]) && $props[$key] !== '' && $props[$key] !== null) {
                $styles[$cssProperty] = in_array($key, ['width', 'height', 'minHeight', 'left', 'top', 'borderRadius'], true)
                    ? $unit($props[$key])
                    : $props[$key];
            }
        }

        if (($props['hidden'] ?? false) === true || ($props['hidden'] ?? '') === 'true') {
            $styles['display'] = 'none';
        }

        return $styles;
    }

    /**
     * Construtor padrão de DTO. Os widgets filhos especializam tag/styles/content.
     */
    public function toDto(array $attributes, array $children = []): array
    {
        $classes = ['flex-widget', 'widget-node'];
        if (!empty($attributes['layout'])) {
            $classes[] = 'layout-' . $attributes['layout'];
        }
        if (!empty($attributes['class_css'])) {
            $classes[] = $attributes['class_css'];
        }

        return [
            'type' => 'div',
            'tag' => 'div',
            'attrs' => [
                'id' => !empty($attributes['id']) ? $attributes['id'] : null,
                'class' => array_values(array_filter($classes)),
            ],
            'styles' => $this->buildStyles($attributes),
            'content' => (string)($attributes['content'] ?? ''),
            'children' => $children,
        ];
    }

    /**
     * Renderização de compatibilidade (ex: Shortcodes diretos) delegando ao DtoRenderer.
     */
    public function render(array $attributes, string $content = ''): string
    {
        $dto = $this->toDto($attributes, []);
        if ($content !== '' && empty($dto['content'])) {
            $dto['content'] = $content;
        }
        return (new DtoRenderer())->render($dto);
    }

    // Helpers legados mantidos como protected para retrocompatibilidade
    protected function extractCommonAttributes(array $attributes): string
    {
        $html = '';
        if (!empty($attributes['id'])) {
            $html .= ' id="' . htmlspecialchars((string)$attributes['id']) . '"';
        }

        $classes = ['widget-node'];
        if (!empty($attributes['layout'])) {
            $classes[] = 'layout-' . $attributes['layout'];
        }
        if (!empty($attributes['class_css'])) {
            $classes[] = $attributes['class_css'];
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
        $styles = $this->buildStyles($props);
        $parts = [];
        foreach ($styles as $prop => $val) {
            $parts[] = "{$prop}: {$val};";
        }
        return implode(' ', $parts);
    }

    protected function extractCommonStyles(array $attributes): string
    {
        $styles = '';
        if (!empty($attributes['margin'])) {
            $styles .= 'margin:' . $attributes['margin'] . ';';
        }
        if (!empty($attributes['padding'])) {
            $styles .= 'padding:' . $attributes['padding'] . ';';
        }
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
