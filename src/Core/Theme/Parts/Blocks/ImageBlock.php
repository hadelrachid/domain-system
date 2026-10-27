<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Blocks;

use DomainSystem\Core\Theme\Parts\Blocks\Traits\AllowedPartTypesTrait;
use DomainSystem\Core\Theme\Parts\Contracts\BlockInterface;

/**
 * Bloco de Imagem com Dimensões e Alinhamento.
 */
class ImageBlock implements BlockInterface
{
    use AllowedPartTypesTrait;
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
        $url = (string)($data['url'] ?? '');
        $alt = htmlspecialchars((string)($data['alt'] ?? 'Imagem'), ENT_QUOTES, 'UTF-8');
        $width = (string)($data['width'] ?? 'auto');
        $height = (string)($data['height'] ?? 'auto');
        $borderRadius = (string)($data['border_radius'] ?? '0');
        $link = (string)($data['link'] ?? '');
        $align = (string)($data['align'] ?? 'left');

        if ($url === '') {
            return '';
        }

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
