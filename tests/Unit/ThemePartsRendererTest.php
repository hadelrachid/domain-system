<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Core\Theme\Parts\Blocks\DefaultBlockRegistryFactory;
use DomainSystem\Core\Theme\Parts\Contracts\MobileToggleStyleInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartInterface;
use DomainSystem\Core\Theme\Parts\Models\DesignTokens;
use DomainSystem\Core\Theme\Parts\Models\ThemePart;
use DomainSystem\Core\Theme\Parts\Grid\LayoutGridRenderer;
use DomainSystem\Core\Theme\Parts\Repositories\JsonThemePartAssociation;
use DomainSystem\Core\Theme\Parts\Repositories\JsonThemePartRepository;
use DomainSystem\Core\Theme\Parts\Services\ThemePartRenderer;
use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\SystemApps\nav_menus\Contracts\MenuRepositoryInterface;
use DomainSystem\SystemApps\nav_menus\Services\NavMenuProviderAdapter;
use PHPUnit\Framework\TestCase;

class ThemePartsRendererTest extends TestCase
{
    private string $tempDir;
    private string $themeMockDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/ds_renderer_test_' . uniqid('', true);
        @mkdir($this->tempDir . '/config/theme_parts', 0755, true);
        
        // Cria um tema mock para testar o fallback nativo
        $this->themeMockDir = $this->tempDir . '/themes/test_theme';
        @mkdir($this->themeMockDir . '/partials', 0755, true);
        file_put_contents($this->themeMockDir . '/partials/header.php', '<header id="native-header">Native Header File</header>');
        file_put_contents($this->themeMockDir . '/partials/footer.php', '<footer id="native-footer">Native Footer File</footer>');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    public function testRenderHeaderAndFooterWithDynamicParts(): void
    {
        $repo = new JsonThemePartRepository($this->tempDir . '/config/theme_parts');
        $assoc = new JsonThemePartAssociation($this->tempDir . '/config/associations.json');
        $grid = new LayoutGridRenderer();
        $tokens = new DesignTokens($this->tempDir . '/config/design_tokens.json');
        $registry = DefaultBlockRegistryFactory::create();

        // 1. Cria Header Pro 3 colunas
        $headerPart = new ThemePart(
            'header_pro_1',
            ThemePartInterface::TYPE_HEADER,
            'Header Pro Test',
            '3_cols_header',
            ['boxed' => true, 'gap' => 'medium'],
            [
                0 => [['type' => 'logo', 'data' => ['brand_text' => 'Domain<b>OS</b>']]],
                1 => [['type' => 'nav_menu', 'data' => ['menu_location' => 'header', 'toggle_style' => 'pill']]],
                2 => [['type' => 'cta_button', 'data' => ['label' => 'Entrar', 'url' => '/login']]],
            ],
            MobileToggleStyleInterface::STYLE_PILL
        );
        $repo->save($headerPart);

        // 2. Cria Footer Pro 1 coluna
        $footerPart = new ThemePart(
            'footer_pro_1',
            ThemePartInterface::TYPE_FOOTER,
            'Footer Pro Test',
            '1_col',
            ['boxed' => true],
            [
                0 => [
                    ['type' => 'copyright', 'data' => ['text' => '© {year} Empresa Teste', 'show_lgpd_links' => true]],
                    ['type' => 'social_icons', 'data' => ['networks' => ['github' => 'https://github.com']]],
                ],
            ]
        );
        $repo->save($footerPart);

        // 3. Atribui ao tema 'test_theme'
        $assoc->assignPart('test_theme', 'header', 'header_pro_1');
        $assoc->assignPart('test_theme', 'footer', 'footer_pro_1');

        $renderer = new ThemePartRenderer($repo, $assoc, $grid, $registry, $tokens, $this->tempDir);

        // 4. Renderiza Header
        $headerHtml = $renderer->renderHeader('test_theme');
        $this->assertStringContainsString('id="ds-design-tokens"', $headerHtml);
        $this->assertStringContainsString('--primary:', $headerHtml);
        $this->assertStringContainsString('Domain<b>OS</b>', $headerHtml);
        $this->assertStringContainsString('ds-toggle-pill', $headerHtml);
        $this->assertStringContainsString('Entrar', $headerHtml);

        // 5. Renderiza Footer
        $footerHtml = $renderer->renderFooter('test_theme');
        $this->assertStringContainsString('Empresa Teste', $footerHtml);
        $this->assertStringContainsString('/privacidade', $footerHtml);
        $this->assertStringContainsString('GitHub', $footerHtml);
    }

