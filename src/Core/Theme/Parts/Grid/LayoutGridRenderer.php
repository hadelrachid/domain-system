<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Grid;

use DomainSystem\Core\Theme\Parts\Contracts\LayoutGridInterface;

/**
 * Motor de Renderização de Grade e Divisórias (CSS Grid / Flexbox).
 */
class LayoutGridRenderer implements LayoutGridInterface
{
    private const PRESETS = [
        '1_col' => [
            'id'       => '1_col',
            'name'     => '1 Coluna (100%)',
            'columns'  => 1,
            'template' => '1fr',
            'tablet'   => '1fr',
            'mobile'   => '1fr',
        ],
        '2_cols_equal' => [
            'id'       => '2_cols_equal',
            'name'     => '2 Colunas Iguais (50% / 50%)',
            'columns'  => 2,
            'template' => '1fr 1fr',
            'tablet'   => '1fr 1fr',
            'mobile'   => '1fr',
        ],
        '2_cols_split' => [
            'id'       => '2_cols_split',
            'name'     => '2 Colunas Pro (30% / 70%)',
            'columns'  => 2,
            'template' => 'minmax(220px, 1fr) 2.5fr',
            'tablet'   => '1fr 1fr',
            'mobile'   => '1fr',
        ],
        '3_cols_header' => [
            'id'       => '3_cols_header',
            'name'     => '3 Colunas Header Pro (25% / 50% / 25%)',
            'columns'  => 3,
            'template' => '1fr 2fr 1fr',
            'tablet'   => '1fr 1fr',
            'mobile'   => '1fr',
        ],
        '3_cols_equal' => [
            'id'       => '3_cols_equal',
            'name'     => '3 Colunas Iguais (33% / 33% / 33%)',
            'columns'  => 3,
            'template' => '1fr 1fr 1fr',
            'tablet'   => '1fr 1fr',
            'mobile'   => '1fr',
        ],
        '4_cols_footer' => [
            'id'       => '4_cols_footer',
            'name'     => '4 Colunas Footer Pro (25% cada)',
            'columns'  => 4,
            'template' => '1fr 1fr 1fr 1fr',
            'tablet'   => '1fr 1fr',
            'mobile'   => '1fr',
        ],
    ];

    public function getPresets(): array
    {
        return self::PRESETS;
    }

    public function renderSection(string $preset, array $columnsHtml, array $settings = []): string
    {
        $presetConfig = self::PRESETS[$preset] ?? self::PRESETS['1_col'];
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
        $padding = (string)($settings['padding'] ?? '20px 16px');
        $background = (string)($settings['background'] ?? 'transparent');
        $customClass = (string)($settings['custom_class'] ?? '');

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
<div id="{$sectionId}" class="ds-section-wrapper {$customClass}" style="background: {$background}; width: 100%;">
    <style>
        #{$sectionId} .ds-section-grid {
            display: grid;
            grid-template-columns: {$gridTemplate};
            gap: {$gap};
            align-items: {$alignItems};
            box-sizing: border-box;
            padding: {$padding};
            {$containerStyle}
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
    </style>
    <div class="ds-section-grid">
        {$innerHtml}
    </div>
</div>
HTML;
    }
}
