<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Blocks;

use DomainSystem\Core\Theme\Parts\Blocks\Traits\AllowedPartTypesTrait;
use DomainSystem\Core\Theme\Parts\Contracts\BlockInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ElementStyleRendererInterface;
use DomainSystem\Core\Theme\Parts\Contracts\HtmlSanitizerInterface;
use DomainSystem\Core\Theme\Parts\Styles\ElementStyleRenderer;
use DomainSystem\Core\Theme\Parts\Support\SafeHtmlSanitizer;

/**
 * Bloco de Parágrafo / Texto Corrido.
 */
class ParagraphBlock implements BlockInterface
{
    use AllowedPartTypesTrait;

    private HtmlSanitizerInterface $htmlSanitizer;
    private ElementStyleRendererInterface $styleRenderer;

    public function __construct(
        ?HtmlSanitizerInterface $htmlSanitizer = null,
        ?ElementStyleRendererInterface $styleRenderer = null
    ) {
        $this->htmlSanitizer = $htmlSanitizer ?? new SafeHtmlSanitizer();
        $this->styleRenderer = $styleRenderer ?? new ElementStyleRenderer();
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
        $text = (string)($data['text'] ?? '');
        $size = $this->styleRenderer->sanitizeCssValue((string)($data['size'] ?? ''));
        $color = $this->styleRenderer->sanitizeCssValue((string)($data['color'] ?? ''));
        $align = $this->styleRenderer->sanitizeCssValue((string)($data['align'] ?? 'left'));
        $lineHeight = $this->styleRenderer->sanitizeCssValue((string)($data['line_height'] ?? '1.7'));

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
        // Sanitização segura de HTML inline contra XSS (atributos on*, javascript:, etc.)
        $sanitized = $this->htmlSanitizer->sanitizeInlineHtml($text);

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
