<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Services;

use DomainSystem\Core\Theme\Parts\Contracts\BlockRegistryInterface;
use DomainSystem\Core\Theme\Parts\Contracts\DesignTokensInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ElementStyleRendererInterface;
use DomainSystem\Core\Theme\Parts\Contracts\LayoutGridInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartAssociationInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartRendererInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartRepositoryInterface;
use DomainSystem\Core\Theme\Parts\Styles\ElementStyleRenderer;

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
    private ElementStyleRendererInterface $styleRenderer;
    private ?string $baseRoot;
    private static bool $tokensInjected = false;

    public static function resetInjectedState(): void
    {
        self::$tokensInjected = false;
    }

    public function __construct(
        ThemePartRepositoryInterface $partRepo,
        ThemePartAssociationInterface $partAssoc,
        LayoutGridInterface $gridRenderer,
        BlockRegistryInterface $blockRegistry,
        DesignTokensInterface $designTokens,
        ?string $baseRoot = null,
        ?ElementStyleRendererInterface $styleRenderer = null
    ) {
        $this->partRepo = $partRepo;
        $this->partAssoc = $partAssoc;
        $this->gridRenderer = $gridRenderer;
        $this->blockRegistry = $blockRegistry;
        $this->designTokens = $designTokens;
        $this->baseRoot = $baseRoot !== null ? rtrim($baseRoot, '/\\') : (defined('DOMAIN_SYSTEM_ROOT') ? DOMAIN_SYSTEM_ROOT : dirname(__DIR__, 5));
        $this->styleRenderer = $styleRenderer ?? new ElementStyleRenderer();
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
        $sections = $part->getSections();
        $sectionsOutput = [];

        foreach ($sections as $section) {
            $preset = (string)($section['layout_preset'] ?? '1_col');
            $settings = (array)($section['container_settings'] ?? []);
            $columnsData = (array)($section['columns_data'] ?? []);

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
                        
                        // Se for bloco de nav_menu, garante o estilo e alinhamento do toggle configurados na parte
                        if ($blockType === 'nav_menu') {
                            if (!isset($blockData['toggle_style']) || empty($blockData['toggle_style'])) {
                                $blockData['toggle_style'] = $part->getMobileToggleStyle();
                            }
                            if (!isset($blockData['toggle_align']) || empty($blockData['toggle_align'])) {
                                $blockData['toggle_align'] = $part->getMobileToggleAlign();
                            }
                        }

                        $block = $this->blockRegistry->get($blockType);
                        if ($block !== null) {
                            // Validação estrita por contrato: impede que blocos de conteúdo vazem para headers ou footers
                            $allowedTypes = $block->getAllowedPartTypes();
                            if (!empty($allowedTypes) && !in_array($part->getType(), $allowedTypes, true)) {
                                continue;
                            }

                            $renderedHtml = $block->render($blockData, $context);
                            $elementStyles = $this->styleRenderer->renderStyleString($blockData);
                            if ($elementStyles !== '') {
                                $renderedHtml = "<div class=\"ds-element-wrapper\" style=\"{$elementStyles}\">{$renderedHtml}</div>";
                            }
                            $colBlocksOutput[] = $renderedHtml;
                        }
                    }
                }
                $columnsHtml[(int)$colIndex] = implode("\n", $colBlocksOutput);
            }

            $sectionsOutput[] = $this->gridRenderer->renderSection($preset, $columnsHtml, $settings);
        }

        $allSectionsHtml = implode("\n", $sectionsOutput);

        $partType = $part->getType();
        $wrapperTag = 'section';
        $roleAttr = '';
        $typeClass = 'ds-theme-section';

        if ($partType === ThemePartInterface::TYPE_HEADER) {
            $wrapperTag = 'header';
            $roleAttr = ' role="banner"';
            $typeClass = 'ds-theme-header';
        } elseif ($partType === ThemePartInterface::TYPE_FOOTER) {
            $wrapperTag = 'footer';
            $roleAttr = ' role="contentinfo"';
            $typeClass = 'ds-theme-footer';
        }

        $wrappedHtml = "<{$wrapperTag} class=\"ds-part-wrapper {$typeClass}\"{$roleAttr}>\n{$allSectionsHtml}\n</{$wrapperTag}>";

        // Injeta variáveis CSS e animações runtime dos Design Tokens se solicitado ou se ainda não injetado
        if ($injectDesignTokens || !self::$tokensInjected) {
            self::$tokensInjected = true;
            $tokensCss = $this->designTokens->generateCssVariables();
            $runtimeCss = <<<CSS
@keyframes ds-bounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
@keyframes ds-shimmer { 0% { background-position: -200% 0; } 100% { background-position: 200% 0; } }
.ds-part-wrapper { position: relative; width: 100%; box-sizing: border-box; }
.ds-part-wrapper * { box-sizing: border-box; }
.ds-btn-grow { transition: transform 0.2s ease, box-shadow 0.2s ease !important; }
.ds-btn-grow:hover { transform: scale(1.05) !important; }
.ds-btn-lift { transition: transform 0.2s ease, box-shadow 0.2s ease !important; }
.ds-btn-lift:hover { transform: translateY(-3px) !important; box-shadow: 0 10px 22px rgba(0,0,0,0.35) !important; }
.ds-btn-glow { transition: box-shadow 0.25s ease, transform 0.2s ease !important; }
.ds-btn-glow:hover { box-shadow: 0 0 25px var(--color-primary, #38bdf8) !important; }
.ds-btn-bounce:hover { animation: ds-bounce 0.6s ease infinite alternate !important; }
.ds-btn-shimmer { position: relative; background-size: 200% auto !important; transition: background 0.3s ease !important; }
.ds-btn-shimmer:hover { animation: ds-shimmer 1.5s linear infinite !important; }
CSS;
            $tokensTag = "<style id=\"ds-design-tokens\">\n{$tokensCss}\n</style>\n<style id=\"ds-builder-runtime\">\n{$runtimeCss}\n</style>\n";
            return $tokensTag . $wrappedHtml;
        }

        return $wrappedHtml;
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
