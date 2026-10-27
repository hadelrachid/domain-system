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
 * Bloco de Imagem com Dimensões e Alinhamento.
 */
class ImageBlock implements BlockInterface
{
    use AllowedPartTypesTrait;

    private ElementStyleRendererInterface $styleRenderer;
    private HtmlSanitizerInterface $htmlSanitizer;

    public function __construct(
        ?ElementStyleRendererInterface $styleRenderer = null,
        ?HtmlSanitizerInterface $htmlSanitizer = null
    ) {
        $this->styleRenderer = $styleRenderer ?? new ElementStyleRenderer();
        $this->htmlSanitizer = $htmlSanitizer ?? new SafeHtmlSanitizer();
    }

    public function getType(): string
    {
        return 'image';
    }

    public function getName(): string
    {
        return 'Imagem';
    }

    public function getIcon(): string
    {
        return '🖼️';
    }

    public function getCategory(): string
    {
        return 'media';
    }

    public function render(array $data = [], array $context = []): string
    {
        $rawUrl = trim((string)($data['url'] ?? ''));
        if ($rawUrl === '') {
            return '';
        }

        $url = $this->htmlSanitizer->sanitizeUrl($rawUrl, '');
        if ($url === '') {
            return '';
        }

        $alt = htmlspecialchars((string)($data['alt'] ?? 'Imagem'), ENT_QUOTES, 'UTF-8');
        $width = $this->styleRenderer->sanitizeCssValue((string)($data['width'] ?? 'auto'));
        $height = $this->styleRenderer->sanitizeCssValue((string)($data['height'] ?? 'auto'));
        $borderRadius = $this->styleRenderer->sanitizeCssValue((string)($data['border_radius'] ?? '0'));
        $rawLink = trim((string)($data['link'] ?? ''));
        $link = $rawLink !== '' ? $this->htmlSanitizer->sanitizeUrl($rawLink, '') : '';
        $rawAlign = (string)($data['align'] ?? 'left');
        $align = in_array($rawAlign, ['left', 'center', 'right'], true) ? $rawAlign : 'left';

        $styles = [
            "max-width: 100%",
            "height: {$height}",
            "border-radius: {$borderRadius}",
            "display: inline-block",
        ];
        if ($width !== 'auto' && $width !== '') {
            $styles[] = "width: {$width}";
        }

        $styleAttr = implode('; ', $styles) . ';';
        $wrapperStyle = "text-align: {$align}; line-height: 0;";

        $imgHtml = "<img src=\"" . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . "\" alt=\"{$alt}\" style=\"{$styleAttr}\" loading=\"lazy\">";

        if ($link !== '') {
            $safeLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
            $imgHtml = "<a href=\"{$safeLink}\" style=\"display: inline-block;\">{$imgHtml}</a>";
        }

        return "<div class=\"ds-block-image\" style=\"{$wrapperStyle}\">{$imgHtml}</div>";
    }

    public function getSchema(): array
    {
        return [
            'type' => 'image',
            'fields' => [
                'url'           => ['type' => 'media', 'label' => 'URL da Imagem', 'default' => ''],
                'alt'           => ['type' => 'text', 'label' => 'Texto Alternativo (Alt)', 'default' => ''],
                'width'         => ['type' => 'dimension', 'label' => 'Largura', 'units' => ['auto', 'px', '%', 'vw'], 'default' => 'auto'],
                'height'        => ['type' => 'dimension', 'label' => 'Altura', 'units' => ['auto', 'px', 'vh'], 'default' => 'auto'],
                'border_radius' => ['type' => 'dimension', 'label' => 'Arredondamento de Bordas', 'units' => ['px', 'rem', '%'], 'default' => '8px'],
                'link'          => ['type' => 'text', 'label' => 'Link de Destino (Opcional)', 'default' => ''],
                'align'         => ['type' => 'select', 'label' => 'Alinhamento', 'options' => ['left', 'center', 'right'], 'default' => 'left'],
            ],
        ];
    }
}
