<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Core\Theme\Parts\Blocks\ButtonBlock;
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
use DomainSystem\Core\Theme\Parts\Blocks\PostCardsGridBlock;
use DomainSystem\Core\Theme\Parts\Blocks\ShortcodeBlock;
use DomainSystem\Core\Theme\Parts\Blocks\SocialIconsBlock;
use DomainSystem\Core\Theme\Parts\Blocks\VideoBlock;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartInterface;
use DomainSystem\Core\Theme\Parts\Grid\LayoutGridRenderer;
use DomainSystem\Core\Theme\Parts\Models\DesignTokens;
use DomainSystem\Core\Theme\Parts\Models\ThemePart;
use DomainSystem\Core\Theme\Parts\Repositories\JsonThemePartAssociation;
use DomainSystem\Core\Theme\Parts\Repositories\JsonThemePartRepository;
use DomainSystem\Core\Theme\Parts\Services\ThemePartRenderer;
use DomainSystem\Core\Theme\ShortcodeManager;
use DomainSystem\Plugins\flextheme\BuilderFlex\Core\DtoRenderer;
use DomainSystem\Plugins\flextheme\Contracts\ThemeDiscoveryServiceInterface;
use DomainSystem\Plugins\flextheme\Services\MediaUploadService;
use DomainSystem\Plugins\flextheme\Services\ThemePartManagementService;
use PHPUnit\Framework\TestCase;

/**
 * Suíte de Testes da Auditoria Cirúrgica Round 2 do Ecossistema Flex Builder:
 * - Alinhamento de Propriedades Studio JS ↔ Blocos PHP
 * - Proteção contra Recursão Infinita ([section] / ThemePartRenderer)
 * - Limpeza de Associações Órfãs em deletePart()
 * - Sanitização de SVG no MediaUploadService
 * - Escrita Atômica e Sanitização CSS em DesignTokens
 * - Bloqueio de javascript: e CSS Injection nos blocos e no DtoRenderer
 * - Ausência de Service Locators ($GLOBALS['app']) em NavMenuBlock, ShortcodeBlock e ThemeController
 */
