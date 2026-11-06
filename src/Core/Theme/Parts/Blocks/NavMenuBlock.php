<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Blocks;

use DomainSystem\Core\Theme\Parts\Blocks\Traits\AllowedPartTypesTrait;
use DomainSystem\Core\Theme\Parts\Contracts\BlockInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ElementStyleRendererInterface;
use DomainSystem\Core\Theme\Parts\Contracts\HtmlSanitizerInterface;
use DomainSystem\Core\Theme\Parts\Contracts\MenuProviderInterface;
use DomainSystem\Core\Theme\Parts\Contracts\MenuTreeBuilderInterface;
use DomainSystem\Core\Theme\Parts\Contracts\MobileToggleStyleInterface;
use DomainSystem\Core\Theme\Parts\Contracts\PublicUrlResolverInterface;
use DomainSystem\Core\Theme\Parts\Styles\ElementStyleRenderer;
use DomainSystem\Core\Theme\Parts\Support\BaseUrlResolver;
use DomainSystem\Core\Theme\Parts\Support\MenuTreeBuilder;
use DomainSystem\Core\Theme\Parts\Support\SafeHtmlSanitizer;

/**
 * Bloco de Navegação e Menu Dinâmico com Suporte a Menus e Submenus Hierárquicos (Walker SOLID),
 * 8 Estilos de Toggle Mobile, Janela Lateral Customizável e Botões Mobile.
 */
class NavMenuBlock implements BlockInterface
{
    use AllowedPartTypesTrait;

    private ?MenuProviderInterface $menuProvider;
    /** @var (\Closure(): ?MenuProviderInterface)|null */
    private ?\Closure $menuProviderResolver;
    private ElementStyleRendererInterface $styleRenderer;
    private HtmlSanitizerInterface $htmlSanitizer;
    private PublicUrlResolverInterface $urlResolver;
    private MenuTreeBuilderInterface $treeBuilder;

    /**
     * @param MenuProviderInterface|null $menuProvider
     * @param (\Closure(): ?MenuProviderInterface)|null $menuProviderResolver
     * @param ElementStyleRendererInterface|null $styleRenderer
     * @param HtmlSanitizerInterface|null $htmlSanitizer
     * @param PublicUrlResolverInterface|null $urlResolver
     * @param MenuTreeBuilderInterface|null $treeBuilder
     */
    public function __construct(
        ?MenuProviderInterface $menuProvider = null,
        ?\Closure $menuProviderResolver = null,
        ?ElementStyleRendererInterface $styleRenderer = null,
        ?HtmlSanitizerInterface $htmlSanitizer = null,
        ?PublicUrlResolverInterface $urlResolver = null,
        ?MenuTreeBuilderInterface $treeBuilder = null
    ) {
        $this->menuProvider = $menuProvider;
        $this->menuProviderResolver = $menuProviderResolver;
        $this->styleRenderer = $styleRenderer ?? new ElementStyleRenderer();
        $this->htmlSanitizer = $htmlSanitizer ?? new SafeHtmlSanitizer();
        $this->urlResolver = $urlResolver ?? new BaseUrlResolver();
        $this->treeBuilder = $treeBuilder ?? new MenuTreeBuilder();
    }

    public function getType(): string
    {
        return 'nav_menu';
    }

    public function getName(): string
    {
        return 'Menu de Navegação';
    }

    public function getIcon(): string
    {
        return '🧭';
    }

    public function getCategory(): string
    {
        return 'navigation';
    }

