<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\flextheme\Services;

use DomainSystem\Core\Theme\Parts\Contracts\MobileToggleStyleInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartCompilerInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartInterface;
use DomainSystem\Core\Theme\Parts\Models\ThemePart;
use DomainSystem\Plugins\flextheme\Contracts\ThemePartPreviewServiceInterface;

/**
 * Serviço dedicado à pré-visualização ao vivo (Preview) no Studio do Flex Builder (SOLID - SRP / DIP).
 *
 * Delega integralmente a compilação da parte visual ao motor oficial (ThemePartCompilerInterface),
 * garantindo caminho único de compilação entre o Preview do Studio e a publicação no Frontend,
 * sem qualquer instanciação direta de classes concretas de infraestrutura.
 */
class ThemePartPreviewService implements ThemePartPreviewServiceInterface
{
    private ThemePartCompilerInterface $compiler;

    public function __construct(ThemePartCompilerInterface $compiler)
    {
        $this->compiler = $compiler;
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
