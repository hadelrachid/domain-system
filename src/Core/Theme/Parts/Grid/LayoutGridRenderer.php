<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Grid;

use DomainSystem\Core\Theme\Parts\Contracts\LayoutGridInterface;
use DomainSystem\Core\Theme\Parts\Contracts\Layout\LayoutPresetRegistryInterface;

/**
 * Motor de Renderização de Grade e Divisórias (CSS Grid / Flexbox) (SOLID - OCP / DIP).
 * 
 * Totalmente extensível via LayoutPresetRegistryInterface: novos layouts podem ser adicionados
 * por plugins ou código de temas sem alterar o motor interno (Open-Closed Principle).
 */
class LayoutGridRenderer implements LayoutGridInterface
{
    private LayoutPresetRegistryInterface $presetRegistry;

    public function __construct(?LayoutPresetRegistryInterface $presetRegistry = null)
    {
        $this->presetRegistry = $presetRegistry ?? DefaultLayoutPresetsFactory::create();
    }

    public function getPresetRegistry(): LayoutPresetRegistryInterface
    {
        return $this->presetRegistry;
    }

    public function getPresets(): array
    {
        return $this->presetRegistry->allAsArray();
    }

    public function renderSection(string $preset, array $columnsHtml, array $settings = []): string
    {
        $presetObj = $this->presetRegistry->get($preset) ?? $this->presetRegistry->get('1_col');
        $presetConfig = $presetObj !== null ? $presetObj->toArray() : [
            'columns'  => 1,
            'template' => '1fr',
            'tablet'   => '1fr',
            'mobile'   => '1fr',
        ];

        $sectionId = 'ds_sec_' . substr(md5(uniqid((string)mt_rand(), true)), 0, 8);

        $isBoxed = (bool)($settings['boxed'] ?? true);
        $maxWidth = (string)($settings['max_width'] ?? '1280px');
        $rawGap = (string)($settings['gap'] ?? '24px');
        $gap = match ($rawGap) {
            'none'   => '0px',
            'small'  => '12px',
            'medium' => '24px',
            'large'  => '40px',
            default  => $rawGap !== '' ? $rawGap : '24px',
        };

        $alignItems = (string)($settings['align_items'] ?? 'center');
        $justifyContent = (string)($settings['justify_content'] ?? 'space-between');
        $padding = (string)($settings['padding'] ?? '20px 16px');
        $margin = (string)($settings['margin'] ?? '0px');
        $background = (string)($settings['background'] ?? 'transparent');
        $customClass = (string)($settings['custom_class'] ?? '');

        // Estilos Visuais Avançados da Seção (Bordas, Sombras, Opacidade)
        $border = !empty($settings['border']) ? 'border: ' . htmlspecialchars((string)$settings['border'], ENT_QUOTES, 'UTF-8') . ';' : '';
        $borderRadius = !empty($settings['border_radius']) ? 'border-radius: ' . htmlspecialchars((string)$settings['border_radius'], ENT_QUOTES, 'UTF-8') . ';' : '';
        $boxShadow = !empty($settings['box_shadow']) ? 'box-shadow: ' . htmlspecialchars((string)$settings['box_shadow'], ENT_QUOTES, 'UTF-8') . ';' : '';
        $opacity = (isset($settings['opacity']) && (string)$settings['opacity'] !== '' && (string)$settings['opacity'] !== '1')
            ? 'opacity: ' . htmlspecialchars((string)$settings['opacity'], ENT_QUOTES, 'UTF-8') . ';'
            : '';
        $extraStyles = array_filter([$border, $borderRadius, $boxShadow, $opacity]);
        $extraStylesStr = !empty($extraStyles) ? ' ' . implode(' ', $extraStyles) : '';

        $gridTemplate = $presetConfig['template'];
        $tabletTemplate = $presetConfig['tablet'];
        $mobileTemplate = $presetConfig['mobile'];

        $numCols = $presetConfig['columns'];
        $colsOutput = [];
        for ($i = 0; $i < $numCols; $i++) {
            $colContent = $columnsHtml[$i] ?? '';
            $colsOutput[] = "<div class=\"ds-grid-col ds-grid-col-{$i}\">{$colContent}</div>";
        }
        $innerHtml = implode("\n", $colsOutput);

        $containerStyle = $isBoxed
            ? "max-width: {$maxWidth}; margin-left: auto; margin-right: auto; width: 100%;"
            : "width: 100%;";

        return <<<HTML
<div id="{$sectionId}" class="ds-section-wrapper {$customClass}" style="background: {$background}; width: 100%; margin: {$margin};{$extraStylesStr}">
    <style>
        #{$sectionId} .ds-section-grid {
            display: grid;
            grid-template-columns: {$gridTemplate};
            gap: {$gap};
            align-items: {$alignItems};
            justify-content: {$justifyContent};
            box-sizing: border-box;
            padding: {$padding};
            {$containerStyle}
        }
        #{$sectionId} .ds-grid-col {
            min-width: 0;
            box-sizing: border-box;
        }
        @media (max-width: 900px) {
            #{$sectionId} .ds-section-grid {
                grid-template-columns: {$tabletTemplate} !important;
                gap: 16px !important;
            }
        }
        @media (max-width: 640px) {
            #{$sectionId} .ds-section-grid {
                grid-template-columns: {$mobileTemplate} !important;
                gap: 12px !important;
                padding-left: 12px !important;
                padding-right: 12px !important;
            }
        }
        .ds-mobile-mode #{$sectionId} .ds-section-grid,
        .ds-is-mobile #{$sectionId} .ds-section-grid,
        .ds-device-375 #{$sectionId} .ds-section-grid {
            grid-template-columns: {$mobileTemplate} !important;
            gap: 12px !important;
            padding-left: 12px !important;
            padding-right: 12px !important;
        }
    </style>
    <div class="ds-section-grid">
        {$innerHtml}
    </div>
</div>
HTML;
    }
}