    public function render(array $data = [], array $context = []): string
    {
        $baseUrl = $this->urlResolver->getBaseUrl();
        $location = (string)($data['menu_location'] ?? 'header');
        $rawToggleStyle = (string)($data['toggle_style'] ?? MobileToggleStyleInterface::STYLE_CLASSIC);
        $toggleStyle = preg_replace('/[^a-z0-9_-]/i', '', $rawToggleStyle) ?: MobileToggleStyleInterface::STYLE_CLASSIC;
        $align = (string)($data['align'] ?? 'center');
        $justifyNav = match ($align) {
            'left'  => 'flex-start',
            'right' => 'flex-end',
            default => 'center',
        };

        $toggleAlign = (string)($data['toggle_align'] ?? $data['align'] ?? 'right');
        $linkColor = $this->styleRenderer->sanitizeCssValue((string)($data['link_color'] ?? $data['text_color'] ?? $data['color'] ?? 'var(--ds-local-link-color, var(--ds-local-text-color, var(--color-text-main, #c5c6c7)))'));
        $activeColor = $this->styleRenderer->sanitizeCssValue((string)($data['active_color'] ?? $data['link_hover_color'] ?? 'var(--ds-local-link-hover-color, var(--color-primary, #38bdf8))'));
        $fontFamily = $this->styleRenderer->sanitizeCssValue(
            !empty($data['font_family']) ? (string)$data['font_family'] : 'var(--font-primary, inherit)'
        );
        $fontSize = $this->styleRenderer->sanitizeCssValue(
            !empty($data['font_size']) ? (string)$data['font_size'] : '0.95rem'
        );
        $fontWeight = $this->styleRenderer->sanitizeCssValue(
            !empty($data['font_weight']) ? (string)$data['font_weight'] : '500'
        );
        $textTransform = $this->styleRenderer->sanitizeCssValue(
            !empty($data['text_transform']) ? (string)$data['text_transform'] : 'none'
        );
        $hoverAnim = (string)($data['hover_animation'] ?? 'underline');
        $justifyToggle = match ($toggleAlign) {
            'left'   => 'flex-start',
            'center' => 'center',
            'right'  => 'flex-end',
            default  => 'flex-end',
        };

        // ==========================================
        // 1. CUSTOMIZAÇÃO DO BOTÃO TOGGLE HAMBURGUER MOBILE
        // ==========================================
        $toggleLabelText = htmlspecialchars(trim((string)($data['toggle_label'] ?? 'MENU')) ?: 'MENU', ENT_QUOTES, 'UTF-8');
        $toggleColor = $this->styleRenderer->sanitizeCssValue(
            !empty($data['toggle_color']) ? (string)$data['toggle_color'] : $activeColor
        );
        $toggleBgDefault = match ($toggleStyle) {
            MobileToggleStyleInterface::STYLE_BOXED => $activeColor,
            MobileToggleStyleInterface::STYLE_ROUNDED_GLOW,
            MobileToggleStyleInterface::STYLE_PILL  => 'rgba(56, 189, 248, 0.1)',
            default                                 => 'transparent',
        };
        $toggleBgColor = $this->styleRenderer->sanitizeCssValue(
            !empty($data['toggle_bg_color']) ? (string)$data['toggle_bg_color'] : $toggleBgDefault
        );
        if ($toggleStyle === MobileToggleStyleInterface::STYLE_BOXED && empty($data['toggle_color'])) {
            $toggleColor = 'var(--color-background, #0b0c10)';
        }
        $toggleBorderColor = $this->styleRenderer->sanitizeCssValue(
            !empty($data['toggle_border_color']) ? (string)$data['toggle_border_color'] : 'rgba(69, 243, 255, 0.28)'
        );
        $toggleHoverColor = $this->styleRenderer->sanitizeCssValue(
            !empty($data['toggle_hover_color']) ? (string)$data['toggle_hover_color'] : $toggleColor
        );
        $toggleHoverBgColor = $this->styleRenderer->sanitizeCssValue(
            !empty($data['toggle_hover_bg_color']) ? (string)$data['toggle_hover_bg_color'] : 'rgba(56, 189, 248, 0.18)'
        );
        $toggleRadiusDefault = match ($toggleStyle) {
            MobileToggleStyleInterface::STYLE_PILL,
            MobileToggleStyleInterface::STYLE_ROUNDED_GLOW => '999px',
            MobileToggleStyleInterface::STYLE_CYBER        => '2px',
            default                                        => '8px',
        };
        $toggleRadius = $this->styleRenderer->sanitizeCssValue(
            !empty($data['toggle_radius']) ? (string)$data['toggle_radius'] : $toggleRadiusDefault
        );

        // ==========================================
        // 2. CUSTOMIZAÇÃO DA JANELA LATERAL (MOBILE DRAWER)
        // ==========================================
        $drawerTitleRaw = array_key_exists('drawer_title', $data) ? trim((string)$data['drawer_title']) : 'Navegação';
        $drawerTitle = htmlspecialchars($drawerTitleRaw, ENT_QUOTES, 'UTF-8');
        $drawerTitleColor = $this->styleRenderer->sanitizeCssValue(
            !empty($data['drawer_title_color']) ? (string)$data['drawer_title_color'] : $activeColor
        );
        $drawerBg = $this->styleRenderer->sanitizeCssValue(
            !empty($data['drawer_bg']) ? (string)$data['drawer_bg'] : 'var(--color-surface, rgba(11, 14, 18, 0.98))'
        );
        $drawerBorderColor = $this->styleRenderer->sanitizeCssValue(
            !empty($data['drawer_border_color']) ? (string)$data['drawer_border_color'] : 'var(--color-border, rgba(255, 255, 255, 0.08))'
        );
        $drawerWidth = $this->styleRenderer->sanitizeCssValue(
            !empty($data['drawer_width']) ? (string)$data['drawer_width'] : '320px'
        );
        $drawerPosition = (string)($data['drawer_position'] ?? ($toggleAlign === 'left' ? 'left' : 'right'));
        $drawerPlacementCss = match ($drawerPosition) {
            MobileToggleStyleInterface::DRAWER_POS_LEFT => "top: 0; left: 0; bottom: 0; width: 85vw; max-width: {$drawerWidth}; border-right: 1px solid {$drawerBorderColor}; transform: translateX(-105%);",
            MobileToggleStyleInterface::DRAWER_POS_TOP => "top: 0; left: 0; right: 0; width: 100vw; max-width: 100%; height: auto; max-height: 85vh; border-bottom: 1px solid {$drawerBorderColor}; transform: translateY(-105%);",
            MobileToggleStyleInterface::DRAWER_POS_FULLSCREEN => "inset: 0; width: 100vw; max-width: 100vw; height: 100vh; transform: scale(0.95); opacity: 0; pointer-events: none;",
            default => "top: 0; right: 0; bottom: 0; width: 85vw; max-width: {$drawerWidth}; border-left: 1px solid {$drawerBorderColor}; transform: translateX(105%);",
        };
        $drawerOpenCss = match ($drawerPosition) {
            MobileToggleStyleInterface::DRAWER_POS_TOP        => 'transform: translateY(0);',
            MobileToggleStyleInterface::DRAWER_POS_FULLSCREEN => 'transform: scale(1); opacity: 1; pointer-events: auto;',
            default                                           => 'transform: translateX(0);',
        };

        // ==========================================
        // 3. CUSTOMIZAÇÃO DOS BOTÕES DE MENU DENTRO DO DRAWER MOBILE
        // ==========================================
        $mobileItemStyle = (string)($data['mobile_item_style'] ?? MobileToggleStyleInterface::ITEM_STYLE_CARD);
        if (!in_array($mobileItemStyle, MobileToggleStyleInterface::ALL_ITEM_STYLES, true)) {
            $mobileItemStyle = MobileToggleStyleInterface::ITEM_STYLE_CARD;
        }
        $mobileItemAlign = (string)($data['mobile_item_align'] ?? 'left');
        $mobileItemJustify = match ($mobileItemAlign) {
            'center' => 'center',
            'right'  => 'flex-end',
            default  => 'flex-start',
        };
        $mobileItemTextAlign = in_array($mobileItemAlign, ['left', 'center', 'right'], true) ? $mobileItemAlign : 'left';
        $mobileItemGap = $this->styleRenderer->sanitizeCssValue(
            !empty($data['mobile_item_gap']) ? (string)$data['mobile_item_gap'] : '8px'
        );
        $mobileItemPadding = $this->styleRenderer->sanitizeCssValue(
            !empty($data['mobile_item_padding']) ? (string)$data['mobile_item_padding'] : '12px 16px'
        );
        $mobileLinkColor = $this->styleRenderer->sanitizeCssValue(
            !empty($data['mobile_link_color']) ? (string)$data['mobile_link_color'] : $linkColor
        );
        $mobileHoverTextColor = $this->styleRenderer->sanitizeCssValue(
            !empty($data['mobile_hover_text_color']) ? (string)$data['mobile_hover_text_color'] : $activeColor
        );

        // Valores padrões inteligentes conforme o formato do botão mobile escolhido
        $defaultItemBg = match ($mobileItemStyle) {
            MobileToggleStyleInterface::ITEM_STYLE_CARD      => 'rgba(255, 255, 255, 0.04)',
            MobileToggleStyleInterface::ITEM_STYLE_PILL      => 'rgba(255, 255, 255, 0.05)',
            MobileToggleStyleInterface::ITEM_STYLE_FILLED    => 'var(--color-primary, #38bdf8)',
            MobileToggleStyleInterface::ITEM_STYLE_CYBER_BAR => 'rgba(56, 189, 248, 0.05)',
            default                                          => 'transparent',
        };
        if ($mobileItemStyle === MobileToggleStyleInterface::ITEM_STYLE_FILLED && empty($data['mobile_link_color'])) {
            $mobileLinkColor = 'var(--color-background, #0b0c10)';
        }
        $mobileItemBg = $this->styleRenderer->sanitizeCssValue(
            !empty($data['mobile_item_bg']) ? (string)$data['mobile_item_bg'] : $defaultItemBg
        );
        $defaultItemBorder = match ($mobileItemStyle) {
            MobileToggleStyleInterface::ITEM_STYLE_CARD     => '1px solid rgba(255, 255, 255, 0.07)',
            MobileToggleStyleInterface::ITEM_STYLE_PILL     => '1px solid rgba(255, 255, 255, 0.08)',
            MobileToggleStyleInterface::ITEM_STYLE_OUTLINED => '1px solid ' . ($this->styleRenderer->sanitizeCssValue((string)($data['mobile_item_border_color'] ?? 'var(--color-border, rgba(255,255,255,0.18))'))),
            MobileToggleStyleInterface::ITEM_STYLE_DIVIDER  => 'none',
            default                                         => '1px solid transparent',
        };
        if (!empty($data['mobile_item_border_color']) && $mobileItemStyle !== MobileToggleStyleInterface::ITEM_STYLE_DIVIDER) {
            $sanitizedBorderCol = $this->styleRenderer->sanitizeCssValue((string)$data['mobile_item_border_color']);
            $defaultItemBorder = "1px solid {$sanitizedBorderCol}";
        }
        $defaultItemRadius = match ($mobileItemStyle) {
            MobileToggleStyleInterface::ITEM_STYLE_PILL      => '999px',
            MobileToggleStyleInterface::ITEM_STYLE_DIVIDER,
            MobileToggleStyleInterface::ITEM_STYLE_CYBER_BAR => '4px',
            default                                          => '8px',
        };
        $mobileItemRadius = $this->styleRenderer->sanitizeCssValue(
            !empty($data['mobile_item_radius']) ? (string)$data['mobile_item_radius'] : $defaultItemRadius
        );
        $mobileHoverBgColor = $this->styleRenderer->sanitizeCssValue(
            !empty($data['mobile_hover_bg_color']) ? (string)$data['mobile_hover_bg_color'] : 'rgba(56, 189, 248, 0.14)'
        );
        $mobileHoverBorderColor = $this->styleRenderer->sanitizeCssValue(
            !empty($data['mobile_hover_border_color']) ? (string)$data['mobile_hover_border_color'] : $mobileHoverTextColor
        );
        $mobileHoverEffect = (string)($data['mobile_hover_effect'] ?? 'slide_right');
        $mobileHoverTransformCss = match ($mobileHoverEffect) {
            'slide_right' => 'transform: translateX(6px);',
            'scale'       => 'transform: scale(1.03);',
            'glow'        => "box-shadow: 0 0 16px {$mobileHoverBorderColor};",
            default       => '',
        };

        // ==========================================
        // 4. CUSTOMIZAÇÃO DE SUBMENUS DROPDOWN (DESKTOP & MOBILE)
        // ==========================================
        $submenuBg = $this->styleRenderer->sanitizeCssValue(
            !empty($data['submenu_bg']) ? (string)$data['submenu_bg'] : 'var(--color-surface, rgba(15, 23, 42, 0.96))'
        );
        $submenuBorderColor = $this->styleRenderer->sanitizeCssValue(
            !empty($data['submenu_border_color']) ? (string)$data['submenu_border_color'] : 'var(--color-border, rgba(255, 255, 255, 0.12))'
        );
        $submenuTextColor = $this->styleRenderer->sanitizeCssValue(
            !empty($data['submenu_text_color']) ? (string)$data['submenu_text_color'] : $linkColor
        );
        $submenuHoverTextColor = $this->styleRenderer->sanitizeCssValue(
            !empty($data['submenu_hover_text_color']) ? (string)$data['submenu_hover_text_color'] : $activeColor
        );
        $submenuHoverBgColor = $this->styleRenderer->sanitizeCssValue(
            !empty($data['submenu_hover_bg_color']) ? (string)$data['submenu_hover_bg_color'] : 'rgba(56, 189, 248, 0.12)'
        );
        $submenuRadius = $this->styleRenderer->sanitizeCssValue(
            !empty($data['submenu_radius']) ? (string)$data['submenu_radius'] : '12px'
        );
        $submenuMinWidth = $this->styleRenderer->sanitizeCssValue(
            !empty($data['submenu_min_width']) ? (string)$data['submenu_min_width'] : '220px'
        );

        $items = $this->resolveMenuItems($data, $location);
        if (empty($items)) {
            $items = [
                ['id' => 1, 'title' => 'Início', 'url' => $baseUrl . '/'],
                ['id' => 2, 'title' => 'Cursos', 'url' => $baseUrl . '/cursos'],
                ['id' => 3, 'title' => 'Sobre', 'url' => $baseUrl . '/sobre'],
                ['id' => 4, 'title' => 'Painel', 'url' => $baseUrl . '/admin'],
            ];
        }

        $tree = $this->treeBuilder->buildTree($items);

        $navId = 'ds_nav_' . substr(md5(uniqid((string)mt_rand(), true)), 0, 8);
        $currentUri = (string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        // Renderiza itens desktop e mobile recursivamente (Menus + Submenus)
        $animClass = match ($hoverAnim) {
            'pill_glow', 'pill' => ' ds-nav-anim-pill',
            'grow'              => ' ds-nav-anim-grow',
            'bracket'           => ' ds-nav-anim-bracket',
            'none'              => '',
            default             => ' ds-nav-anim-underline',
        };

        $desktopUl = $this->renderDesktopTree(
            $tree,
            $baseUrl,
            $currentUri,
            $linkColor,
            $activeColor,
            $fontFamily,
            $fontSize,
            $fontWeight,
            $textTransform,
            $animClass,
            0
        );

        $mobileUl = $this->renderMobileTree(
            $tree,
            $baseUrl,
            $currentUri,
            $mobileItemStyle,
            $fontFamily,
            $fontWeight,
            $textTransform,
            $navId,
            0
        );

        // Ícone / Markup do Botão Hamburguer conforme os 8 Estilos do Contrato
        $toggleContent = match ($toggleStyle) {
            MobileToggleStyleInterface::STYLE_PILL         => "<span style=\"font-size:0.78rem; font-weight:800; letter-spacing:1px; margin-right:6px;\">{$toggleLabelText}</span> &#9776;",
            MobileToggleStyleInterface::STYLE_MINIMAL      => '<span style="display:flex; flex-direction:column; gap:5px; width:20px;"><span style="height:2px; background:currentColor; border-radius:2px;"></span><span style="height:2px; background:currentColor; border-radius:2px; width:70%; align-self:flex-end;"></span></span>',
            MobileToggleStyleInterface::STYLE_DOTS         => '<span style="font-size:1.35rem; line-height:1;">&#8942;</span>',
            MobileToggleStyleInterface::STYLE_BOXED        => '<span style="display:inline-flex; align-items:center; gap:5px; font-weight:800; font-size:0.85rem;">&#9776;</span>',
            MobileToggleStyleInterface::STYLE_GRID         => '<span style="font-size:1.3rem; line-height:1; letter-spacing:1px;">&#10303;</span>',
            MobileToggleStyleInterface::STYLE_ROUNDED_GLOW => '<span style="display:inline-flex; align-items:center; justify-content:center; width:22px; height:22px;">&#9776;</span>',
            MobileToggleStyleInterface::STYLE_CYBER        => '<span style="font-family:monospace; font-weight:800; font-size:0.95rem; letter-spacing:1px;">[&#9776;]</span>',
            default                                        => '&#9776;',
        };

        $cyberBarCss = $mobileItemStyle === MobileToggleStyleInterface::ITEM_STYLE_CYBER_BAR
            ? "border-left: 3px solid {$mobileHoverBorderColor} !important;"
            : '';
        $dividerItemCss = $mobileItemStyle === MobileToggleStyleInterface::ITEM_STYLE_DIVIDER
            ? "border-bottom: 1px solid {$drawerBorderColor}; border-radius: 0;"
            : '';
        $roundedGlowToggleCss = $toggleStyle === MobileToggleStyleInterface::STYLE_ROUNDED_GLOW
            ? "box-shadow: 0 0 12px rgba(56, 189, 248, 0.35); width: 40px; height: 40px; padding: 0;"
            : '';

        return <<<HTML
<div id="{$navId}" class="ds-block-nav-wrapper" data-nav-id="{$navId}" style="display:flex; justify-content:{$justifyNav}; width:100%;">
    <style>
        #{$navId} .ds-desktop-menu {
            display: flex;
            align-items: center;
            justify-content: {$justifyNav};
            gap: 24px;
            list-style: none;
            margin: 0;
            padding: 0;
            width: 100%;
        }
        #{$navId} .ds-nav-link {
            position: relative;
            display: inline-flex;
            align-items: center;
            padding: 4px 6px;
        }
        #{$navId} .ds-nav-link:hover, #{$navId} .ds-nav-link.active {
            color: {$activeColor} !important;
        }
        /* HOVER ANIMATIONS DESKTOP */
        #{$navId} .ds-nav-anim-underline::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -2px;
            left: 50%;
            background: {$activeColor};
            transition: all 0.25s ease;
            transform: translateX(-50%);
        }
        #{$navId} .ds-nav-anim-underline:hover::after, #{$navId} .ds-nav-anim-underline.active::after {
            width: 100%;
        }
        #{$navId} .ds-nav-anim-pill {
            border-radius: 20px;
            padding: 6px 14px;
        }
        #{$navId} .ds-nav-anim-pill:hover, #{$navId} .ds-nav-anim-pill.active {
            background: rgba(56, 189, 248, 0.12);
            box-shadow: 0 0 12px rgba(56, 189, 248, 0.2);
        }
        #{$navId} .ds-nav-anim-grow:hover {
            transform: scale(1.1);
        }
        #{$navId} .ds-nav-anim-bracket::before {
            content: '[';
            opacity: 0;
            margin-right: 4px;
            transition: all 0.2s ease;
            color: {$activeColor};
        }
        #{$navId} .ds-nav-anim-bracket::after {
            content: ']';
            opacity: 0;
            margin-left: 4px;
            transition: all 0.2s ease;
            color: {$activeColor};
        }
        #{$navId} .ds-nav-anim-bracket:hover::before, #{$navId} .ds-nav-anim-bracket:hover::after {
            opacity: 1;
        }

        /* SUBMENUS DROPDOWN DESKTOP (INSPIRADO NO WALKER_NAV_MENU / DAHERCLINICA) */
        #{$navId} .ds-menu-item {
            position: relative;
            margin: 0;
            list-style: none;
        }
        #{$navId} .ds-submenu-chevron {
            display: inline-block;
            font-size: 0.72em;
            margin-left: 5px;
            opacity: 0.7;
            transition: transform 0.3s ease, opacity 0.3s ease;
        }
        #{$navId} .menu-item-has-children:hover > .ds-nav-link .ds-submenu-chevron {
            transform: rotate(180deg);
            opacity: 1;
        }
        #{$navId} .ds-sub-menu {
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%) translateY(14px);
            background: {$submenuBg};
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            min-width: {$submenuMinWidth};
            border-radius: {$submenuRadius};
            padding: 8px;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: all 0.32s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 18px 38px rgba(0, 0, 0, 0.45), 0 0 0 1px rgba(255, 255, 255, 0.04);
            z-index: 1200;
            list-style: none;
            margin: 0;
            border: 1px solid {$submenuBorderColor};
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        #{$navId} .menu-item-has-children:hover > .ds-sub-menu,
        #{$navId} .menu-item-has-children:focus-within > .ds-sub-menu {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
            transform: translateX(-50%) translateY(6px);
        }
        /* Sub-submenus (Nível 2+) abrem para a lateral direita */
        #{$navId} .ds-sub-menu .ds-sub-menu {
            top: 0;
            left: 100%;
            transform: translateX(10px) translateY(0);
        }
        #{$navId} .ds-sub-menu .menu-item-has-children:hover > .ds-sub-menu {
            transform: translateX(4px) translateY(0);
        }
        #{$navId} .ds-sub-menu .ds-submenu-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 9px 14px;
            font-size: 0.9rem;
            border-radius: calc({$submenuRadius} - 4px);
            font-weight: 500;
            color: {$submenuTextColor};
            text-decoration: none;
            transition: all 0.22s ease;
            white-space: nowrap;
        }
        #{$navId} .ds-sub-menu .ds-submenu-link:hover,
        #{$navId} .ds-sub-menu .ds-submenu-link.active {
            background: {$submenuHoverBgColor};
            color: {$submenuHoverTextColor} !important;
            padding-left: 18px;
        }

        /* BOTÃO TOGGLE HAMBURGUER MOBILE */
        #{$navId} .ds-mobile-toggle-wrapper {
            display: none;
            width: 100%;
            justify-content: {$justifyToggle};
        }
        #{$navId} .ds-mobile-toggle-btn {
            display: none;
            background: {$toggleBgColor};
            border: 1px solid {$toggleBorderColor};
            color: {$toggleColor};
            padding: 7px 13px;
            border-radius: {$toggleRadius};
            font-size: 1.25rem;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            transition: all 0.25s ease;
            {$roundedGlowToggleCss}
        }
        #{$navId} .ds-mobile-toggle-btn:hover {
            color: {$toggleHoverColor};
            background: {$toggleHoverBgColor};
            border-color: {$toggleHoverColor};
            transform: translateY(-1px);
        }

        /* JANELA LATERAL / MODAL MOBILE (DRAWER) */
        #{$navId} .ds-mobile-drawer {
            display: none;
            position: fixed;
            {$drawerPlacementCss}
            background: {$drawerBg};
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            z-index: 1150;
            padding: 24px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.75);
            flex-direction: column;
            gap: 18px;
            overflow-y: auto;
            transition: transform 0.32s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.25s ease;
        }
        #{$navId} .ds-mobile-drawer.is-open {
            {$drawerOpenCss}
        }
        #{$navId} .ds-mobile-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.65);
            backdrop-filter: blur(4px);
            z-index: 1140;
        }
        #{$navId} .ds-mobile-backdrop.is-open {
            display: block;
        }

        /* BOTÕES DE ITENS DE MENU DENTRO DO DRAWER MOBILE */
        #{$navId} .ds-mobile-nav-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: {$mobileItemGap};
        }
        #{$navId} .ds-mobile-nav-link {
            display: flex;
            align-items: center;
            justify-content: {$mobileItemJustify};
            text-align: {$mobileItemTextAlign};
            padding: {$mobileItemPadding};
            text-decoration: none;
            border-radius: {$mobileItemRadius};
            font-size: 1rem;
            color: {$mobileLinkColor};
            background: {$mobileItemBg};
            border: {$defaultItemBorder};
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            {$cyberBarCss}
            {$dividerItemCss}
        }
        #{$navId} .ds-mobile-nav-link:hover,
        #{$navId} .ds-mobile-nav-link.active {
            color: {$mobileHoverTextColor} !important;
            background: {$mobileHoverBgColor} !important;
            border-color: {$mobileHoverBorderColor} !important;
            {$mobileHoverTransformCss}
        }
        /* SUBMENUS ACORDEÃO DENTRO DO DRAWER MOBILE */
        #{$navId} .ds-mobile-item-row {
            display: flex;
            align-items: stretch;
            gap: 6px;
            width: 100%;
        }
        #{$navId} .ds-mobile-item-row > .ds-mobile-nav-link {
            flex: 1;
        }
        #{$navId} .ds-mobile-submenu-toggle {
            background: {$mobileItemBg};
            border: {$defaultItemBorder};
            color: {$mobileLinkColor};
            border-radius: {$mobileItemRadius};
            width: 42px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            transition: all 0.25s ease;
        }
        #{$navId} .ds-mobile-submenu-toggle.is-open {
            color: {$mobileHoverTextColor};
            background: {$mobileHoverBgColor};
            border-color: {$mobileHoverBorderColor};
            transform: rotate(180deg);
        }
        #{$navId} .ds-mobile-sub-menu {
            list-style: none;
            margin: 6px 0 4px 14px;
            padding: 0 0 0 10px;
            border-left: 2px solid {$drawerBorderColor};
            display: none;
            flex-direction: column;
            gap: 6px;
        }
        #{$navId} .ds-mobile-sub-menu.is-open {
            display: flex;
        }
        @media (max-width: 768px) {
            body:not(.ds-live-preview) #{$navId} .ds-desktop-menu { display: none !important; }
            body:not(.ds-live-preview) #{$navId} .ds-mobile-toggle-wrapper { display: flex !important; }
            body:not(.ds-live-preview) #{$navId} .ds-mobile-toggle-btn { display: inline-flex !important; }
            body:not(.ds-live-preview) #{$navId} .ds-mobile-drawer { display: flex !important; }
            body:not(.ds-live-preview) .ds-swallowed-in-mobile { display: none !important; }
            body:not(.ds-live-preview) header .ds-block-button-wrapper:not(.ds-mobile-drawer *),
            body:not(.ds-live-preview) header .ds-block-user-auth:not(.ds-mobile-drawer *),
            body:not(.ds-live-preview) .ds-theme-header .ds-block-button-wrapper:not(.ds-mobile-drawer *),
            body:not(.ds-live-preview) .ds-theme-header .ds-block-user-auth:not(.ds-mobile-drawer *),
            body:not(.ds-live-preview) header [data-mobile-absorb="true"]:not(.ds-mobile-drawer *),
            body:not(.ds-live-preview) .ds-theme-header [data-mobile-absorb="true"]:not(.ds-mobile-drawer *) {
                display: none !important;
            }
        }
        .ds-mobile-mode #{$navId} .ds-desktop-menu,
        .ds-is-mobile #{$navId} .ds-desktop-menu,
        .ds-device-375 #{$navId} .ds-desktop-menu,
        .ds-device-768 #{$navId} .ds-desktop-menu {
            display: none !important;
        }
        .ds-mobile-mode #{$navId} .ds-mobile-toggle-wrapper,
        .ds-is-mobile #{$navId} .ds-mobile-toggle-wrapper,
        .ds-device-375 #{$navId} .ds-mobile-toggle-wrapper,
        .ds-device-768 #{$navId} .ds-mobile-toggle-wrapper {
            display: flex !important;
        }
        .ds-mobile-mode #{$navId} .ds-mobile-toggle-btn,
        .ds-is-mobile #{$navId} .ds-mobile-toggle-btn,
        .ds-device-375 #{$navId} .ds-mobile-toggle-btn,
        .ds-device-768 #{$navId} .ds-mobile-toggle-btn {
            display: inline-flex !important;
        }
        .ds-mobile-mode #{$navId} .ds-mobile-drawer,
        .ds-is-mobile #{$navId} .ds-mobile-drawer,
        .ds-device-375 #{$navId} .ds-mobile-drawer,
        .ds-device-768 #{$navId} .ds-mobile-drawer {
            display: flex !important;
        }

        .ds-mobile-mode .ds-swallowed-in-mobile,
        .ds-is-mobile .ds-swallowed-in-mobile,
        .ds-device-375 .ds-swallowed-in-mobile,
        .ds-device-768 .ds-swallowed-in-mobile,
        .ds-mobile-mode header .ds-block-button-wrapper:not(.ds-mobile-drawer *),
        .ds-mobile-mode .ds-theme-header .ds-block-button-wrapper:not(.ds-mobile-drawer *),
        .ds-mobile-mode header .ds-block-user-auth:not(.ds-mobile-drawer *),
        .ds-mobile-mode .ds-theme-header .ds-block-user-auth:not(.ds-mobile-drawer *),
        .ds-device-375 header .ds-block-button-wrapper:not(.ds-mobile-drawer *),
        .ds-device-375 .ds-theme-header .ds-block-button-wrapper:not(.ds-mobile-drawer *),
        .ds-device-375 header .ds-block-user-auth:not(.ds-mobile-drawer *),
        .ds-device-375 .ds-theme-header .ds-block-user-auth:not(.ds-mobile-drawer *),
        .ds-device-768 header .ds-block-button-wrapper:not(.ds-mobile-drawer *),
        .ds-device-768 .ds-theme-header .ds-block-button-wrapper:not(.ds-mobile-drawer *),
        .ds-device-768 header .ds-block-user-auth:not(.ds-mobile-drawer *),
        .ds-device-768 .ds-theme-header .ds-block-user-auth:not(.ds-mobile-drawer *) {
            display: none !important;
        }

        /* Container de botões engolidos para dentro da gaveta mobile */
        #{$navId} .ds-mobile-drawer-actions {
            margin-top: auto;
            padding-top: 16px;
            border-top: 1px solid {$drawerBorderColor};
            display: none;
            flex-direction: column;
            gap: 10px;
            width: 100%;
        }
        #{$navId} .ds-mobile-drawer-actions .ds-block-button-wrapper,
        #{$navId} .ds-mobile-drawer-actions .ds-block-user-auth {
            display: flex !important;
            width: 100% !important;
            justify-content: stretch !important;
            margin: 0 !important;
        }
        #{$navId} .ds-mobile-drawer-actions .ds-block-btn,
        #{$navId} .ds-mobile-drawer-actions .ds-auth-btn {
            display: flex !important;
            width: 100% !important;
            justify-content: center !important;
            text-align: center !important;
            padding: 10px 14px !important;
            font-size: 0.95rem !important;
            box-sizing: border-box !important;
        }
    </style>

    <!-- Menu Desktop -->
    <nav class="ds-desktop-nav" aria-label="Menu Principal">
        <ul class="ds-desktop-menu">
            {$desktopUl}
        </ul>
    </nav>

    <!-- Botão Toggle Mobile com Alinhamento e Estilo Configurável -->
    <div class="ds-mobile-toggle-wrapper">
        <button type="button" class="ds-mobile-toggle-btn ds-toggle-{$toggleStyle}" onclick="window.toggleDsNavDrawer('{$navId}', true)" aria-label="Abrir Menu">
            {$toggleContent}
        </button>
    </div>

    <!-- Backdrop & Gaveta Mobile Personalizável -->
    <div class="ds-mobile-backdrop" id="{$navId}_backdrop" onclick="window.toggleDsNavDrawer('{$navId}', false)"></div>
    <aside class="ds-mobile-drawer ds-drawer-pos-{$drawerPosition}" id="{$navId}_drawer">
        <div class="ds-mobile-drawer-header" style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid {$drawerBorderColor}; padding-bottom:14px;">
            <span class="ds-mobile-drawer-title" style="font-weight:800; font-size:0.9rem; letter-spacing:1px; text-transform:uppercase; color:{$drawerTitleColor};">{$drawerTitle}</span>
            <button type="button" class="ds-mobile-drawer-close" onclick="window.toggleDsNavDrawer('{$navId}', false)" style="background:rgba(255,255,255,0.08); border:1px solid {$drawerBorderColor}; color:{$mobileLinkColor}; width:32px; height:32px; border-radius:8px; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; transition:all 0.2s ease;" aria-label="Fechar Menu">✕</button>
        </div>
        <ul class="ds-mobile-nav-list">
            {$mobileUl}
        </ul>
        <div class="ds-mobile-drawer-actions" id="{$navId}_actions"></div>
    </aside>

    <script>
        if (!window.toggleDsNavDrawer) {
            window.toggleDsNavDrawer = function(navId, open) {
                const drawer = document.getElementById(navId + '_drawer');
                const backdrop = document.getElementById(navId + '_backdrop');
                if (!drawer || !backdrop) return;
                if (open) {
                    if (window.syncDsNavDrawerActions) {
                        window.syncDsNavDrawerActions(navId);
                    }
                    drawer.classList.add('is-open');
                    backdrop.classList.add('is-open');
                } else {
                    drawer.classList.remove('is-open');
                    backdrop.classList.remove('is-open');
                }
            };
            window.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    document.querySelectorAll('.ds-mobile-drawer.is-open').forEach(el => el.classList.remove('is-open'));
                    document.querySelectorAll('.ds-mobile-backdrop.is-open').forEach(el => el.classList.remove('is-open'));
                }
            });
        }
        if (!window.toggleDsMobileSubmenu) {
            window.toggleDsMobileSubmenu = function(btnEl, subId) {
                const sub = document.getElementById(subId);
                if (!sub) return;
                const isOpen = sub.classList.toggle('is-open');
                if (btnEl) {
                    btnEl.classList.toggle('is-open', isOpen);
                    btnEl.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                }
            };
        }
        if (!window.syncDsNavDrawerActions) {
            window.syncDsNavDrawerActions = function(navId) {
                const nav = document.getElementById(navId);
                const drawer = document.getElementById(navId + '_drawer');
                const actionsContainer = document.getElementById(navId + '_actions');
                if (!drawer || !actionsContainer) return;

                const header = nav ? (nav.closest('header') || nav.closest('.ds-theme-header') || nav.closest('.ds-theme-part') || nav.closest('.container') || document.querySelector('header, .ds-theme-header')) : document.querySelector('header');
                if (!header) return;

                const candidates = header.querySelectorAll('.ds-block-button-wrapper, .ds-block-user-auth, [data-mobile-absorb="true"]');
                candidates.forEach(function(el) {
                    if (drawer.contains(el)) return;

                    el.classList.add('ds-swallowed-in-mobile');
                    const wrapper = el.closest('.ds-element-wrapper');
                    if (wrapper) wrapper.classList.add('ds-swallowed-in-mobile');

                    const origId = el.id || ('ds_orig_' + Math.random().toString(36).substring(2, 7));
                    if (!el.id) el.id = origId;
                    if (actionsContainer.querySelector('[data-absorbed-from="' + origId + '"]')) return;

                    const clone = el.cloneNode(true);
                    clone.setAttribute('data-absorbed-from', origId);
                    clone.classList.remove('ds-swallowed-in-mobile');
                    clone.style.display = 'flex';
                    clone.style.width = '100%';
                    clone.style.margin = '0';
                    clone.style.justifyContent = 'stretch';
                    clone.querySelectorAll('.ds-block-btn, .ds-auth-btn').forEach(function(btn) {
                        btn.style.width = '100%';
                        btn.style.justifyContent = 'center';
                        btn.style.textAlign = 'center';
                        btn.style.boxSizing = 'border-box';
                    });
                    actionsContainer.appendChild(clone);
                });

                if (actionsContainer.children.length > 0) {
                    actionsContainer.style.display = 'flex';
                }
            };
        }
        if (typeof window !== 'undefined') {
            const initSync = function() {
                if (window.syncDsNavDrawerActions) {
                    window.syncDsNavDrawerActions('{$navId}');
                }
            };
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initSync);
            } else {
                setTimeout(initSync, 50);
            }
        }
    </script>