    public function testRenderHeaderAndFooterGracefulFallbackToFile(): void
    {
        $repo = new JsonThemePartRepository($this->tempDir . '/config/theme_parts');
        $assoc = new JsonThemePartAssociation($this->tempDir . '/config/associations.json');
        $grid = new LayoutGridRenderer();
        $tokens = new DesignTokens($this->tempDir . '/config/design_tokens.json');
        $registry = DefaultBlockRegistryFactory::create();

        // Não há nenhuma associação definida para 'test_theme'
        $renderer = new ThemePartRenderer($repo, $assoc, $grid, $registry, $tokens, $this->tempDir);

        // Renderiza Header (deve carregar o arquivo native partials/header.php)
        $headerHtml = $renderer->renderHeader('test_theme');
        $this->assertStringContainsString('Native Header File', $headerHtml);
        $this->assertStringContainsString('id="native-header"', $headerHtml);

        // Renderiza Footer (deve carregar o arquivo native partials/footer.php)
        $footerHtml = $renderer->renderFooter('test_theme');
        $this->assertStringContainsString('Native Footer File', $footerHtml);
        $this->assertStringContainsString('id="native-footer"', $footerHtml);
    }

    public function testNavMenuProviderAdapterConnectsToRepository(): void
    {
        $repoMock = $this->createMock(MenuRepositoryInterface::class);
        $repoMock->method('getMenuByLocation')->with('header')->willReturn([
            'id' => 99,
            'name' => 'Menu do Topo',
            'location' => 'header',
        ]);
        $repoMock->method('getMenuItems')->with(99)->willReturn([
            ['title' => 'Início', 'url' => '/'],
            ['title' => 'Blog', 'url' => '/blog'],
        ]);

        $adapter = new NavMenuProviderAdapter($repoMock);
        $items = $adapter->getMenuItems('header');

        $this->assertCount(2, $items);
        $this->assertSame('Início', $items[0]['title']);
        $this->assertSame('/blog', $items[1]['url']);
    }

    public function testThemeManagerIntegratesWithThemePartRenderer(): void
    {
        $repo = new JsonThemePartRepository($this->tempDir . '/config/theme_parts');
        $assoc = new JsonThemePartAssociation($this->tempDir . '/config/associations.json');
        $grid = new LayoutGridRenderer();
        $tokens = new DesignTokens($this->tempDir . '/config/design_tokens.json');
        $registry = DefaultBlockRegistryFactory::create();

        $headerPart = new ThemePart(
            'hdr_tm',
            ThemePartInterface::TYPE_HEADER,
            'ThemeManager Header',
            '1_col',
            [],
            [0 => [['type' => 'heading', 'data' => ['text' => 'Cabeçalho do ThemeManager']]]]
        );
        $repo->save($headerPart);
        $assoc->assignPart('test_theme', 'header', 'hdr_tm');

        $renderer = new ThemePartRenderer($repo, $assoc, $grid, $registry, $tokens, $this->tempDir);

        $tm = new ThemeManager($this->themeMockDir, null, $renderer);

        ob_start();
        $rendered = $tm->get_header();
        $output = ob_get_clean();

        $this->assertStringContainsString('Cabeçalho do ThemeManager', $rendered);
        $this->assertSame($rendered, $output);
    }
}
