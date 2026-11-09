<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Blocks;

use DomainSystem\Core\Theme\Parts\Blocks\Traits\AllowedPartTypesTrait;
use DomainSystem\Core\Theme\Parts\Contracts\BlockInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ElementStyleRendererInterface;
use DomainSystem\Core\Theme\Parts\Contracts\HtmlSanitizerInterface;
use DomainSystem\Core\Theme\Parts\Contracts\Styles\StylableElementInterface;
use DomainSystem\Core\Theme\Parts\Styles\ElementStyleRenderer;
use DomainSystem\Core\Theme\Parts\Styles\Traits\StylableElementTrait;
use DomainSystem\Core\Theme\Parts\Support\SafeHtmlSanitizer;

/**
 * Bloco de Parágrafo / Texto Corrido.
 */
class ParagraphBlock extends AbstractBlock
{
    public function __construct(
        ?HtmlSanitizerInterface $htmlSanitizer = null,
        ?ElementStyleRendererInterface $styleRenderer = null
    ) {
        parent::__construct($htmlSanitizer, null, $styleRenderer);
    }

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
        $this->styleData = $data;
        $text = (string)($data['text'] ?? '');
        $size = $this->styleRenderer->sanitizeCssValue((string)($data['size'] ?? $data['font_size'] ?? ''));
        $color = $this->styleRenderer->sanitizeCssValue((string)($data['color'] ?? $data['text_color'] ?? ''));
        $align = $this->styleRenderer->sanitizeCssValue((string)($data['align'] ?? $data['text_align'] ?? 'left'));
        $lineHeight = $this->styleRenderer->sanitizeCssValue((string)($data['line_height'] ?? '1.7'));
        $fontFamily = $this->styleRenderer->sanitizeCssValue((string)($data['font_family'] ?? ''));
        $fontWeight = $this->styleRenderer->sanitizeCssValue((string)($data['font_weight'] ?? ''));
        $letterSpacing = $this->styleRenderer->sanitizeCssValue((string)($data['letter_spacing'] ?? ''));
        $textTransform = $this->styleRenderer->sanitizeCssValue((string)($data['text_transform'] ?? ''));

        $resolvedFont = match ($fontFamily) {
            'secondary' => 'var(--font-secondary, monospace)',
            'tertiary'  => 'var(--font-tertiary, sans-serif)',
            '', 'primary' => 'var(--font-primary, sans-serif)',
            default     => $fontFamily,
        };

        $styles = [
            "text-align: {$align}",
            "line-height: {$lineHeight}",
            "margin: 0",
            "font-family: {$resolvedFont}",
        ];

        if ($size !== '') {
            $styles[] = "font-size: {$size}";
        }
        if ($fontWeight !== '') {
            $styles[] = "font-weight: {$fontWeight}";
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
            $styles[] = "color: var(--ds-local-text-color, var(--color-text-muted, #8b8c8d))";
        }

        $styleAttr = implode('; ', $styles) . ';';
        // Sanitização segura de HTML inline contra XSS (atributos on*, javascript:, etc.)
        $sanitized = $this->htmlSanitizer->sanitizeInlineHtml($text);

        return "<p class=\"ds-block-paragraph\" style=\"{$styleAttr}\">{$sanitized}</p>";
    }

    public function getSchema(): array
    {
        return [
            'type' => 'paragraph',
            'fields' => [
                'text'           => ['type' => 'textarea', 'label' => 'Conteúdo do Texto', 'default' => 'Texto do parágrafo.'],
                'size'           => ['type' => 'dimension', 'label' => 'Tamanho da Fonte', 'units' => ['rem', 'px', 'em'], 'default' => '1.05rem'],
                'color'          => ['type' => 'color', 'label' => 'Cor', 'default' => 'var(--color-text-muted)'],
                'align'          => ['type' => 'select', 'label' => 'Alinhamento', 'options' => ['left', 'center', 'right', 'justify'], 'default' => 'left'],
                'line_height'    => ['type' => 'text', 'label' => 'Altura de Linha', 'default' => '1.7'],
                'font_family'    => ['type' => 'select', 'label' => 'Família de Fonte', 'options' => ['primary', 'secondary', 'tertiary'], 'default' => 'primary'],
                'font_weight'    => ['type' => 'select', 'label' => 'Peso da Fonte', 'options' => ['300', '400', '500', '600', '700', '800'], 'default' => '400'],
                'letter_spacing' => ['type' => 'dimension', 'label' => 'Espaçamento entre Letras', 'units' => ['px', 'em'], 'default' => '0px'],
                'text_transform' => ['type' => 'select', 'label' => 'Transformação de Texto', 'options' => ['none', 'uppercase', 'lowercase', 'capitalize'], 'default' => 'none'],
            ],
        ];
    }
}
