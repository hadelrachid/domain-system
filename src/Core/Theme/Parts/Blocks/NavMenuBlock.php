<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Blocks;

use DomainSystem\Core\Theme\Parts\Blocks\Traits\AllowedPartTypesTrait;
use DomainSystem\Core\Theme\Parts\Contracts\BlockInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ElementStyleRendererInterface;
use DomainSystem\Core\Theme\Parts\Contracts\HtmlSanitizerInterface;
use DomainSystem\Core\Theme\Parts\Contracts\MenuProviderInterface;
use DomainSystem\Core\Theme\Parts\Contracts\MobileToggleStyleInterface;
use DomainSystem\Core\Theme\Parts\Contracts\PublicUrlResolverInterface;
use DomainSystem\Core\Theme\Parts\Styles\ElementStyleRenderer;
use DomainSystem\Core\Theme\Parts\Support\BaseUrlResolver;
use DomainSystem\Core\Theme\Parts\Support\SafeHtmlSanitizer;

/**
 * Bloco de Navegação e Menu Dinâmico com Suporte aos 4 Estilos de Toggle Mobile.
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

    /**
     * @param MenuProviderInterface|null $menuProvider
     * @param (\Closure(): ?MenuProviderInterface)|null $menuProviderResolver
     * @param ElementStyleRendererInterface|null $styleRenderer
     * @param HtmlSanitizerInterface|null $htmlSanitizer
     * @param PublicUrlResolverInterface|null $urlResolver
     */
    public function __construct(
        ?MenuProviderInterface $menuProvider = null,
        ?\Closure $menuProviderResolver = null,
        ?ElementStyleRendererInterface $styleRenderer = null,
        ?HtmlSanitizerInterface $htmlSanitizer = null,
        ?PublicUrlResolverInterface $urlResolver = null
    ) {
        $this->menuProvider = $menuProvider;
        $this->menuProviderResolver = $menuProviderResolver;
        $this->styleRenderer = $styleRenderer ?? new ElementStyleRenderer();
        $this->htmlSanitizer = $htmlSanitizer ?? new SafeHtmlSanitizer();
        $this->urlResolver = $urlResolver ?? new BaseUrlResolver();
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
        $linkColor = $this->styleRenderer->sanitizeCssValue((string)($data['link_color'] ?? 'var(--color-text-main, #c5c6c7)'));
        $activeColor = $this->styleRenderer->sanitizeCssValue((string)($data['active_color'] ?? 'var(--color-primary, #38bdf8)'));
        $fontFamily = $this->styleRenderer->sanitizeCssValue(
            !empty($data['font_family']) ? (string)$data['font_family'] : 'var(--font-primary, inherit)'
        );
        $fontSize = $this->styleRenderer->sanitizeCssValue(
            !empty($data['font_size']) ? (string)$data['font_size'] : '0.95rem'
        );
        $hoverAnim = (string)($data['hover_animation'] ?? 'underline');
        $justifyToggle = match ($toggleAlign) {
            'left'   => 'flex-start',
            'center' => 'center',
            'right'  => 'flex-end',
            default  => 'flex-end',
        };

        $drawerSide = ($toggleAlign === 'left') ? 'left: 0; transform: translateX(-105%);' : 'right: 0; transform: translateX(105%);';

        $items = $this->resolveMenuItems($data, $location);
        if (empty($items)) {
            $items = [
                ['title' => 'Início', 'url' => $baseUrl . '/'],
                ['title' => 'Cursos', 'url' => $baseUrl . '/cursos'],
                ['title' => 'Sobre', 'url' => $baseUrl . '/sobre'],
                ['title' => 'Painel', 'url' => $baseUrl . '/admin'],
            ];
        }

        $navId = 'ds_nav_' . substr(md5(uniqid((string)mt_rand(), true)), 0, 8);
        $currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        // Renderiza itens desktop
        $animClass = match ($hoverAnim) {
            'pill_glow', 'pill' => ' ds-nav-anim-pill',
            'grow'              => ' ds-nav-anim-grow',
            'bracket'           => ' ds-nav-anim-bracket',
            'none'              => '',
            default             => ' ds-nav-anim-underline',
        };

        $desktopLinks = [];
        $mobileLinks = [];
        foreach ($items as $item) {
            $title = htmlspecialchars((string)($item['title'] ?? ''), ENT_QUOTES, 'UTF-8');
            $safeUrl = $this->htmlSanitizer->sanitizeUrl((string)($item['url'] ?? '#'), '#');
            $url = htmlspecialchars($safeUrl, ENT_QUOTES, 'UTF-8');
            $isActive = ($currentUri === $url || ($url !== $baseUrl . '/' && $url !== '#' && str_starts_with((string)$currentUri, (string)$url)));

            $itemColor = $isActive ? $activeColor : $linkColor;
            $activeClass = $isActive ? ' active' : '';
            $activeStyle = "color: {$itemColor};" . ($isActive ? ' font-weight: 700;' : '');

            $desktopLinks[] = "<li style=\"margin: 0;\"><a href=\"{$url}\" class=\"ds-nav-link{$activeClass}{$animClass}\" style=\"{$activeStyle} text-decoration: none; font-family: {$fontFamily}; font-size: {$fontSize}; transition: all 0.25s ease;\">{$title}</a></li>";
            $mobileLinks[] = "<li style=\"margin: 0;\"><a href=\"{$url}\" class=\"ds-mobile-nav-link{$activeClass}\" style=\"{$activeStyle} display: block; padding: 10px 14px; text-decoration: none; border-radius: 6px; font-size: 1rem; font-family: {$fontFamily};\">{$title}</a></li>";
        }

        $desktopUl = implode("\n", $desktopLinks);
        $mobileUl = implode("\n", $mobileLinks);

        // Ícone / Markup do Botão Hamburguer conforme o Contrato
        $toggleContent = match ($toggleStyle) {
            MobileToggleStyleInterface::STYLE_PILL    => '<span style="font-size:0.8rem; font-weight:800; letter-spacing:1px; margin-right:4px;">MENU</span> &#9776;',
            MobileToggleStyleInterface::STYLE_MINIMAL => '<span style="display:flex; flex-direction:column; gap:4px; width:20px;"><span style="height:2px; background:currentColor; border-radius:2px;"></span><span style="height:2px; background:currentColor; border-radius:2px;"></span></span>',
            MobileToggleStyleInterface::STYLE_DOTS    => '&#8942;',
            default                                   => '&#9776;',
        };

        return <<<HTML
<div id="{$navId}" class="ds-block-nav-wrapper" style="display:flex; justify-content:{$justifyNav}; width:100%;">
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
        /* HOVER ANIMATIONS */
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

        #{$navId} .ds-mobile-toggle-wrapper {
            display: none;
            width: 100%;
            justify-content: {$justifyToggle};
        }
        #{$navId} .ds-mobile-toggle-btn {
            display: none;
            background: transparent;
            border: 1px solid rgba(69, 243, 255, 0.2);
            color: var(--primary, #45f3ff);
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 1.25rem;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        #{$navId} .ds-mobile-drawer {
            display: none;
            position: fixed;
            top: 0;
            {$drawerSide}
            bottom: 0;
            width: 80vw;
            max-width: 320px;
            background: rgba(11, 14, 18, 0.98);
            backdrop-filter: blur(16px);
            z-index: 1150;
            padding: 24px;
            box-shadow: -10px 0 30px rgba(0,0,0,0.7);
            flex-direction: column;
            gap: 16px;
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        #{$navId} .ds-mobile-drawer.is-open {
            transform: translateX(0);
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
        @media (max-width: 768px) {
            #{$navId} .ds-desktop-menu { display: none !important; }
            #{$navId} .ds-mobile-toggle-wrapper { display: flex !important; }
            #{$navId} .ds-mobile-toggle-btn { display: inline-flex !important; }
            #{$navId} .ds-mobile-drawer { display: flex !important; }
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
    </style>

    <!-- Menu Desktop -->
    <nav class="ds-desktop-nav" aria-label="Menu Principal">
        <ul class="ds-desktop-menu">
            {$desktopUl}
        </ul>
    </nav>

    <!-- Botão Toggle Mobile com Alinhamento Configurável -->
    <div class="ds-mobile-toggle-wrapper">
        <button type="button" class="ds-mobile-toggle-btn ds-toggle-{$toggleStyle}" onclick="window.toggleDsNavDrawer('{$navId}', true)" aria-label="Abrir Menu">
            {$toggleContent}
        </button>
    </div>

    <!-- Backdrop & Gaveta Mobile -->
    <div class="ds-mobile-backdrop" id="{$navId}_backdrop" onclick="window.toggleDsNavDrawer('{$navId}', false)"></div>
    <aside class="ds-mobile-drawer" id="{$navId}_drawer">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:12px;">
            <span style="font-weight:800; font-size:0.85rem; letter-spacing:1px; text-transform:uppercase; color:var(--primary, #45f3ff);">Navegação</span>
            <button type="button" onclick="window.toggleDsNavDrawer('{$navId}', false)" style="background:rgba(255,255,255,0.08); border:none; color:#fff; width:30px; height:30px; border-radius:6px; cursor:pointer;">✕</button>
        </div>
        <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:6px;">
            {$mobileUl}
        </ul>
    </aside>

    <script>
        if (!window.toggleDsNavDrawer) {
            window.toggleDsNavDrawer = function(navId, open) {
                const drawer = document.getElementById(navId + '_drawer');
                const backdrop = document.getElementById(navId + '_backdrop');
                if (!drawer || !backdrop) return;
                if (open) {
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
    </script>
</div>
HTML;
    }

    private function resolveMenuItems(array $data, string $location): array
    {
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
                'menu_location' => ['type' => 'select', 'label' => 'Local do Menu', 'options' => ['header', 'footer', 'custom'], 'default' => 'header'],
                'menu_id'       => ['type' => 'number', 'label' => 'ID Específico do Menu (Opcional)', 'default' => 0],
                'toggle_style'  => ['type' => 'select', 'label' => 'Estilo do Botão Hamburguer', 'options' => ['classic', 'pill', 'minimal', 'dots'], 'default' => 'classic'],
            ],
        ];
    }
}
