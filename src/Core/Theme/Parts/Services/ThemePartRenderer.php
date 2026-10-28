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
use Throwable;

/**
 * Orquestrador de Renderização de Partes de Tema (Header, Footer, Seções)
 * com compilação de blocos, injeção de Design Tokens, Visibilidade Responsiva e Fallback Gracioso.
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
    /** @var array<int, string> Pilha de proteção contra recursão infinita (ex: Shortcode [section] auto-referenciado) */
    private array $renderingStack = [];
    private const MAX_RENDER_DEPTH = 10;

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

    public function renderTemplateSection(string $themeFolder, string $templateName, array $context = []): string
    {
        $cleanTemplate = str_replace(['templates/', '.php'], '', $templateName);
        $partId = $this->partAssoc->getAssignedPartId($themeFolder, 'template:' . $cleanTemplate);
        if ($partId === null || trim($partId) === '') {
            return '';
        }

        $part = $this->partRepo->findById($partId);
        if ($part === null) {
            return '';
        }

        return $this->compileDynamicPart($part, $context, false);
    }

    /**
     * Compila uma instância de ThemePart (persistida ou efêmera de preview) aplicando todas as regras
     * de contrato (getAllowedPartTypes), estilos de elemento e classes responsivas.
     *
     * @param array<string, mixed> $context
     */
    public function compilePart(ThemePartInterface $part, array $context = [], bool $injectDesignTokens = true, bool $wrapWithSemanticTag = true): string
    {
        return $this->compileDynamicPart($part, $context, $injectDesignTokens, $wrapWithSemanticTag);
    }

    private function compileDynamicPart(
        ThemePartInterface $part,
        array $context,
        bool $injectDesignTokens,
        bool $wrapWithSemanticTag = true
    ): string {
        $partId = trim($part->getId());
        if ($partId !== '') {
            if (in_array($partId, $this->renderingStack, true) || count($this->renderingStack) >= self::MAX_RENDER_DEPTH) {
                $safeId = htmlspecialchars($partId, ENT_QUOTES, 'UTF-8');
                return "<!-- Recursion guard triggered for part: {$safeId} -->";
            }
            $this->renderingStack[] = $partId;
        }

        try {
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
                                $visibilityClasses = $this->styleRenderer->getResponsiveVisibilityClasses($blockData);

                                if ($elementStyles !== '' || $visibilityClasses !== '') {
                                    $wrapperClass = trim("ds-element-wrapper {$visibilityClasses}");
                                    $styleAttr = $elementStyles !== '' ? " style=\"{$elementStyles}\"" : '';
                                    $renderedHtml = "<div class=\"{$wrapperClass}\"{$styleAttr}>{$renderedHtml}</div>";
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

            if ($wrapWithSemanticTag) {
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
            } else {
                $wrappedHtml = $allSectionsHtml;
            }

            // Injeta variáveis CSS, animações runtime e utilitários responsivos dos Design Tokens
            if ($injectDesignTokens || !self::$tokensInjected) {
                self::$tokensInjected = true;
                $tokensCss = $this->designTokens->generateCssVariables();
                $runtimeCss = self::getRuntimeCss();
                $tokensTag = "<style id=\"ds-design-tokens\">\n{$tokensCss}\n</style>\n<style id=\"ds-builder-runtime\">\n{$runtimeCss}\n</style>\n";
                return $tokensTag . $wrappedHtml;
            }

            return $wrappedHtml;
        } finally {
            if ($partId !== '') {
                array_pop($this->renderingStack);
            }
        }
    }

    public static function getRuntimeCss(): string
    {
        return <<<CSS
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
/* Visibilidade Responsiva de Elementos (4 Resoluções: Desktop, 1024px, 768px, 375px) */
@media (min-width: 1025px) {
    body:not(.ds-live-preview):not(.ds-is-laptop):not(.ds-is-tablet):not(.ds-is-mobile):not(.ds-mobile-mode) .ds-hide-desktop { display: none !important; }
}
@media (min-width: 769px) and (max-width: 1024px) {
    body:not(.ds-live-preview) .ds-hide-laptop { display: none !important; }
}
@media (min-width: 481px) and (max-width: 768px) {
    body:not(.ds-live-preview) .ds-hide-tablet { display: none !important; }
}
@media (max-width: 480px) {
    body:not(.ds-live-preview) .ds-hide-mobile { display: none !important; }
}
.ds-desktop-mode .ds-hide-desktop,
.ds-device-desktop .ds-hide-desktop,
.ds-device-1440 .ds-hide-desktop,
.ds-is-desktop .ds-hide-desktop { display: none !important; }
.ds-laptop-mode .ds-hide-laptop,
.ds-device-1024 .ds-hide-laptop,
.ds-is-laptop .ds-hide-laptop { display: none !important; }
.ds-tablet-mode .ds-hide-tablet,
.ds-device-768 .ds-hide-tablet,
.ds-is-tablet .ds-hide-tablet { display: none !important; }
.ds-mobile-mode .ds-hide-mobile,
.ds-device-375 .ds-hide-mobile,
.ds-is-mobile .ds-hide-mobile { display: none !important; }
CSS;
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
                    // Isolamento estrito de escopo: impede vazamento de $this para o arquivo de template
                    $isolatedInclude = static function (string $__file, array $__ctx): string {
                        ob_start();
                        try {
                            extract($__ctx, EXTR_SKIP);
                            include $__file;
                            return (string)ob_get_clean();
                        } catch (Throwable $e) {
                            if (ob_get_level() > 0) {
                                ob_end_clean();
                            }
                            throw $e;
                        }
                    };

                    return $isolatedInclude($realCandidate, $context);
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
