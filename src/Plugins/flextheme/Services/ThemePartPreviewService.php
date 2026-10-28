<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\flextheme\Services;

use DomainSystem\Core\Theme\Parts\Contracts\BlockRegistryInterface;
use DomainSystem\Core\Theme\Parts\Contracts\DesignTokensInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ElementStyleRendererInterface;
use DomainSystem\Core\Theme\Parts\Contracts\LayoutGridInterface;
use DomainSystem\Core\Theme\Parts\Contracts\MobileToggleStyleInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartInterface;
use DomainSystem\Core\Theme\Parts\Models\ThemePart;
use DomainSystem\Core\Theme\Parts\Services\ThemePartRenderer;
use DomainSystem\Core\Theme\Parts\Styles\ElementStyleRenderer;
use DomainSystem\Plugins\flextheme\Contracts\ThemePartPreviewServiceInterface;

/**
 * Serviço dedicado à pré-visualização ao vivo (Preview) no Studio do Flex Builder (SOLID - SRP).
 *
 * Elimina a duplicação de regras de compilação entre o Preview e o Frontend,
 * garantindo que blocos restritos (ex: post_cards_grid em header) e classes
 * responsivas (.ds-hide-desktop, .ds-hide-tablet, .ds-hide-mobile) funcionem de forma idêntica.
 */
class ThemePartPreviewService implements ThemePartPreviewServiceInterface
{
    private BlockRegistryInterface $blockRegistry;
    private LayoutGridInterface $layoutGrid;
    private DesignTokensInterface $tokens;
    private ElementStyleRendererInterface $styleRenderer;

    public function __construct(
        BlockRegistryInterface $blockRegistry,
        LayoutGridInterface $layoutGrid,
        DesignTokensInterface $tokens,
        ?ElementStyleRendererInterface $styleRenderer = null
    ) {
        $this->blockRegistry = $blockRegistry;
        $this->layoutGrid = $layoutGrid;
        $this->tokens = $tokens;
        $this->styleRenderer = $styleRenderer ?? new ElementStyleRenderer();
    }

    public function renderPreview(array $payload): string
    {
        $rawType = (string)($payload['type'] ?? ThemePartInterface::TYPE_SECTION);
        $validTypes = [ThemePartInterface::TYPE_HEADER, ThemePartInterface::TYPE_FOOTER, ThemePartInterface::TYPE_SECTION];
        $partType = in_array($rawType, $validTypes, true) ? $rawType : ThemePartInterface::TYPE_SECTION;

        // Se o payload não informar 'type' explicitamente, permitimos validação baseada no tipo ou sem restrição caso omitido
        $enforceTypeFilter = isset($payload['type']) && in_array($rawType, $validTypes, true);

        $preset = (string)($payload['layout_preset'] ?? '1_col');
        $settings = (array)($payload['container_settings'] ?? []);
        $columnsData = (array)($payload['columns_data'] ?? []);
        $mobileToggleStyle = (string)($payload['mobile_toggle_style'] ?? MobileToggleStyleInterface::STYLE_CLASSIC);
        $mobileToggleAlign = (string)($payload['mobile_toggle_align'] ?? 'right');
        $sections = (array)($payload['sections'] ?? []);

        $ephemeralPart = new ThemePart(
            'preview_part',
            $partType,
            (string)($payload['title'] ?? 'Preview'),
            $preset,
            $settings,
            $columnsData,
            $mobileToggleStyle,
            false,
            null,
            null,
            $sections,
            $mobileToggleAlign
        );

        $sectionsOutput = [];
        foreach ($ephemeralPart->getSections() as $sec) {
            $secPreset = (string)($sec['layout_preset'] ?? '1_col');
            $secSettings = (array)($sec['container_settings'] ?? []);
            $secColsData = (array)($sec['columns_data'] ?? []);

            $colsHtml = [];
            $colHiddenMap = [];
            foreach ($secColsData as $colIndex => $blocksList) {
                $colBlocks = [];
                $validBlocksCount = 0;
                $hiddenCounts = [
                    'desktop' => 0,
                    'laptop'  => 0,
                    'tablet'  => 0,
                    'mobile'  => 0,
                ];

                if (is_array($blocksList)) {
                    foreach ($blocksList as $bDef) {
                        if (!is_array($bDef)) {
                            continue;
                        }
                        $bType = (string)($bDef['type'] ?? '');
                        $bData = (array)($bDef['data'] ?? []);

                        if ($bType === 'nav_menu') {
                            if (!isset($bData['toggle_style']) || empty($bData['toggle_style'])) {
                                $bData['toggle_style'] = $ephemeralPart->getMobileToggleStyle();
                            }
                            if (!isset($bData['toggle_align']) || empty($bData['toggle_align'])) {
                                $bData['toggle_align'] = $ephemeralPart->getMobileToggleAlign();
                            }
                        }

                        $block = $this->blockRegistry->get($bType);
                        if ($block !== null) {
                            if ($enforceTypeFilter) {
                                $allowedTypes = $block->getAllowedPartTypes();
                                if (!empty($allowedTypes) && !in_array($ephemeralPart->getType(), $allowedTypes, true)) {
                                    continue;
                                }
                            }

                            $renderedHtml = $block->render($bData, ['preview' => true]);
                            $elementStyles = $this->styleRenderer->renderStyleString($bData);
                            $visibilityClasses = $this->styleRenderer->getResponsiveVisibilityClasses($bData);

                            $validBlocksCount++;
                            if (str_contains($visibilityClasses, 'ds-hide-desktop')) {
                                $hiddenCounts['desktop']++;
                            }
                            if (str_contains($visibilityClasses, 'ds-hide-laptop')) {
                                $hiddenCounts['laptop']++;
                            }
                            if (str_contains($visibilityClasses, 'ds-hide-tablet')) {
                                $hiddenCounts['tablet']++;
                            }
                            if (str_contains($visibilityClasses, 'ds-hide-mobile')) {
                                $hiddenCounts['mobile']++;
                            }

                            if ($elementStyles !== '' || $visibilityClasses !== '') {
                                $wrapperClass = trim("ds-element-wrapper {$visibilityClasses}");
                                $styleAttr = $elementStyles !== '' ? " style=\"{$elementStyles}\"" : '';
                                $renderedHtml = "<div class=\"{$wrapperClass}\"{$styleAttr}>{$renderedHtml}</div>";
                            }
                            $colBlocks[] = $renderedHtml;
                        }
                    }
                }
                $cIdx = (int)$colIndex;
                $colsHtml[$cIdx] = implode("\n", $colBlocks);
                if ($validBlocksCount > 0) {
                    $colHiddenMap[$cIdx] = [
                        'desktop' => $hiddenCounts['desktop'] === $validBlocksCount,
                        'laptop'  => $hiddenCounts['laptop'] === $validBlocksCount,
                        'tablet'  => $hiddenCounts['tablet'] === $validBlocksCount,
                        'mobile'  => $hiddenCounts['mobile'] === $validBlocksCount,
                    ];
                }
            }
            $secSettings['_col_hidden'] = $colHiddenMap;
            $sectionsOutput[] = $this->layoutGrid->renderSection($secPreset, $colsHtml, $secSettings);
        }

        $renderedGrid = implode("\n", $sectionsOutput);
        $runtimeCss = ThemePartRenderer::getRuntimeCss();
        $cssVars = '<style id="ds-design-tokens">' . $this->tokens->generateCssVariables() . '</style>' . "\n" .
            '<style id="ds-builder-runtime">' . $runtimeCss . '</style>';

        return $cssVars . $renderedGrid;
    }
}
