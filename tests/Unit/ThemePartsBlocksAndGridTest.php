<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Core\Theme\Parts\Blocks\BlockRegistry;
use DomainSystem\Core\Theme\Parts\Blocks\CopyrightBlock;
use DomainSystem\Core\Theme\Parts\Blocks\CtaButtonBlock;
use DomainSystem\Core\Theme\Parts\Blocks\CustomCodeBlock;
use DomainSystem\Core\Theme\Parts\Blocks\DefaultBlockRegistryFactory;
use DomainSystem\Core\Theme\Parts\Blocks\DividerBlock;
use DomainSystem\Core\Theme\Parts\Blocks\HeadingBlock;
use DomainSystem\Core\Theme\Parts\Blocks\ImageBlock;
use DomainSystem\Core\Theme\Parts\Blocks\LogoBlock;
use DomainSystem\Core\Theme\Parts\Blocks\NavMenuBlock;
use DomainSystem\Core\Theme\Parts\Blocks\ParagraphBlock;
use DomainSystem\Core\Theme\Parts\Blocks\ShortcodeBlock;
use DomainSystem\Core\Theme\Parts\Blocks\SocialIconsBlock;
use DomainSystem\Core\Theme\Parts\Blocks\VideoBlock;
use DomainSystem\Core\Theme\Parts\Contracts\MenuProviderInterface;
use DomainSystem\Core\Theme\Parts\Contracts\MobileToggleStyleInterface;
use DomainSystem\Core\Theme\Parts\Grid\LayoutGridRenderer;
use DomainSystem\Core\Theme\ShortcodeManager;
use PHPUnit\Framework\TestCase;

class ThemePartsBlocksAndGridTest extends TestCase
{
    public function testLayoutGridRendererPresetsAndOutput(): void
    {
        $grid = new LayoutGridRenderer();
        $presets = $grid->getPresets();

        $this->assertArrayHasKey('1_col', $presets);
        $this->assertArrayHasKey('2_cols_equal', $presets);
        $this->assertArrayHasKey('2_cols_split', $presets);
        $this->assertArrayHasKey('3_cols_header', $presets);
        $this->assertArrayHasKey('3_cols_equal', $presets);
        $this->assertArrayHasKey('4_cols_footer', $presets);

        // Renderiza seção 3 colunas (Header Pro)
        $cols = [
            '<div>Logo</div>',
            '<div>Menu</div>',
            '<div>CTA</div>',
        ];
        $html = $grid->renderSection('3_cols_header', $cols, [
            'boxed'       => true,
            'max_width'   => '1200px',
            'gap'         => 'medium',
            'align_items' => 'center',
            'padding'     => '15px 20px',
        ]);

        $this->assertStringContainsString('ds-section-wrapper', $html);
        $this->assertStringContainsString('max-width: 1200px', $html);
        $this->assertStringContainsString('gap: 24px', $html);
        $this->assertStringContainsString('Logo', $html);
        $this->assertStringContainsString('Menu', $html);
        $this->assertStringContainsString('CTA', $html);
        $this->assertStringContainsString('@media (max-width: 640px)', $html);
    }

    public function testDefaultBlockRegistryFactoryContainsAll12Blocks(): void
    {
        $registry = DefaultBlockRegistryFactory::create();
        $all = $registry->all();

        $this->assertCount(14, $all);
        $this->assertNotNull($registry->get('heading'));
        $this->assertNotNull($registry->get('paragraph'));
        $this->assertNotNull($registry->get('image'));
        $this->assertNotNull($registry->get('video'));
        $this->assertNotNull($registry->get('logo'));
        $this->assertNotNull($registry->get('button'));
        $this->assertNotNull($registry->get('cta_button'));
        $this->assertNotNull($registry->get('divider'));
        $this->assertNotNull($registry->get('shortcode'));
        $this->assertNotNull($registry->get('custom_code'));
        $this->assertNotNull($registry->get('social_icons'));
        $this->assertNotNull($registry->get('copyright'));
        $this->assertNotNull($registry->get('nav_menu'));
        $this->assertNotNull($registry->get('post_cards_grid'));

        $grouped = $registry->allByCategory();
        $this->assertArrayHasKey('typography', $grouped);
        $this->assertArrayHasKey('media', $grouped);
        $this->assertArrayHasKey('navigation', $grouped);
        $this->assertArrayHasKey('action', $grouped);
        $this->assertArrayHasKey('system', $grouped);
        $this->assertArrayHasKey('layout', $grouped);
        $this->assertArrayHasKey('footer', $grouped);
    }