</div>
HTML;
    }

    /**
     * Renderiza recursivamente a árvore de menus e submenus para Desktop.
     *
     * @param array<int, array<string, mixed>> $nodes
     */
    private function renderDesktopTree(
        array $nodes,
        string $baseUrl,
        string $currentUri,
        string $linkColor,
        string $activeColor,
        string $fontFamily,
        string $fontSize,
        string $fontWeight,
        string $textTransform,
        string $animClass,
        int $level = 0
    ): string {
        $itemsHtml = [];

        foreach ($nodes as $item) {
            $title = htmlspecialchars((string)($item['title'] ?? ''), ENT_QUOTES, 'UTF-8');
            $safeUrl = $this->htmlSanitizer->sanitizeUrl((string)($item['url'] ?? '#'), '#');
            $url = htmlspecialchars($safeUrl, ENT_QUOTES, 'UTF-8');
            $target = (($item['target'] ?? '_self') === '_blank') ? ' target="_blank" rel="noopener noreferrer"' : '';
            $extraClass = !empty($item['css_classes'])
                ? ' ' . htmlspecialchars(preg_replace('/[^a-zA-Z0-9_\- ]/', '', (string)$item['css_classes']) ?? '', ENT_QUOTES, 'UTF-8')
                : '';

            $isActive = ($currentUri === $url || ($url !== $baseUrl . '/' && $url !== '#' && str_starts_with($currentUri, $url)));
            $children = (!empty($item['children']) && is_array($item['children'])) ? $item['children'] : [];
            $hasChildren = !empty($children);
            $liClass = 'ds-menu-item' . ($hasChildren ? ' menu-item-has-children' : '') . $extraClass;

            if ($level === 0) {
                $itemColor = $isActive ? $activeColor : $linkColor;
                $activeClass = $isActive ? ' active' : '';
                $activeStyle = "color: {$itemColor}; font-weight: " . ($isActive ? '700' : $fontWeight) . "; text-transform: {$textTransform};";
                $chevron = $hasChildren ? '<span class="ds-submenu-chevron" aria-hidden="true">&#9662;</span>' : '';
                $linkHtml = "<a href=\"{$url}\"{$target} class=\"ds-nav-link{$activeClass}{$animClass}\" style=\"{$activeStyle} text-decoration: none; font-family: {$fontFamily}; font-size: {$fontSize}; transition: all 0.25s ease;\">{$title}{$chevron}</a>";
            } else {
                $activeClass = $isActive ? ' active' : '';
                $chevron = $hasChildren ? '<span class="ds-submenu-chevron" aria-hidden="true">&#9656;</span>' : '';
                $linkHtml = "<a href=\"{$url}\"{$target} class=\"ds-submenu-link{$activeClass}\" style=\"font-family: {$fontFamily}; text-transform: {$textTransform};\">{$title}{$chevron}</a>";
            }

            $submenuHtml = '';
            if ($hasChildren) {
                $inner = $this->renderDesktopTree(
                    $children,
                    $baseUrl,
                    $currentUri,
                    $linkColor,
                    $activeColor,
                    $fontFamily,
                    $fontSize,
                    $fontWeight,
                    $textTransform,
                    $animClass,
                    $level + 1
                );
                $submenuHtml = "\n<ul class=\"sub-menu ds-sub-menu\">\n{$inner}\n</ul>";
            }

            $itemsHtml[] = "<li class=\"{$liClass}\">{$linkHtml}{$submenuHtml}</li>";
        }

        return implode("\n", $itemsHtml);
    }

    /**
     * Renderiza recursivamente a árvore de menus e submenus para a Gaveta Mobile (Acordeão).
     *
     * @param array<int, array<string, mixed>> $nodes
     */
    private function renderMobileTree(
        array $nodes,
        string $baseUrl,
        string $currentUri,
        string $mobileItemStyle,
        string $fontFamily,
        string $fontWeight,
        string $textTransform,
        string $navId,
        int $level = 0
    ): string {
        $itemsHtml = [];

        foreach ($nodes as $idx => $item) {
            $title = htmlspecialchars((string)($item['title'] ?? ''), ENT_QUOTES, 'UTF-8');
            $safeUrl = $this->htmlSanitizer->sanitizeUrl((string)($item['url'] ?? '#'), '#');
            $url = htmlspecialchars($safeUrl, ENT_QUOTES, 'UTF-8');
            $target = (($item['target'] ?? '_self') === '_blank') ? ' target="_blank" rel="noopener noreferrer"' : '';
            $isActive = ($currentUri === $url || ($url !== $baseUrl . '/' && $url !== '#' && str_starts_with($currentUri, $url)));
            $activeClass = $isActive ? ' active' : '';
            $children = (!empty($item['children']) && is_array($item['children'])) ? $item['children'] : [];
            $hasChildren = !empty($children);

            $subId = "{$navId}_mob_sub_{$level}_{$idx}_" . (int)($item['id'] ?? $idx);
            $linkFontSize = $level > 0 ? 'font-size: 0.92rem;' : '';
            $onClickToggle = ($hasChildren && ($url === '#' || $url === ''))
                ? " onclick=\"event.preventDefault(); window.toggleDsMobileSubmenu(this.nextElementSibling, '{$subId}')\""
                : '';

            if ($hasChildren) {
                $innerMobile = $this->renderMobileTree(
                    $children,
                    $baseUrl,
                    $currentUri,
                    $mobileItemStyle,
                    $fontFamily,
                    $fontWeight,
                    $textTransform,
                    $navId,
                    $level + 1
                );
                $itemsHtml[] = "<li class=\"ds-mobile-menu-item menu-item-has-children\" style=\"margin: 0;\">"
                    . "<div class=\"ds-mobile-item-row\">"
                    . "<a href=\"{$url}\"{$target}{$onClickToggle} class=\"ds-mobile-nav-link ds-mobile-item-{$mobileItemStyle}{$activeClass}\" style=\"font-weight: " . ($isActive ? '700' : $fontWeight) . "; text-transform: {$textTransform}; font-family: {$fontFamily}; {$linkFontSize}\">{$title}</a>"
                    . "<button type=\"button\" class=\"ds-mobile-submenu-toggle\" onclick=\"window.toggleDsMobileSubmenu(this, '{$subId}')\" aria-label=\"Expandir Submenu\" aria-expanded=\"false\">&#9662;</button>"
                    . "</div>"
                    . "<ul id=\"{$subId}\" class=\"ds-mobile-sub-menu\">{$innerMobile}</ul>"
                    . "</li>";
            } else {
                $itemsHtml[] = "<li class=\"ds-mobile-menu-item\" style=\"margin: 0;\"><a href=\"{$url}\"{$target} class=\"ds-mobile-nav-link ds-mobile-item-{$mobileItemStyle}{$activeClass}\" style=\"font-weight: " . ($isActive ? '700' : $fontWeight) . "; text-transform: {$textTransform}; font-family: {$fontFamily}; {$linkFontSize}\">{$title}</a></li>";
            }
        }

        return implode("\n", $itemsHtml);
    }

    private function resolveMenuItems(array $data, string $location): array
    {
        if (!empty($data['custom_items']) && is_array($data['custom_items'])) {
            return $data['custom_items'];
        }

        $provider = $this->menuProvider;
        if ($provider === null && $this->menuProviderResolver !== null) {
            try {
                $provider = ($this->menuProviderResolver)();
            } catch (\Throwable $e) {
                $provider = null;
            }
        }

        if ($provider === null) {
            return [];
        }

        try {
            $menuId = !empty($data['menu_id']) ? (int)$data['menu_id'] : null;
            return $provider->getMenuItems($location, $menuId);
        } catch (\Throwable $e) {}

        return [];
    }

    public function getSchema(): array
    {
        return [
            'type' => 'nav_menu',
            'fields' => [
                'menu_location'             => ['type' => 'select', 'label' => 'Local do Menu', 'options' => ['header', 'footer', 'custom'], 'default' => 'header'],
                'menu_id'                   => ['type' => 'number', 'label' => 'ID Específico do Menu (Opcional)', 'default' => 0],
                'toggle_style'              => ['type' => 'select', 'label' => 'Estilo do Botão Hamburguer', 'options' => MobileToggleStyleInterface::ALL_TOGGLE_STYLES, 'default' => 'classic'],
                'toggle_color'              => ['type' => 'color', 'label' => 'Cor do Ícone Hamburguer', 'default' => 'var(--color-primary)'],
                'toggle_bg_color'           => ['type' => 'color', 'label' => 'Cor de Fundo do Hamburguer', 'default' => 'transparent'],
                'toggle_hover_color'        => ['type' => 'color', 'label' => 'Cor Hover do Hamburguer', 'default' => 'var(--color-primary)'],
                'toggle_hover_bg_color'     => ['type' => 'color', 'label' => 'Fundo Hover do Hamburguer', 'default' => 'rgba(56, 189, 248, 0.18)'],
                'drawer_title'              => ['type' => 'text', 'label' => 'Título da Janela Mobile', 'default' => 'Navegação'],
                'drawer_title_color'        => ['type' => 'color', 'label' => 'Cor do Título da Janela Mobile', 'default' => 'var(--color-primary)'],
                'drawer_bg'                 => ['type' => 'color', 'label' => 'Fundo da Janela Lateral Mobile', 'default' => 'var(--color-surface)'],
                'drawer_position'           => ['type' => 'select', 'label' => 'Modo de Abertura da Janela Mobile', 'options' => ['right', 'left', 'top', 'fullscreen'], 'default' => 'right'],
                'drawer_width'              => ['type' => 'dimension', 'label' => 'Largura da Janela Mobile', 'default' => '320px'],
                'drawer_border_color'       => ['type' => 'color', 'label' => 'Cor da Borda da Janela Mobile', 'default' => 'var(--color-border)'],
                'mobile_item_style'         => ['type' => 'select', 'label' => 'Formato dos Botões Mobile', 'options' => MobileToggleStyleInterface::ALL_ITEM_STYLES, 'default' => 'card'],
                'mobile_item_align'         => ['type' => 'select', 'label' => 'Alinhamento dos Botões Mobile', 'options' => ['left', 'center', 'right'], 'default' => 'left'],
                'mobile_hover_effect'       => ['type' => 'select', 'label' => 'Efeito Hover dos Botões Mobile', 'options' => ['slide_right', 'scale', 'glow', 'fill', 'none'], 'default' => 'slide_right'],
                'mobile_link_color'         => ['type' => 'color', 'label' => 'Cor do Texto dos Botões Mobile', 'default' => 'var(--color-text-main)'],
                'mobile_item_bg'            => ['type' => 'color', 'label' => 'Cor de Fundo dos Botões Mobile', 'default' => 'rgba(255, 255, 255, 0.04)'],
                'mobile_item_border_color'  => ['type' => 'color', 'label' => 'Cor da Borda dos Botões Mobile', 'default' => 'rgba(255, 255, 255, 0.07)'],
                'mobile_hover_text_color'   => ['type' => 'color', 'label' => 'Cor do Texto no Hover (Mobile)', 'default' => 'var(--color-primary)'],
                'mobile_hover_bg_color'     => ['type' => 'color', 'label' => 'Cor de Fundo no Hover (Mobile)', 'default' => 'rgba(56, 189, 248, 0.14)'],
                'mobile_hover_border_color' => ['type' => 'color', 'label' => 'Cor da Borda no Hover (Mobile)', 'default' => 'var(--color-primary)'],
                'submenu_bg'                => ['type' => 'color', 'label' => 'Cor de Fundo do Submenu Dropdown', 'default' => 'var(--color-surface)'],
                'submenu_border_color'      => ['type' => 'color', 'label' => 'Cor da Borda do Submenu Dropdown', 'default' => 'var(--color-border)'],
                'submenu_text_color'        => ['type' => 'color', 'label' => 'Cor do Texto do Submenu', 'default' => 'var(--color-text-main)'],
                'submenu_hover_text_color'  => ['type' => 'color', 'label' => 'Cor do Texto no Hover do Submenu', 'default' => 'var(--color-primary)'],
                'submenu_hover_bg_color'    => ['type' => 'color', 'label' => 'Cor de Fundo no Hover do Submenu', 'default' => 'rgba(56, 189, 248, 0.12)'],
                'submenu_radius'            => ['type' => 'dimension', 'label' => 'Arredondamento do Submenu', 'default' => '12px'],
                'submenu_min_width'         => ['type' => 'dimension', 'label' => 'Largura Mínima do Submenu', 'default' => '220px'],
                'link_color'                => ['type' => 'color', 'label' => 'Cor dos Links (Desktop)', 'default' => 'var(--color-text-main)'],
                'active_color'              => ['type' => 'color', 'label' => 'Cor do Link Ativo / Hover', 'default' => 'var(--color-primary)'],
                'font_family'               => ['type' => 'font', 'label' => 'Família da Fonte', 'default' => 'var(--font-primary)'],
                'font_size'                 => ['type' => 'dimension', 'label' => 'Tamanho da Fonte', 'default' => '0.95rem'],
                'font_weight'               => ['type' => 'select', 'label' => 'Peso da Fonte', 'options' => ['400', '500', '600', '700', '800'], 'default' => '500'],
                'text_transform'            => ['type' => 'select', 'label' => 'Transformação de Texto', 'options' => ['none', 'uppercase', 'lowercase', 'capitalize'], 'default' => 'none'],
            ],
        ];
    }
}
