<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Blocks;

use DomainSystem\Core\Theme\Parts\Blocks\Traits\AllowedPartTypesTrait;
use DomainSystem\Core\Theme\Parts\Contracts\BlockInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ElementStyleRendererInterface;
use DomainSystem\Core\Theme\Parts\Styles\ElementStyleRenderer;

/**
 * Bloco de Divisória ou Espaçador.
 */
class DividerBlock implements BlockInterface
{
    use AllowedPartTypesTrait;

    private ElementStyleRendererInterface $styleRenderer;

    public function __construct(?ElementStyleRendererInterface $styleRenderer = null)
    {
        $this->styleRenderer = $styleRenderer ?? new ElementStyleRenderer();
    }

    public function getType(): string
    {
        return 'divider';
    }

    public function getName(): string
    {
        return 'Divisória / Espaçador';
    }

    public function getIcon(): string
    {
        return '➖';
    }

    public function getCategory(): string
    {
        return 'layout';
    }

    public function render(array $data = [], array $context = []): string
    {
        $style = (string)($data['style'] ?? 'solid');
        $height = $this->styleRenderer->sanitizeCssValue((string)($data['height'] ?? '20px'));
        $color = $this->styleRenderer->sanitizeCssValue((string)($data['color'] ?? 'rgba(255, 255, 255, 0.1)'));
        $width = $this->styleRenderer->sanitizeCssValue((string)($data['width'] ?? '100%'));
        $align = (string)($data['align'] ?? 'center');

        if ($style === 'spacer' || $style === 'transparent') {
            return "<div class=\"ds-block-spacer\" style=\"height: {$height}; width: 100%;\" aria-hidden=\"true\"></div>";
        }

        $marginAuto = match ($align) {
            'left'   => 'margin: 0 auto 0 0;',
            'right'  => 'margin: 0 0 0 auto;',
            default  => 'margin: 0 auto;',
        };

        if ($style === 'gradient') {
            return <<<HTML
<div class="ds-block-divider-wrap" style="padding: {$height} 0;">
    <div style="height: 1px; width: {$width}; background: linear-gradient(90deg, transparent, {$color}, transparent); {$marginAuto}"></div>
</div>
HTML;
        }

        $borderStyle = ($style === 'dashed') ? 'dashed' : 'solid';
        return <<<HTML
<div class="ds-block-divider-wrap" style="padding: {$height} 0;">
    <hr style="border: none; border-top: 1px {$borderStyle} {$color}; width: {$width}; {$marginAuto}">
</div>
HTML;
    }

    public function getSchema(): array
    {
        return [
            'type' => 'divider',
            'fields' => [
                'style'  => ['type' => 'select', 'label' => 'Tipo de Divisória', 'options' => ['solid', 'dashed', 'gradient', 'spacer'], 'default' => 'solid'],
                'height' => ['type' => 'dimension', 'label' => 'Altura / Espaçamento', 'units' => ['px', 'rem', 'vh'], 'default' => '20px'],
                'color'  => ['type' => 'color', 'label' => 'Cor da Linha', 'default' => 'rgba(255, 255, 255, 0.1)'],
                'width'  => ['type' => 'dimension', 'label' => 'Largura da Linha', 'units' => ['%', 'px', 'vw'], 'default' => '100%'],
                'align'  => ['type' => 'select', 'label' => 'Alinhamento', 'options' => ['center', 'left', 'right'], 'default' => 'center'],
            ],
        ];
    }
}
