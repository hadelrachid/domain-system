<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Blocks;

use DomainSystem\Core\Theme\Parts\Blocks\Traits\AllowedPartTypesTrait;
use DomainSystem\Core\Theme\Parts\Contracts\BlockInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ElementStyleRendererInterface;
use DomainSystem\Core\Theme\Parts\Contracts\Styles\StylableElementInterface;
use DomainSystem\Core\Theme\Parts\Styles\ElementStyleRenderer;
use DomainSystem\Core\Theme\Parts\Styles\Traits\StylableElementTrait;

/**
 * Bloco de Título / Cabeçalho (H1 - H6).
 */
class HeadingBlock implements BlockInterface, StylableElementInterface
{
    use AllowedPartTypesTrait;
    use StylableElementTrait;

    private ElementStyleRendererInterface $styleRenderer;

    public function __construct(?ElementStyleRendererInterface $styleRenderer = null)
    {
        $this->styleRenderer = $styleRenderer ?? new ElementStyleRenderer();
    }

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
        $this->styleData = $data;
        $rawLevel = $data['level'] ?? 2;
        if (is_string($rawLevel) && preg_match('/^h?([1-6])$/i', trim($rawLevel), $m)) {
            $parsedLevel = (int)$m[1];
        } else {
            $parsedLevel = (int)$rawLevel;
        }
        $level = in_array($parsedLevel, [1, 2, 3, 4, 5, 6], true) ? $parsedLevel : 2;
        $tag = 'h' . $level;

        $text = (string)($data['text'] ?? 'Título');
        $size = $this->styleRenderer->sanitizeCssValue((string)($data['size'] ?? $data['font_size'] ?? ''));
        $color = $this->styleRenderer->sanitizeCssValue((string)($data['color'] ?? $data['text_color'] ?? $data['heading_color'] ?? ''));
        $align = $this->styleRenderer->sanitizeCssValue((string)($data['align'] ?? $data['text_align'] ?? 'left'));
        $fontFamily = $this->styleRenderer->sanitizeCssValue((string)($data['font_family'] ?? 'primary'));
        $fontWeight = $this->styleRenderer->sanitizeCssValue((string)($data['font_weight'] ?? ''));
        $lineHeight = $this->styleRenderer->sanitizeCssValue((string)($data['line_height'] ?? ''));
        $letterSpacing = $this->styleRenderer->sanitizeCssValue((string)($data['letter_spacing'] ?? ''));
        $textTransform = $this->styleRenderer->sanitizeCssValue((string)($data['text_transform'] ?? ''));

        $styles = [
            "text-align: {$align}",
            "margin: 0",
        ];

        if ($fontFamily === 'secondary') {
            $styles[] = "font-family: var(--font-secondary, monospace)";
        } elseif ($fontFamily === 'tertiary') {
            $styles[] = "font-family: var(--font-tertiary, sans-serif)";
        } elseif ($fontFamily === 'primary' || $fontFamily === '') {
            $styles[] = "font-family: var(--font-primary, sans-serif)";
        } else {
            $styles[] = "font-family: {$fontFamily}";
        }

        if ($size !== '') {
            $styles[] = "font-size: {$size}";
        }
        if ($fontWeight !== '') {
            $styles[] = "font-weight: {$fontWeight}";
        }
        if ($lineHeight !== '') {
            $styles[] = "line-height: {$lineHeight}";
        }
        if ($letterSpacing !== '') {
            $styles[] = "letter-spacing: {$letterSpacing}";
        }
        if ($textTransform !== '') {
            $styles[] = "text-transform: {$textTransform}";
        }
        if ($color !== '') {
            $styles[] = "color: {$color}";
        } else {
            $styles[] = "color: var(--ds-local-heading-color, var(--ds-local-text-color, var(--color-text-main, #ffffff)))";
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
                'text'           => ['type' => 'text', 'label' => 'Texto do Título', 'default' => 'Meu Título'],
                'level'          => ['type' => 'select', 'label' => 'Nível (H1-H6)', 'options' => [1, 2, 3, 4, 5, 6], 'default' => 2],
                'size'           => ['type' => 'dimension', 'label' => 'Tamanho da Fonte', 'units' => ['rem', 'px', 'em', 'vw'], 'default' => '1.8rem'],
                'color'          => ['type' => 'color', 'label' => 'Cor', 'default' => 'var(--color-text-main)'],
                'align'          => ['type' => 'select', 'label' => 'Alinhamento', 'options' => ['left', 'center', 'right'], 'default' => 'left'],
                'font_family'    => ['type' => 'select', 'label' => 'Família de Fonte', 'options' => ['primary', 'secondary', 'tertiary'], 'default' => 'primary'],
                'font_weight'    => ['type' => 'select', 'label' => 'Peso da Fonte', 'options' => ['300', '400', '500', '600', '700', '800', '900'], 'default' => '800'],
                'line_height'    => ['type' => 'text', 'label' => 'Altura de Linha', 'default' => '1.25'],
                'letter_spacing' => ['type' => 'dimension', 'label' => 'Espaçamento entre Letras', 'units' => ['px', 'em'], 'default' => '0px'],
                'text_transform' => ['type' => 'select', 'label' => 'Transformação de Texto', 'options' => ['none', 'uppercase', 'lowercase', 'capitalize'], 'default' => 'none'],
            ],
        ];
    }
}