class FlexBuilderRound2AuditTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/ds_round2_audit_' . uniqid('', true);
        @mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    public function testStudioInspectorPropertiesMatchPhpBlocks(): void
    {
        // 1. LogoBlock aceita 'text' e 'url' (usados pelo Studio Inspector) além de 'brand_text' e 'link'
        $logo = new LogoBlock();
        $logoHtml = $logo->render([
            'text'       => 'MinhaMarca<strong>Pro</strong>',
            'url'        => '/inicio',
            'show_image' => false,
            'show_text'  => true,
        ]);
        $this->assertStringContainsString('MinhaMarca<strong>Pro</strong>', $logoHtml);
        $this->assertStringContainsString('href="/inicio"', $logoHtml);

        // 2. HeadingBlock aceita level como string ('h3') e 'font_size' (usados pelo Studio Inspector)
        $heading = new HeadingBlock();
        $headingHtml = $heading->render([
            'text'        => 'Subtítulo H3',
            'level'       => 'h3',
            'font_size'   => '2.4rem',
            'font_family' => 'var(--font-secondary)',
        ]);
        $this->assertStringContainsString('<h3 class="ds-block-heading ds-heading-3"', $headingHtml);
        $this->assertStringContainsString('font-size: 2.4rem', $headingHtml);
        $this->assertStringContainsString('font-family: var(--font-secondary)', $headingHtml);

        // 3. ParagraphBlock aceita 'font_size' e 'font_family'
        $paragraph = new ParagraphBlock();
        $paraHtml = $paragraph->render([
            'text'        => 'Texto corrido do parágrafo.',
            'font_size'   => '1.15rem',
            'font_family' => 'var(--font-secondary)',
        ]);
        $this->assertStringContainsString('font-size: 1.15rem', $paraHtml);
        $this->assertStringContainsString('font-family: var(--font-secondary)', $paraHtml);

        // 4. CustomCodeBlock aceita 'code' (usado pelo Studio Inspector) além de 'html'/'css'
        $customCode = new CustomCodeBlock();
        $codeHtml = $customCode->render([
            'code' => '<div class="custom-widget">Widget Custom</div>',
            'css'  => '.custom-widget { color: red; }</style><script>alert(1)</script>',
        ]);
        $this->assertStringContainsString('<div class="custom-widget">Widget Custom</div>', $codeHtml);
        $this->assertStringNotContainsString('</style><script>', $codeHtml);

        // 5. ShortcodeBlock aceita 'tag' e 'code'
        $shortcode = new ShortcodeBlock();
        $this->assertSame('[meu_shortcode]', $shortcode->render(['tag' => '[meu_shortcode]']));
        $this->assertSame('[outro_shortcode]', $shortcode->render(['code' => '[outro_shortcode]']));

        // 6. CopyrightBlock respeita 'site_name'
        $copyright = new CopyrightBlock();
        $copyHtml = $copyright->render([
            'site_name' => 'Corporação Acme',
            'text'      => 'Todos os direitos reservados.',
        ]);
        $this->assertStringContainsString('Corporação Acme', $copyHtml);

        // 7. CtaButtonBlock respeita 'hover_animation', 'bg_color', 'text_color' e 'padding'
        $cta = new CtaButtonBlock();
        $ctaHtml = $cta->render([
            'label'           => 'Assinar Agora',
            'url'             => 'https://example.com',
            'hover_animation' => 'lift',
            'bg_color'        => '#10b981',
            'text_color'      => '#ffffff',
            'padding'         => '16px 32px',
        ]);
        $this->assertStringContainsString('ds-btn-lift', $ctaHtml);
        $this->assertStringContainsString('background: #10b981;', $ctaHtml);
        $this->assertStringContainsString('color: #ffffff;', $ctaHtml);
        $this->assertStringContainsString('padding: 16px 32px;', $ctaHtml);

        // 8. DividerBlock aceita 'transparent' como espaçador invisível (igual a 'spacer')
        $divider = new DividerBlock();
        $divHtml = $divider->render(['style' => 'transparent', 'height' => '32px']);
        $this->assertStringContainsString('ds-block-spacer', $divHtml);
        $this->assertStringContainsString('height: 32px;', $divHtml);
    }

    public function testInfiniteRecursionGuardInThemePartRenderer(): void
    {
        $partsDir = $this->tempDir . '/parts';
        $assocFile = $this->tempDir . '/assoc.json';
        $tokensFile = $this->tempDir . '/tokens.json';

        $repo = new JsonThemePartRepository($partsDir);
        $assoc = new JsonThemePartAssociation($assocFile);
        $tokens = new DesignTokens($tokensFile);
        $grid = new LayoutGridRenderer();

        $sm = new ShortcodeManager();
        $registry = DefaultBlockRegistryFactory::create(null, $sm);
        $renderer = new ThemePartRenderer($repo, $assoc, $grid, $registry, $tokens, $this->tempDir);

        // Registra shortcode [section id="..."] que chama o próprio ThemePartRenderer
        $sm->add('section', function (array $attrs) use ($renderer): string {
            $id = (string)($attrs['id'] ?? '');
            return $renderer->renderSection($id);
        });

        // Cria uma seção que referencia a si mesma via ShortcodeBlock ([section id="sec_loop"])
        $selfRefPart = new ThemePart(
            'sec_loop',
            ThemePartInterface::TYPE_SECTION,
            'Seção Recursiva',
            '1_col',
            [],
            [
                0 => [
                    ['type' => 'heading', 'data' => ['text' => 'Topo da Seção']],
                    ['type' => 'shortcode', 'data' => ['code' => '[section id="sec_loop"]']],
                ],
            ]
        );
        $repo->save($selfRefPart);

        $output = $renderer->renderSection('sec_loop');
        $this->assertStringContainsString('Topo da Seção', $output);
        $this->assertStringContainsString('<!-- Recursion guard triggered for part: sec_loop -->', $output);
    }

    public function testDeletePartRemovesOrphanedAssociations(): void
    {
        $partsDir = $this->tempDir . '/parts';
        $assocFile = $this->tempDir . '/assoc.json';

        $repo = new JsonThemePartRepository($partsDir);
        $assoc = new JsonThemePartAssociation($assocFile);

        $discovery = new class implements ThemeDiscoveryServiceInterface {
            public function getAvailableThemes(): array { return []; }
            public function getActiveThemeFolder(): string { return 'default'; }
            public function getThemeTemplates(string $themeFolder): array { return ['home' => 'home.php']; }
        };

        $service = new ThemePartManagementService($repo, $assoc, $discovery);

        $savedPart = $service->saveFromPayload([
            'id'                => 'sec_hero_home',
            'type'              => ThemePartInterface::TYPE_SECTION,
            'title'             => 'Hero Home',
            'is_default'        => true,
            'assigned_theme'    => 'default',
            'assigned_template' => 'home',
        ]);

        $this->assertSame('sec_hero_home', $assoc->getGlobalDefault(ThemePartInterface::TYPE_SECTION));
        $this->assertSame('home', $assoc->getAssignedTemplateForSection('default', $savedPart->getId()));

        // Ao excluir a parte, todas as referências em globals e themes devem ser removidas
        $deleted = $service->deletePart('sec_hero_home');
        $this->assertTrue($deleted);
        $this->assertNull($repo->findById('sec_hero_home'));
        $this->assertNull($assoc->getGlobalDefault(ThemePartInterface::TYPE_SECTION));
        $this->assertNull($assoc->getAssignedTemplateForSection('default', 'sec_hero_home'));
    }

    public function testMediaUploadServiceSanitizesMaliciousSvg(): void
    {
        $service = new MediaUploadService($this->tempDir, 'http://localhost');

        $maliciousSvgPath = $this->tempDir . '/evil.svg';
        $svgPayload = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" onload="alert('xss')">
    <script type="text/javascript">alert('stealCookies()');</script>
    <foreignObject><iframe src="javascript:alert(1)"></iframe></foreignObject>
    <a href="javascript:evil()" xlink:href="data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==">
        <circle cx="50" cy="50" r="40" fill="#38bdf8" onclick="hack()" />
    </a>
</svg>
SVG;
        file_put_contents($maliciousSvgPath, $svgPayload);

        $result = $service->upload([
            'name'     => 'logo.svg',
            'tmp_name' => $maliciousSvgPath,
            'error'    => UPLOAD_ERR_OK,
            'size'     => filesize($maliciousSvgPath),
        ]);

        $this->assertTrue($result['success']);
        $savedFile = $this->tempDir . '/public/uploads/builder/' . $result['filename'];
        $this->assertFileExists($savedFile);

        $savedContent = (string)file_get_contents($savedFile);
        $this->assertStringContainsString('<circle cx="50" cy="50" r="40" fill="#38bdf8"', $savedContent);
        $this->assertStringNotContainsString('<script', $savedContent);
        $this->assertStringNotContainsString('<foreignObject', $savedContent);
        $this->assertStringNotContainsString('onload=', $savedContent);
        $this->assertStringNotContainsString('onclick=', $savedContent);
        $this->assertStringNotContainsString('javascript:', $savedContent);
        $this->assertStringNotContainsString('data:text/html', $savedContent);
    }

    public function testDesignTokensAtomicSaveAndCssSanitization(): void
    {
        $tokensFile = $this->tempDir . '/design_tokens.json';
        $tokens = new DesignTokens($tokensFile);

        $updated = $tokens->updateTokens([
            'palette' => [
                'primary' => '#38bdf8; } </style><script>alert(1)</script>',
            ],
            'typography' => [
                'primary_font' => 'Inter, sans-serif; background: url(javascript:alert(1))',
            ],
        ]);

        $this->assertTrue($updated);
        $this->assertFileExists($tokensFile);

        $css = $tokens->generateCssVariables('.custom-scope; } <script>');
        $this->assertStringNotContainsString('</style>', $css);
        $this->assertStringNotContainsString('<script>', $css);
        $this->assertStringNotContainsString('javascript:', $css);
    }

    public function testBlocksAndDtoRendererBlockJavascriptUrlsAndCssInjection(): void
    {
        $image = new ImageBlock();
        $this->assertSame('', $image->render(['url' => 'javascript:alert(1)']));

        $imgWithBadLink = $image->render([
            'url'    => 'https://example.com/photo.png',
            'link'   => 'javascript:alert(1)',
            'height' => '100px; background:url(javascript:alert(1))',
        ]);
        $this->assertStringNotContainsString('javascript:', $imgWithBadLink);

        $video = new VideoBlock();
        $this->assertSame('', $video->render(['url' => 'javascript:alert(1)']));

        $btn = new ButtonBlock();
        $btnHtml = $btn->render([
            'action_type' => ButtonBlock::ACTION_LINK,
            'url'         => 'javascript:alert(1)',
        ]);
        $this->assertStringContainsString('href="#"', $btnHtml);
        $this->assertStringNotContainsString('javascript:', $btnHtml);

        $social = new SocialIconsBlock();
        $socialHtml = $social->render([
            'networks' => [
                ['network' => 'instagram', 'url' => 'javascript:alert(1)'],
            ],
        ]);
        $this->assertStringContainsString('href="#"', $socialHtml);
        $this->assertStringNotContainsString('javascript:', $socialHtml);

        $posts = new PostCardsGridBlock();
        $postsHtml = $posts->render([
            'manual_posts' => [
                [
                    'title'          => 'Post Seguro',
                    'url'            => 'javascript:alert(1)',
                    'featured_image' => 'javascript:alert(2)',
                ],
            ],
        ]);
        $this->assertStringNotContainsString('javascript:', $postsHtml);

        $dtoRenderer = new DtoRenderer();
        $dtoHtml = $dtoRenderer->render([
            'tag'     => 'div',
            'attrs'   => [
                'class'   => 'box',
                'onclick' => 'alert(1)',
                'href'    => 'javascript:alert(1)',
            ],
            'styles'  => [
                'color' => '#fff; background: url(javascript:alert(1))',
            ],
            'content' => '<strong> Seguro </strong><script>alert(1)</script><a href="javascript:evil()">Link</a>',
        ]);
        $this->assertStringContainsString('<strong> Seguro </strong>', $dtoHtml);
        $this->assertStringNotContainsString('<script>', $dtoHtml);
        $this->assertStringNotContainsString('onclick=', $dtoHtml);
        $this->assertStringNotContainsString('javascript:', $dtoHtml);
    }

    public function testZeroServiceLocatorGlobalsInBlocksAndThemeController(): void
    {
        $filesToCheck = [
            dirname(__DIR__, 2) . '/src/Core/Theme/Parts/Blocks/NavMenuBlock.php',
            dirname(__DIR__, 2) . '/src/Core/Theme/Parts/Blocks/ShortcodeBlock.php',
            dirname(__DIR__, 2) . '/src/Plugins/flextheme/Controllers/ThemeController.php',
        ];

        foreach ($filesToCheck as $file) {
            $code = (string)file_get_contents($file);
            $this->assertStringNotContainsString(
                "\$GLOBALS['app']",
                $code,
                "O arquivo {$file} não deve utilizar o Service Locator \$GLOBALS['app']."
            );
        }
    }
}
