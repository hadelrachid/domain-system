<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\flextheme\Services;

use DomainSystem\Core\Theme\Parts\Contracts\BlockRegistryInterface;
use DomainSystem\Core\Theme\Parts\Contracts\DesignTokensInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ElementStyleRendererInterface;
use DomainSystem\Core\Theme\Parts\Contracts\LayoutGridInterface;
use DomainSystem\Core\Theme\Parts\Contracts\MobileToggleStyleInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartAssociationInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartCompilerInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartRepositoryInterface;
use DomainSystem\Core\Theme\Parts\Models\ThemePart;
use DomainSystem\Core\Theme\Parts\Services\ThemePartRenderer;
use DomainSystem\Core\Theme\Parts\Styles\ElementStyleRenderer;
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
        BlockRegistryInterface $blockRegistry,
        LayoutGridInterface $layoutGrid,
        DesignTokensInterface $tokens,
        ?ElementStyleRendererInterface $styleRenderer = null,
        ?ThemePartCompilerInterface $compiler = null
    ) {
        $this->compiler = $compiler ?? new ThemePartRenderer(
            new class implements ThemePartRepositoryInterface {
                public function all(): array { return []; }
                public function findByType(string $type): array { return []; }
                public function findById(string $id): ?ThemePartInterface { return null; }
                public function findDefaultByType(string $type): ?ThemePartInterface { return null; }
                public function save(ThemePartInterface $part): bool { return false; }
                public function delete(string $id): bool { return false; }
            },
            new class implements ThemePartAssociationInterface {
                public function assignPart(string $themeFolder, string $partType, ?string $partId): bool { return false; }
                public function getAssignedPartId(string $themeFolder, string $partType): ?string { return null; }
                public function setGlobalDefault(string $partType, ?string $partId): bool { return false; }
                public function getGlobalDefault(string $partType): ?string { return null; }
                public function assignTemplateToSection(string $themeFolder, string $sectionId, ?string $templateSlug): bool { return false; }
                public function getAssignedTemplateForSection(string $themeFolder, string $sectionId): ?string { return null; }
                public function removePartReferences(string $partId): bool { return false; }
                public function getThemeAssignments(string $themeFolder): array { return []; }
                public function getAllAssignments(): array { return []; }
            },
            $layoutGrid,
            $blockRegistry,
            $tokens,
            null,
            $styleRenderer ?? new ElementStyleRenderer()
        );
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
