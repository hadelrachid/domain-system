<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Blocks;

use DomainSystem\Core\Theme\Parts\Contracts\BlockInterface;

/**
 * Bloco de Título / Cabeçalho (H1 - H6).
 */
class HeadingBlock implements BlockInterface
{
    public function getType(): string
    {
        return 'heading';
    }

    public function getName(): string
    {
        return 'Título (Heading)';
    }

    public function getIcon(): string
    {
        return '🔤';
    }

    public function getCategory(): string
    {
        return 'typography';
    }

    public function render(array $data = [], array $context = []): string
    {
        $rawLevel = (int)($data['level'] ?? 2);
        $level = in_array($rawLevel, [1, 2, 3, 4, 5, 6], true) ? $rawLevel : 2;
        $tag = 'h' . $level;

        $text = (string)($data['text'] ?? 'Título');
        $size = (string)($data['size'] ?? '');
        $color = (string)($data['color'] ?? '');
        $align = (string)($data['align'] ?? 'left');
        $fontFamily = (string)($data['font_family'] ?? 'primary');

        $styles = [
            "text-align: {$align}",
            "margin: 0",
        ];

        if ($fontFamily === 'secondary') {
            $styles[] = "font-family: var(--font-secondary, monospace)";
        } else {
            $styles[] = "font-family: var(--font-primary, sans-serif)";
        }

        if ($size !== '') {
            $styles[] = "font-size: {$size}";
        }
        if ($color !== '') {
            $styles[] = "color: {$color}";
        } else {
            $styles[] = "color: var(--color-text-main, #ffffff)";
        }

        $styleAttr = implode('; ', $styles) . ';';
        $safeText = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        return "<{$tag} class=\"ds-block-heading ds-heading-{$level}\" style=\"{$styleAttr}\">{$safeText}</{$tag}>";
    }

    public function getSchema(): array
    {
        return [
            'type' => 'heading',
            'fields' => [
                'text'        => ['type' => 'text', 'label' => 'Texto do Título', 'default' => 'Meu Título'],
                'level'       => ['type' => 'select', 'label' => 'Nível (H1-H6)', 'options' => [1, 2, 3, 4, 5, 6], 'default' => 2],
                'size'        => ['type' => 'dimension', 'label' => 'Tamanho da Fonte', 'units' => ['rem', 'px', 'em', 'vw'], 'default' => '1.8rem'],
                'color'       => ['type' => 'color', 'label' => 'Cor', 'default' => 'var(--color-text-main)'],
                'align'       => ['type' => 'select', 'label' => 'Alinhamento', 'options' => ['left', 'center', 'right'], 'default' => 'left'],
                'font_family' => ['type' => 'select', 'label' => 'Família de Fonte', 'options' => ['primary', 'secondary'], 'default' => 'primary'],
            ],
        ];
    }
}
