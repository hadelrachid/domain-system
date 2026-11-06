<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Grid;

use DomainSystem\Core\Theme\Parts\Contracts\ElementStyleRendererInterface;
use DomainSystem\Core\Theme\Parts\Contracts\LayoutGridInterface;
use DomainSystem\Core\Theme\Parts\Contracts\Layout\LayoutPresetRegistryInterface;
use DomainSystem\Core\Theme\Parts\Styles\ElementStyleRenderer;

/**
 * Motor de Renderização de Grade e Divisórias (CSS Grid / Flexbox) (SOLID - OCP / DIP).
 * 
 * Totalmente extensível via LayoutPresetRegistryInterface: novos layouts podem ser adicionados
 * por plugins ou código de temas sem alterar o motor interno (Open-Closed Principle).
 */
class LayoutGridRenderer implements LayoutGridInterface
{
    private LayoutPresetRegistryInterface $presetRegistry;
    private ElementStyleRendererInterface $styleRenderer;

    public function __construct(
        ?LayoutPresetRegistryInterface $presetRegistry = null,
        ?ElementStyleRendererInterface $styleRenderer = null
    ) {
        $this->presetRegistry = $presetRegistry ?? DefaultLayoutPresetsFactory::create();
        $this->styleRenderer = $styleRenderer ?? new ElementStyleRenderer();
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
        $maxWidth = $this->styleRenderer->sanitizeCssValue((string)($settings['max_width'] ?? '1280px'));
        if ($maxWidth === '') {
            $maxWidth = '1280px';
        }

        $rawGap = (string)($settings['gap'] ?? '24px');
        $mappedGap = match ($rawGap) {
            'none'   => '0px',
            'small'  => '12px',
            'medium' => '24px',
            'large'  => '40px',
            default  => $rawGap !== '' ? $rawGap : '24px',
        };
        $gap = $this->styleRenderer->sanitizeCssValue($mappedGap);
        if ($gap === '') {
            $gap = '24px';
        }

        $alignItems = $this->styleRenderer->sanitizeCssValue((string)($settings['align_items'] ?? 'center'));
        if ($alignItems === '') {
            $alignItems = 'center';
        }

        $justifyContent = $this->styleRenderer->sanitizeCssValue((string)($settings['justify_content'] ?? 'space-between'));
        if ($justifyContent === '') {
            $justifyContent = 'space-between';
        }

        $padding = $this->styleRenderer->sanitizeCssValue((string)($settings['padding'] ?? '20px 16px'));
        if ($padding === '') {
            $padding = '20px 16px';
        }

        $margin = $this->styleRenderer->sanitizeCssValue((string)($settings['margin'] ?? '0px'));
        if ($margin === '') {
            $margin = '0px';
        }

        $background = $this->styleRenderer->sanitizeCssValue((string)($settings['background'] ?? 'transparent'));
        if ($background === '') {
            $background = 'transparent';
        }

        $customClass = preg_replace('/[^a-zA-Z0-9_\-\s]/', '', (string)($settings['custom_class'] ?? '')) ?? '';
        $combinedClasses = trim("ds-section-wrapper {$customClass}");

        // Estilos Visuais Avançados da Seção (Bordas, Sombras, Opacidade)
        $borderVal = !empty($settings['border']) ? $this->styleRenderer->sanitizeCssValue((string)$settings['border']) : '';
        $borderRadiusVal = !empty($settings['border_radius']) ? $this->styleRenderer->sanitizeCssValue((string)$settings['border_radius']) : '';
        $boxShadowVal = !empty($settings['box_shadow']) ? $this->styleRenderer->sanitizeCssValue((string)$settings['box_shadow']) : '';
        $opacityVal = (isset($settings['opacity']) && (string)$settings['opacity'] !== '' && (string)$settings['opacity'] !== '1')
            ? $this->styleRenderer->sanitizeCssValue((string)$settings['opacity'])
            : '';

        $border = $borderVal !== '' ? "border: {$borderVal};" : '';
        $borderRadius = $borderRadiusVal !== '' ? "border-radius: {$borderRadiusVal};" : '';
        $boxShadow = $boxShadowVal !== '' ? "box-shadow: {$boxShadowVal};" : '';
        $opacity = $opacityVal !== '' ? "opacity: {$opacityVal};" : '';

        $extraStyles = array_filter([$border, $borderRadius, $boxShadow, $opacity]);

        // Cores Tipográficas & Formatação de Texto da Seção / Container (Cascading via Contrato)
        $rawTextColor = (string)($settings['text_color'] ?? $settings['color'] ?? '');
        $secTextColor = $rawTextColor !== '' ? $this->styleRenderer->sanitizeCssValue($rawTextColor) : '';
        $secHeadingColor = !empty($settings['heading_color']) ? $this->styleRenderer->sanitizeCssValue((string)$settings['heading_color']) : '';
        $secLinkColor = !empty($settings['link_color']) ? $this->styleRenderer->sanitizeCssValue((string)$settings['link_color']) : '';
        $secLinkHoverColor = !empty($settings['link_hover_color']) ? $this->styleRenderer->sanitizeCssValue((string)$settings['link_hover_color']) : '';
        $secFontFamily = !empty($settings['font_family']) ? $this->styleRenderer->sanitizeCssValue((string)$settings['font_family']) : '';
        $secFontSize = !empty($settings['font_size']) ? $this->styleRenderer->sanitizeCssValue((string)$settings['font_size']) : '';
        $secFontWeight = !empty($settings['font_weight']) ? $this->styleRenderer->sanitizeCssValue((string)$settings['font_weight']) : '';
        $secLineHeight = !empty($settings['line_height']) ? $this->styleRenderer->sanitizeCssValue((string)$settings['line_height']) : '';
        $secLetterSpacing = !empty($settings['letter_spacing']) ? $this->styleRenderer->sanitizeCssValue((string)$settings['letter_spacing']) : '';
        $secTextTransform = !empty($settings['text_transform']) ? $this->styleRenderer->sanitizeCssValue((string)$settings['text_transform']) : '';
        $secTextAlign = !empty($settings['text_align']) ? $this->styleRenderer->sanitizeCssValue((string)$settings['text_align']) : '';

        if ($secTextColor !== '') {
            $extraStyles[] = "color: {$secTextColor};";
            $extraStyles[] = "--ds-local-text-color: {$secTextColor};";
        }
        if ($secHeadingColor !== '') {
            $extraStyles[] = "--ds-local-heading-color: {$secHeadingColor};";
        }
        if ($secLinkColor !== '') {
            $extraStyles[] = "--ds-local-link-color: {$secLinkColor};";
        }
        if ($secLinkHoverColor !== '') {
            $extraStyles[] = "--ds-local-link-hover-color: {$secLinkHoverColor};";
        }
        if ($secFontFamily !== '') {
            $resolvedSecFont = match ($secFontFamily) {
                'primary'   => 'var(--font-primary, sans-serif)',
                'secondary' => 'var(--font-secondary, monospace)',
                'tertiary'  => 'var(--font-tertiary, sans-serif)',
                default     => $secFontFamily,
            };
            $extraStyles[] = "font-family: {$resolvedSecFont};";
        }
        if ($secFontSize !== '') {
            $extraStyles[] = "font-size: {$secFontSize};";
        }
        if ($secFontWeight !== '') {
            $extraStyles[] = "font-weight: {$secFontWeight};";
        }
        if ($secLineHeight !== '') {
            $extraStyles[] = "line-height: {$secLineHeight};";
        }
        if ($secLetterSpacing !== '') {
            $extraStyles[] = "letter-spacing: {$secLetterSpacing};";
        }
        if ($secTextTransform !== '') {
            $extraStyles[] = "text-transform: {$secTextTransform};";
        }
        if ($secTextAlign !== '') {
            $extraStyles[] = "text-align: {$secTextAlign};";
        }

        $extraStylesStr = !empty($extraStyles) ? ' ' . implode(' ', $extraStyles) : '';

        $secCascadingCss = '';
        if ($secHeadingColor !== '') {
            $secCascadingCss .= "\n        #{$sectionId} h1, #{$sectionId} h2, #{$sectionId} h3, #{$sectionId} h4, #{$sectionId} h5, #{$sectionId} h6, #{$sectionId} .ds-block-heading, #{$sectionId} .ds-logo-text, #{$sectionId} .ds-card-title a { color: {$secHeadingColor}; }";
        }
        if ($secTextColor !== '') {
            $secCascadingCss .= "\n        #{$sectionId} p, #{$sectionId} .ds-block-paragraph, #{$sectionId} .ds-block-copyright, #{$sectionId} .ds-card-excerpt { color: {$secTextColor}; }";
        }
        if ($secLinkColor !== '') {
            $secCascadingCss .= "\n        #{$sectionId} a:not(.ds-block-btn):not(.ds-block-cta), #{$sectionId} .ds-nav-link, #{$sectionId} .ds-card-readmore { color: {$secLinkColor}; }";
        }
        if ($secLinkHoverColor !== '') {
            $secCascadingCss .= "\n        #{$sectionId} a:not(.ds-block-btn):not(.ds-block-cta):hover, #{$sectionId} .ds-nav-link:hover, #{$sectionId} .ds-card-readmore:hover { color: {$secLinkHoverColor} !important; }";
        }

        $baseDesktopTemplate = $presetConfig['template'];
        $baseTabletTemplate = $presetConfig['tablet'];
        $baseMobileTemplate = $presetConfig['mobile'];

        $numCols = (int)$presetConfig['columns'];
        $colHiddenMap = is_array($settings['_col_hidden'] ?? null) ? $settings['_col_hidden'] : [];

        $gridTemplate = $this->resolveResponsiveGridTemplate($baseDesktopTemplate, $numCols, $colHiddenMap, 'desktop');
        $laptopTemplate = $this->resolveResponsiveGridTemplate($baseDesktopTemplate, $numCols, $colHiddenMap, 'laptop');
        $tabletTemplate = $this->resolveResponsiveGridTemplate($baseTabletTemplate, $numCols, $colHiddenMap, 'tablet');
        $mobileTemplate = $this->resolveResponsiveGridTemplate($baseMobileTemplate, $numCols, $colHiddenMap, 'mobile');

        $colsOutput = [];
        for ($i = 0; $i < $numCols; $i++) {
            $colContent = $columnsHtml[$i] ?? '';
            $colVisClasses = [];
            if (!empty($colHiddenMap[$i]['desktop'])) {
                $colVisClasses[] = 'ds-hide-desktop';
            }
            if (!empty($colHiddenMap[$i]['laptop'])) {
                $colVisClasses[] = 'ds-hide-laptop';
            }
            if (!empty($colHiddenMap[$i]['tablet'])) {
                $colVisClasses[] = 'ds-hide-tablet';
            }
            if (!empty($colHiddenMap[$i]['mobile'])) {
                $colVisClasses[] = 'ds-hide-mobile';
            }
            $colClassStr = trim("ds-grid-col ds-grid-col-{$i} " . implode(' ', $colVisClasses));
            $colsOutput[] = "<div class=\"{$colClassStr}\">{$colContent}</div>";
        }
        $innerHtml = implode("\n", $colsOutput);

        $containerStyle = $isBoxed
            ? "max-width: {$maxWidth}; margin-left: auto; margin-right: auto; width: 100%;"
            : "width: 100%;";

        return <<<HTML
<div id="{$sectionId}" class="{$combinedClasses}" style="background: {$background}; width: 100%; margin: {$margin};{$extraStylesStr}">
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
        }{$secCascadingCss}
        @media (min-width: 769px) and (max-width: 1024px) {
            body:not(.ds-live-preview) #{$sectionId} .ds-section-grid {
                grid-template-columns: {$laptopTemplate} !important;
            }
        }
        @media (min-width: 481px) and (max-width: 768px) {
            body:not(.ds-live-preview) #{$sectionId} .ds-section-grid {
                grid-template-columns: {$tabletTemplate} !important;
                gap: 16px !important;
            }
        }
        @media (max-width: 480px) {
            body:not(.ds-live-preview) #{$sectionId} .ds-section-grid {
                grid-template-columns: {$mobileTemplate} !important;
                gap: 12px !important;
                padding-left: 12px !important;
                padding-right: 12px !important;
            }
        }
        .ds-laptop-mode #{$sectionId} .ds-section-grid,
        .ds-is-laptop #{$sectionId} .ds-section-grid,
        .ds-device-1024 #{$sectionId} .ds-section-grid {
            grid-template-columns: {$laptopTemplate} !important;
        }
        .ds-tablet-mode #{$sectionId} .ds-section-grid,
        .ds-is-tablet #{$sectionId} .ds-section-grid,
        .ds-device-768 #{$sectionId} .ds-section-grid {
            grid-template-columns: {$tabletTemplate} !important;
            gap: 16px !important;
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

    /**
     * Reajusta dinamicamente as colunas do CSS Grid quando todos os elementos de uma ou mais colunas
     * estão ocultos na resolução alvo, redistribuindo 100% da largura entre as colunas visíveis restantes.
     *
     * @param array<int, array<string, bool>> $colHiddenMap
     */
    private function resolveResponsiveGridTemplate(
        string $baseTemplate,
        int $numCols,
        array $colHiddenMap,
        string $resolution
    ): string {
        if ($numCols <= 1 || empty($colHiddenMap)) {
            return $baseTemplate;
        }

        $visibleIndices = [];
        for ($i = 0; $i < $numCols; $i++) {
            if (empty($colHiddenMap[$i][$resolution])) {
                $visibleIndices[] = $i;
            }
        }

        $visibleCount = count($visibleIndices);
        if ($visibleCount === $numCols || $visibleCount === 0) {
            return $baseTemplate;
        }

        if ($visibleCount === 1) {
            return '1fr';
        }

        $tracks = $this->parseGridTracks($baseTemplate);
        if (count($tracks) === $numCols) {
            $remainingTracks = [];
            foreach ($visibleIndices as $idx) {
                $remainingTracks[] = $tracks[$idx];
            }
            return implode(' ', $remainingTracks);
        }

        return $baseTemplate;
    }

    /**
     * Divide um template CSS Grid em suas tracks individuais (suportando repeat() e funções como minmax()).
     *
     * @return array<int, string>
     */
    private function parseGridTracks(string $template): array
    {
        $trimmed = trim($template);
        if (preg_match('/^repeat\(\s*(\d+)\s*,\s*(.+)\)$/i', $trimmed, $m)) {
            $count = max(1, (int)$m[1]);
            return array_fill(0, $count, trim($m[2]));
        }

        $tracks = [];
        $current = '';
        $depth = 0;
        $len = strlen($trimmed);

        for ($i = 0; $i < $len; $i++) {
            $ch = $trimmed[$i];
            if ($ch === '(') {
                $depth++;
                $current .= $ch;
            } elseif ($ch === ')') {
                $depth = max(0, $depth - 1);
                $current .= $ch;
            } elseif (ctype_space($ch) && $depth === 0) {
                if ($current !== '') {
                    $tracks[] = $current;
                    $current = '';
                }
            } else {
                $current .= $ch;
            }
        }

        if ($current !== '') {
            $tracks[] = $current;
        }

        return $tracks;
    }
}
