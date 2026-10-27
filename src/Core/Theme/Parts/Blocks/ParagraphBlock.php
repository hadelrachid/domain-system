<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Blocks;

use DomainSystem\Core\Theme\Parts\Blocks\Traits\AllowedPartTypesTrait;
use DomainSystem\Core\Theme\Parts\Contracts\BlockInterface;

/**
 * Bloco de Parágrafo / Texto Corrido.
 */
class ParagraphBlock implements BlockInterface
{
    use AllowedPartTypesTrait;
    public function getType(): string
    {
        return 'paragraph';
    }

    public function getName(): string
    {
        return 'Parágrafo (Texto)';
    }

    public function getIcon(): string
    {
        return '📄';
    }

    public function getCategory(): string
    {
        return 'typography';
    }

    public function render(array $data = [], array $context = []): string
    {
        $text = (string)($data['text'] ?? '');
        $size = (string)($data['size'] ?? '');
        $color = (string)($data['color'] ?? '');
        $align = (string)($data['align'] ?? 'left');
        $lineHeight = (string)($data['line_height'] ?? '1.7');

        $styles = [
            "text-align: {$align}",
            "line-height: {$lineHeight}",
            "margin: 0",
            "font-family: var(--font-primary, sans-serif)",
        ];

        if ($size !== '') {
            $styles[] = "font-size: {$size}";
        }
        if ($color !== '') {
            $styles[] = "color: {$color}";
        } else {
            $styles[] = "color: var(--color-text-muted, #8b8c8d)";
        }

        $styleAttr = implode('; ', $styles) . ';';
        // Permite tags HTML básicas como <strong>, <em>, <a>, <code>
        $sanitized = strip_tags($text, '<strong><b><em><i><a><code><br><span>');

        return "<p class=\"ds-block-paragraph\" style=\"{$styleAttr}\">{$sanitized}</p>";
    }

    public function getSchema(): array
    {
        return [
            'type' => 'paragraph',
            'fields' => [
                'text'        => ['type' => 'textarea', 'label' => 'Conteúdo do Texto', 'default' => 'Texto do parágrafo.'],
                'size'        => ['type' => 'dimension', 'label' => 'Tamanho da Fonte', 'units' => ['rem', 'px', 'em'], 'default' => '1.05rem'],
                'color'       => ['type' => 'color', 'label' => 'Cor', 'default' => 'var(--color-text-muted)'],
                'align'       => ['type' => 'select', 'label' => 'Alinhamento', 'options' => ['left', 'center', 'right', 'justify'], 'default' => 'left'],
                'line_height' => ['type' => 'text', 'label' => 'Altura de Linha', 'default' => '1.7'],
            ],
        ];
    }
}
