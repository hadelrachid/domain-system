<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Services;

use DomainSystem\Core\Theme\Parts\Contracts\BlockRegistryInterface;
use DomainSystem\Core\Theme\Parts\Contracts\DesignTokensInterface;
use DomainSystem\Core\Theme\Parts\Contracts\LayoutGridInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartAssociationInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartRendererInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartRepositoryInterface;

/**
 * Orquestrador de Renderização de Partes de Tema (Header, Footer, Seções)
 * com compilação de blocos, injeção de Design Tokens e Fallback Gracioso.
 */
class ThemePartRenderer implements ThemePartRendererInterface
{
    private ThemePartRepositoryInterface $partRepo;
    private ThemePartAssociationInterface $partAssoc;
    private LayoutGridInterface $gridRenderer;
    private BlockRegistryInterface $blockRegistry;
    private DesignTokensInterface $designTokens;
    private ?string $baseRoot;

    public function __construct(
        ThemePartRepositoryInterface $partRepo,
        ThemePartAssociationInterface $partAssoc,
        LayoutGridInterface $gridRenderer,
        BlockRegistryInterface $blockRegistry,
        DesignTokensInterface $designTokens,
        ?string $baseRoot = null
    ) {
        $this->partRepo = $partRepo;
        $this->partAssoc = $partAssoc;
        $this->gridRenderer = $gridRenderer;
        $this->blockRegistry = $blockRegistry;
        $this->designTokens = $designTokens;
        $this->baseRoot = $baseRoot !== null ? rtrim($baseRoot, '/\\') : (defined('DOMAIN_SYSTEM_ROOT') ? DOMAIN_SYSTEM_ROOT : dirname(__DIR__, 5));
    }

    public function renderHeader(string $themeFolder, array $context = []): string
    {
        $partId = $this->partAssoc->getAssignedPartId($themeFolder, ThemePartInterface::TYPE_HEADER);
        if ($partId !== null) {
            $part = $this->partRepo->findById($partId);
            if ($part !== null && $part->getType() === ThemePartInterface::TYPE_HEADER) {
                return $this->compileDynamicPart($part, $context, true);
            }
        }

        // Fallback gracioso: Carrega o arquivo nativo do tema
        return $this->renderFileFallback($themeFolder, 'header', $context);
    }

    public function renderFooter(string $themeFolder, array $context = []): string
    {
        $partId = $this->partAssoc->getAssignedPartId($themeFolder, ThemePartInterface::TYPE_FOOTER);
        if ($partId !== null) {
            $part = $this->partRepo->findById($partId);
            if ($part !== null && $part->getType() === ThemePartInterface::TYPE_FOOTER) {
                return $this->compileDynamicPart($part, $context, false);
            }
        }

        // Fallback gracioso: Carrega o arquivo nativo do tema
        return $this->renderFileFallback($themeFolder, 'footer', $context);
    }

    public function renderSection(string $sectionId, array $context = []): string
    {
        $part = $this->partRepo->findById($sectionId);
        if ($part === null) {
            return "<!-- Section not found: " . htmlspecialchars($sectionId, ENT_QUOTES, 'UTF-8') . " -->";
        }

        return $this->compileDynamicPart($part, $context, false);
    }

    private function compileDynamicPart(ThemePartInterface $part, array $context, bool $injectDesignTokens): string
    {
        $preset = $part->getLayoutPreset();
        $settings = $part->getContainerSettings();
        $columnsData = $part->getColumnsData();

        $columnsHtml = [];
        foreach ($columnsData as $colIndex => $blocksList) {
            $colBlocksOutput = [];
            if (is_array($blocksList)) {
                foreach ($blocksList as $blockDef) {
                    if (!is_array($blockDef)) {
                        continue;
                    }
                    $blockType = (string)($blockDef['type'] ?? '');
                    $blockData = (array)($blockDef['data'] ?? []);
                    
                    // Se for bloco de nav_menu, garante o estilo do toggle configurado na parte
                    if ($blockType === 'nav_menu' && !isset($blockData['toggle_style'])) {
                        $blockData['toggle_style'] = $part->getMobileToggleStyle();
                    }

                    $block = $this->blockRegistry->get($blockType);
                    if ($block !== null) {
                        $colBlocksOutput[] = $block->render($blockData, $context);
                    }
                }
            }
            $columnsHtml[$colIndex] = implode("\n", $colBlocksOutput);
        }

        $gridHtml = $this->gridRenderer->renderSection($preset, $columnsHtml, $settings);

        // Se for Header, injeta as variáveis CSS dos Design Tokens no topo
        if ($injectDesignTokens) {
            $tokensCss = $this->designTokens->generateCssVariables();
            $tokensTag = "<style id=\"ds-design-tokens\">\n{$tokensCss}\n</style>\n";
            return $tokensTag . $gridHtml;
        }

        return $gridHtml;
    }

    private function renderFileFallback(string $themeFolder, string $partName, array $context): string
    {
        $themeDir = $this->resolveThemeDir($themeFolder);
        if ($themeDir === null) {
            return '';
        }

        $candidates = [
            $themeDir . '/partials/' . $partName . '.php',
            $themeDir . '/' . $partName . '.php',
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                $realCandidate = realpath($candidate);
                $realThemeDir = realpath($themeDir);
                if ($realCandidate !== false && $realThemeDir !== false && str_starts_with($realCandidate, $realThemeDir)) {
                    ob_start();
                    extract($context, EXTR_SKIP);
                    include $realCandidate;
                    return (string)ob_get_clean();
                }
            }
        }

        return '';
    }

    private function resolveThemeDir(string $themeFolder): ?string
    {
        // Se já for um caminho absoluto válido
        if (is_dir($themeFolder)) {
            return rtrim($themeFolder, '/\\');
        }

        $candidates = [
            $this->baseRoot . '/themes/' . $themeFolder,
            $this->baseRoot . '/src/Plugins/flextheme/themes/' . $themeFolder,
            $this->baseRoot . '/src/Plugins/flextheme/themes/default',
        ];

        foreach ($candidates as $candidate) {
            if (is_dir($candidate)) {
                return rtrim($candidate, '/\\');
            }
        }

        return null;
    }
}