    public function testHeadingBlockRendering(): void
    {
        $block = new HeadingBlock();
        $html = $block->render([
            'text'        => 'Meu Título H1',
            'level'       => 1,
            'size'        => '2.5rem',
            'color'       => '#45f3ff',
            'align'       => 'center',
            'font_family' => 'secondary',
        ]);

        $this->assertStringContainsString('<h1', $html);
        $this->assertStringContainsString('Meu Título H1', $html);
        $this->assertStringContainsString('font-size: 2.5rem', $html);
        $this->assertStringContainsString('color: #45f3ff', $html);
        $this->assertStringContainsString('text-align: center', $html);
        $this->assertStringContainsString('font-family: var(--font-secondary', $html);
    }

    public function testParagraphBlockRenderingAndSanitization(): void
    {
        $block = new ParagraphBlock();
        $html = $block->render([
            'text'        => 'Texto com <strong>negrito</strong> e <script>alert(1)</script>',
            'size'        => '1.1rem',
            'color'       => '#ffffff',
            'line_height' => '1.8',
        ]);

        $this->assertStringContainsString('<p', $html);
        $this->assertStringContainsString('<strong>negrito</strong>', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('line-height: 1.8', $html);
    }

    public function testImageBlockRendering(): void
    {
        $block = new ImageBlock();
        $html = $block->render([
            'url'           => 'https://example.com/foto.jpg',
            'alt'           => 'Foto Exemplo',
            'width'         => '300px',
            'border_radius' => '12px',
            'link'          => 'https://example.com',
            'align'         => 'center',
        ]);

        $this->assertStringContainsString('<img', $html);
        $this->assertStringContainsString('src="https://example.com/foto.jpg"', $html);
        $this->assertStringContainsString('alt="Foto Exemplo"', $html);
        $this->assertStringContainsString('border-radius: 12px', $html);
        $this->assertStringContainsString('<a href="https://example.com"', $html);
    }

    public function testVideoBlockRenderingYouTubeAndDirect(): void
    {
        $block = new VideoBlock();

        // YouTube embed
        $ytHtml = $block->render(['url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']);
        $this->assertStringContainsString('youtube.com/embed/dQw4w9WgXcQ', $ytHtml);

        // Vimeo embed
        $vimeoHtml = $block->render(['url' => 'https://vimeo.com/123456789']);
        $this->assertStringContainsString('player.vimeo.com/video/123456789', $vimeoHtml);

        // MP4 direto
        $mp4Html = $block->render(['url' => 'https://example.com/video.mp4']);
        $this->assertStringContainsString('<video src="https://example.com/video.mp4"', $mp4Html);
    }

    public function testLogoBlockRendering(): void
    {
        $block = new LogoBlock();
        $html = $block->render([
            'brand_text' => 'Domain<span class="text-primary">System</span>',
            'image_url'  => 'assets/img/logo.png',
            'height'     => '38px',
        ]);

        $this->assertStringContainsString('ds-block-logo', $html);
        $this->assertStringContainsString('src="assets/img/logo.png"', $html);
        $this->assertStringContainsString('Domain<span class="text-primary">System</span>', $html);
    }

    public function testCtaButtonBlockVariants(): void
    {
        $block = new CtaButtonBlock();
        $html = $block->render([
            'label'   => 'Matricule-se Agora',
            'url'     => '/cursos',
            'variant' => 'glow',
            'size'    => 'large',
        ]);

        $this->assertStringContainsString('Matricule-se Agora', $html);
        $this->assertStringContainsString('href="/cursos"', $html);
        $this->assertStringContainsString('box-shadow: 0 0 20px', $html);
    }

    public function testDividerBlockStyles(): void
    {
        $block = new DividerBlock();

        $spacerHtml = $block->render(['style' => 'spacer', 'height' => '50px']);
        $this->assertStringContainsString('height: 50px', $spacerHtml);
        $this->assertStringContainsString('ds-block-spacer', $spacerHtml);

        $gradHtml = $block->render(['style' => 'gradient', 'color' => '#45f3ff']);
        $this->assertStringContainsString('background: linear-gradient', $gradHtml);
    }

    public function testShortcodeBlockExecution(): void
    {
        $sm = new ShortcodeManager();
        $sm->add('test_shortcode', fn() => '<div>Conteúdo do Shortcode Executado</div>');

        $block = new ShortcodeBlock($sm);
        $html = $block->render(['code' => '[test_shortcode]']);

        $this->assertStringContainsString('Conteúdo do Shortcode Executado', $html);
    }

    public function testCustomCodeBlockRendering(): void
    {
        $block = new CustomCodeBlock();
        $html = $block->render([
            'html' => '<div class="meu-custom">Olá Mundo</div>',
            'css'  => '.meu-custom { color: red; }',
        ]);

        $this->assertStringContainsString('<style>.meu-custom { color: red; }</style>', $html);
        $this->assertStringContainsString('Olá Mundo', $html);
    }

    public function testSocialIconsBlockRendering(): void
    {
        $block = new SocialIconsBlock();
        $html = $block->render([
            'networks' => [
                'github'   => 'https://github.com/hadelrachid',
                'linkedin' => 'https://linkedin.com/in/hadelrachid',
            ],
            'align'    => 'right',
        ]);

        $this->assertStringContainsString('GitHub', $html);
        $this->assertStringContainsString('LinkedIn', $html);
        $this->assertStringContainsString('https://github.com/hadelrachid', $html);
        $this->assertStringContainsString('justify-content: flex-end', $html);
    }

    public function testCopyrightBlockRendering(): void
    {
        $block = new CopyrightBlock();
        $html = $block->render([
            'text'            => '© {year} Meu Site Inc.',
            'show_lgpd_links' => true,
        ]);

        $year = date('Y');
        $this->assertStringContainsString("© {$year} Meu Site Inc.", $html);
        $this->assertStringContainsString('/privacidade', $html);
        $this->assertStringContainsString('/termos', $html);
    }

    public function testNavMenuBlockWithRepositoryAndToggleStyles(): void
    {
        $providerMock = $this->createMock(MenuProviderInterface::class);
        $providerMock->method('getMenuItems')->with('header', null)->willReturn([
            ['title' => 'Home', 'url' => '/'],
            ['title' => 'Academy', 'url' => '/cursos'],
            ['title' => 'Contato', 'url' => '/contato'],
        ]);

        $block = new NavMenuBlock($providerMock);

        // 1. Teste com estilo PILL
        $htmlPill = $block->render([
            'menu_location' => 'header',
            'toggle_style'  => MobileToggleStyleInterface::STYLE_PILL,
        ]);

        $this->assertStringContainsString('Home', $htmlPill);
        $this->assertStringContainsString('Academy', $htmlPill);
        $this->assertStringContainsString('Contato', $htmlPill);
        $this->assertStringContainsString('ds-toggle-pill', $htmlPill);
        $this->assertStringContainsString('MENU', $htmlPill);
        $this->assertStringContainsString('ds-mobile-drawer', $htmlPill);

        // 2. Teste com fallback padrão (quando provider retorna vazio)
        $emptyProvider = $this->createMock(MenuProviderInterface::class);
        $emptyProvider->method('getMenuItems')->willReturn([]);
        $blockFallback = new NavMenuBlock($emptyProvider);
        $htmlFallback = $blockFallback->render([
            'menu_location' => 'header',
            'toggle_style'  => MobileToggleStyleInterface::STYLE_CLASSIC,
        ]);

        $this->assertStringContainsString('Início', $htmlFallback);
        $this->assertStringContainsString('Cursos', $htmlFallback);
        $this->assertStringContainsString('Painel', $htmlFallback);
        $this->assertStringContainsString('ds-toggle-classic', $htmlFallback);
    }
}
