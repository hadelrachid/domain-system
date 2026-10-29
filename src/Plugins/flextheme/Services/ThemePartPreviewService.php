<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\flextheme\Services;

use DomainSystem\Core\Theme\Parts\Contracts\BlockRegistryInterface;
use DomainSystem\Core\Theme\Parts\Contracts\DesignTokensInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ElementStyleRendererInterface;
use DomainSystem\Core\Theme\Parts\Contracts\LayoutGridInterface;
use DomainSystem\Core\Theme\Parts\Contracts\MobileToggleStyleInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartCompilerInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartInterface;
use DomainSystem\Core\Theme\Parts\Models\ThemePart;
use DomainSystem\Core\Theme\Parts\Repositories\JsonThemePartAssociation;
use DomainSystem\Core\Theme\Parts\Repositories\JsonThemePartRepository;
use DomainSystem\Core\Theme\Parts\Services\ThemePartRenderer;
use DomainSystem\Core\Theme\Parts\Styles\ElementStyleRenderer;
use DomainSystem\Core\Theme\Parts\Layout\LayoutGridRenderer;
use DomainSystem\Core\Theme\Parts\Styles\DesignTokens;
use DomainSystem\Plugins\flextheme\Contracts\ThemePartPreviewServiceInterface;

/**
 * Serviço dedicado à pré-visualização ao vivo (Preview) no Studio do Flex Builder (SOLID - SRP / DIP).
 *
 * Delega integralmente a compilação da parte visual ao motor oficial (ThemePartCompilerInterface),
 * garantindo caminho único de compilação entre o Preview do Studio e a publicação no Frontend.
 */
class ThemePartPreviewService implements ThemePartPreviewServiceInterface
{
    private ThemePartCompilerInterface $compiler;

    public function __construct(
        ThemePartCompilerInterface|BlockRegistryInterface $compilerOrRegistry,
        ?LayoutGridInterface $layoutGrid = null,
        ?DesignTokensInterface $tokens = null,
        ?ElementStyleRendererInterface $styleRenderer = null,
        ?ThemePartCompilerInterface $compiler = null
    ) {
        if ($compilerOrRegistry instanceof ThemePartCompilerInterface) {
            $this->compiler = $compilerOrRegistry;
        } elseif ($compiler !== null) {
            $this->compiler = $compiler;
        } else {
            $this->compiler = new ThemePartRenderer(
                new JsonThemePartRepository(),
                new JsonThemePartAssociation(),
                $layoutGrid ?? new LayoutGridRenderer(),
                $compilerOrRegistry,
                $tokens ?? new DesignTokens(),
                null,
                $styleRenderer ?? new ElementStyleRenderer()
            );
        }
    }

    public function renderPreview(array $payload): string
    {
        $rawType = (string)($payload['type'] ?? ThemePartInterface::TYPE_SECTION);
        $validTypes = [ThemePartInterface::TYPE_HEADER, ThemePartInterface::TYPE_FOOTER, ThemePartInterface::TYPE_SECTION];
        $partType = in_array($rawType, $validTypes, true) ? $rawType : ThemePartInterface::TYPE_SECTION;

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

        return $this->compiler->compilePart(
            $ephemeralPart,
            [
                'preview'            => true,
                '_enforce_part_type' => $enforceTypeFilter,
            ],
            true,
            false
        );
    }
}
