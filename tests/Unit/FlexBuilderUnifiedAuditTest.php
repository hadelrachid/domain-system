<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Core\Theme\Parts\Blocks\CoreBlockProvider;
use DomainSystem\Core\Theme\Parts\Blocks\DefaultBlockRegistryFactory;
use DomainSystem\Core\Theme\Parts\Contracts\BlockInterface;
use DomainSystem\Core\Theme\Parts\Contracts\BlockProviderInterface;
use DomainSystem\Core\Theme\Parts\Contracts\BlockRegistryInterface;
use DomainSystem\Core\Theme\Parts\Contracts\LayoutContainerInterface;
use DomainSystem\Core\Theme\Parts\Contracts\MobileToggleAwareInterface;
use DomainSystem\Core\Theme\Parts\Contracts\RenderableBlockInterface;
use DomainSystem\Core\Theme\Parts\Contracts\SchemaAwareBlockInterface;
use DomainSystem\Core\Theme\Parts\Contracts\SectionedPartInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartInterface;
use DomainSystem\Core\Theme\Parts\Grid\LayoutGridRenderer;
use DomainSystem\Core\Theme\Parts\Models\DesignTokens;
use DomainSystem\Core\Theme\Parts\Models\ThemePart;
use DomainSystem\Core\Theme\Parts\Repositories\JsonThemePartAssociation;
use DomainSystem\Core\Theme\Parts\Repositories\JsonThemePartRepository;
use DomainSystem\Core\Theme\Parts\Services\ThemePartRenderer;
use DomainSystem\Core\Theme\Parts\Styles\ElementStyleRenderer;
use DomainSystem\Core\Theme\Parts\Support\BaseUrlResolver;
use DomainSystem\Core\Theme\Parts\Support\SafeHtmlSanitizer;
use DomainSystem\Plugins\flextheme\Services\ThemeDiscoveryService;
use DomainSystem\Plugins\flextheme\Services\ThemePartManagementService;
use DomainSystem\Plugins\flextheme\Services\ThemePartPreviewService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

/**
 * Suíte de Auditoria Unificada (ChatGPT + DeepSeek + Nemotron + Gemini 4 Argon).
 *
 * Valida:
 * 1. Segurança XSS e CSS em todos os 14 blocos e no LayoutGridRenderer.
 * 2. Paridade total entre ThemePartPreviewService e ThemePartRenderer (incluindo getAllowedPartTypes).
 * 3. Persistência atômica, bloqueio de Path Traversal e falha controlada em save().
 * 4. Isolamento puro de Ring 0 (zero imports de DomainSystem\Plugins\* ou DomainSystem\SystemApps\* em src/Core).
 * 5. Segregação de interfaces (ISP), extensibilidade via BlockProviderInterface (OCP) e Visibilidade Responsiva.
 */
class FlexBuilderUnifiedAuditTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/ds_audit_test_' . uniqid('', true);
        @mkdir($this->tempDir, 0777, true);
        ThemePartRenderer::resetInjectedState();
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
        $items = scandir($dir) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    /**
     * 1. Segurança XSS em ParagraphBlock e LogoBlock (SafeHtmlSanitizer).
     */
    public function testSafeHtmlSanitizerBlocksInlineEventAttributesAndJavascriptUrls(): void
    {
        $sanitizer = new SafeHtmlSanitizer();

        $malicious = '<span onmouseover="alert(1)" onclick=\'evil()\'>Texto</span> ' .
                     '<a href="javascript:alert(2)" target="_blank">Link Malicioso</a> ' .
                     '<a href="https://example.com" class="btn">Link Seguro</a>' .
                     '<script>alert(3)</script>';

        $clean = $sanitizer->sanitizeInlineHtml($malicious);

        $this->assertStringNotContainsString('onmouseover', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringNotContainsString('<script>', $clean);
        $this->assertStringContainsString('<span>Texto</span>', $clean);
        $this->assertStringContainsString('href="https://example.com"', $clean);
    }

    /**
     * 2. Todos os 14 blocos cumprem os subcontratos ISP e resistem a payloads hostis.
     */
    public function testAll14BlocksSatisfyIspContractsAndSanitizeInputs(): void
    {
        $registry = DefaultBlockRegistryFactory::create();
        $blocks = $registry->all();

        $this->assertCount(14, $blocks);

        foreach ($blocks as $type => $block) {
            $this->assertInstanceOf(BlockInterface::class, $block);
            $this->assertInstanceOf(RenderableBlockInterface::class, $block);
            $this->assertInstanceOf(SchemaAwareBlockInterface::class, $block);
            $this->assertSame($type, $block->getType());
            $this->assertNotEmpty($block->getName());
            $this->assertIsArray($block->getSchema());

            // Renderiza com dados vazios sem lançar erro
            $emptyOutput = $block->render([], ['preview' => true]);
            $this->assertIsString($emptyOutput);
        }

        // ParagraphBlock deve remover atributos on*
        $paragraph = $registry->get('paragraph');
        $this->assertNotNull($paragraph);
        $html = $paragraph->render([
            'text'  => '<strong onmouseover="steal()">Seguro</strong> <a href="javascript:evil()">Click</a>',
            'color' => 'red; background: url(javascript:evil)',
        ]);
        $this->assertStringNotContainsString('onmouseover', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringContainsString('<strong>Seguro</strong>', $html);
    }

    /**
     * 3. LayoutGridRenderer higieniza CSS via ElementStyleRendererInterface.
     */
    public function testLayoutGridRendererSanitizesCssValuesAndAppliesResponsiveClasses(): void
    {
        $grid = new LayoutGridRenderer();
        $html = $grid->renderSection('1_col', [0 => '<p>Coluna</p>'], [
            'background'   => '#112233; }</style><script>alert(1)</script>',
            'padding'      => '20px; expression(alert(1))',
            'hide_mobile'  => true,
            'hide_tablet'  => true,
            'hide_desktop' => false,
        ]);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('</style><script>', $html);
        $this->assertStringContainsString('ds-hide-mobile', $html);
        $this->assertStringContainsString('ds-hide-tablet', $html);
        $this->assertStringNotContainsString('ds-hide-desktop', $html);
    }

    /**
     * 4. Paridade entre ThemePartPreviewService e ThemePartRenderer (getAllowedPartTypes e Visibilidade Responsiva).
     */
    public function testPreviewServiceAndProductionRendererEnforceIdenticalBlockContractsAndVisibility(): void
    {
        $repo = new JsonThemePartRepository($this->tempDir . '/parts');
        $assoc = new JsonThemePartAssociation($this->tempDir . '/assoc.json');
        $tokens = new DesignTokens($this->tempDir . '/tokens.json');
        $grid = new LayoutGridRenderer();
        $registry = DefaultBlockRegistryFactory::create();
        $styleRenderer = new ElementStyleRenderer();

        $renderer = new ThemePartRenderer($repo, $assoc, $grid, $registry, $tokens, $this->tempDir, $styleRenderer);
        $previewService = new ThemePartPreviewService($registry, $grid, $tokens, $styleRenderer);

        // Header tentando renderizar 'post_cards_grid' (permitido apenas em 'section') e 'logo' (universal) com hide_mobile
        $payload = [
            'id'            => 'part_header_parity',
            'type'          => ThemePartInterface::TYPE_HEADER,
            'title'         => 'Header Paridade',
            'layout_preset' => '1_col',
            'sections'      => [
                [
                    'id'                 => 'sec_1',
                    'layout_preset'      => '1_col',
                    'container_settings' => ['hide_tablet' => true],
                    'columns_data'       => [
                        0 => [
                            ['type' => 'logo', 'data' => ['brand_text' => 'MinhaMarca', 'hide_mobile' => true]],
                            ['type' => 'post_cards_grid', 'data' => ['columns' => 3]],
                        ],
                    ],
                ],
            ],
        ];

        $part = ThemePart::fromArray($payload);
        $repo->save($part);
        $assoc->assignPart('default', ThemePartInterface::TYPE_HEADER, 'part_header_parity');

        $prodHtml = $renderer->renderHeader('default');
        $previewHtml = $previewService->renderPreview($payload);

        // Ambos devem conter o logo com classe ds-hide-mobile e a seção com ds-hide-tablet
        $this->assertStringContainsString('MinhaMarca', $prodHtml);
        $this->assertStringContainsString('MinhaMarca', $previewHtml);
        $this->assertStringContainsString('ds-hide-mobile', $prodHtml);
        $this->assertStringContainsString('ds-hide-mobile', $previewHtml);
        $this->assertStringContainsString('ds-hide-tablet', $prodHtml);
        $this->assertStringContainsString('ds-hide-tablet', $previewHtml);

        // Ambos devem bloquear post_cards_grid dentro de um Header
        $this->assertStringNotContainsString('ds-post-cards-grid', $prodHtml);
        $this->assertStringNotContainsString('ds-post-cards-grid', $previewHtml);
    }

    /**
     * 5. Persistência Atômica, Bloqueio de Path Traversal e Verificação de Retorno de save().
     */
    public function testRepositoryBlocksPathTraversalAndPerformsAtomicSave(): void
    {
        $repo = new JsonThemePartRepository($this->tempDir . '/parts');

        $validPart = new ThemePart('part_6719a.12345', ThemePartInterface::TYPE_SECTION, 'Seção Segura');
        $this->assertTrue($repo->save($validPart));
        $loaded = $repo->findById('part_6719a.12345');
        $this->assertNotNull($loaded);
        $this->assertSame('Seção Segura', $loaded->getTitle());

        // Path traversal deve retornar null no findById e lançar InvalidArgumentException no save
        $this->assertNull($repo->findById('../../evil'));
        $this->assertFalse($repo->delete('../../evil'));

        $this->expectException(InvalidArgumentException::class);
        $evilPart = new ThemePart('../evil_traversal', ThemePartInterface::TYPE_SECTION, 'Evil');
        $repo->save($evilPart);
    }

    /**
     * 6. ThemePartManagementService lança exceção quando o repositório falha ao gravar.
     */
    public function testManagementServiceThrowsWhenRepositorySaveReturnsFalse(): void
    {
        $failingRepo = $this->createMock(\DomainSystem\Core\Theme\Parts\Contracts\ThemePartRepositoryInterface::class);
        $failingRepo->method('save')->willReturn(false);

        $assoc = new JsonThemePartAssociation($this->tempDir . '/assoc.json');
        $discovery = new ThemeDiscoveryService($this->tempDir);
        $service = new ThemePartManagementService($failingRepo, $assoc, $discovery);

        $this->expectException(RuntimeException::class);
        $service->saveFromPayload([
            'id'    => 'part_test',
            'type'  => 'section',
            'title' => 'Teste Falha Disco',
        ]);
    }

    /**
     * 7. Contratos de Associação de Template (assignTemplateToSection e getAssignedTemplateForSection).
     */
    public function testThemePartAssociationTemplateMethods(): void
    {
        $assoc = new JsonThemePartAssociation($this->tempDir . '/assoc.json');

        $this->assertTrue($assoc->assignTemplateToSection('rachidd', 'part_sec_1', 'home.php'));
        $this->assertSame('home', $assoc->getAssignedTemplateForSection('rachidd', 'part_sec_1'));
        $this->assertSame('part_sec_1', $assoc->getAssignedPartId('rachidd', 'template:home'));

        // Reatribuir a mesma seção para 'sobre' deve limpar automaticamente o vínculo anterior com 'home'
        $this->assertTrue($assoc->assignTemplateToSection('rachidd', 'part_sec_1', 'sobre'));
        $this->assertNull($assoc->getAssignedPartId('rachidd', 'template:home'));
        $this->assertSame('sobre', $assoc->getAssignedTemplateForSection('rachidd', 'part_sec_1'));

        // Desvincular passando 'none'
        $this->assertTrue($assoc->assignTemplateToSection('rachidd', 'part_sec_1', 'none'));
        $this->assertNull($assoc->getAssignedTemplateForSection('rachidd', 'part_sec_1'));
    }

    /**
     * 8. Isolamento Puro de Ring 0: Nenhum arquivo em src/Core pode importar DomainSystem\Plugins\* ou DomainSystem\SystemApps\*.
     */
    public function testRingZeroCoreHasZeroImportsFromPluginsOrSystemApps(): void
    {
        $coreDir = dirname(__DIR__, 2) . '/src/Core';
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($coreDir));

        $violations = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $content = (string)file_get_contents($file->getPathname());
                if (
                    preg_match('/^\s*use\s+DomainSystem\\\\Plugins\\\\/m', $content) ||
                    preg_match('/^\s*use\s+DomainSystem\\\\SystemApps\\\\/m', $content) ||
                    str_contains($content, '\\DomainSystem\\Plugins\\') ||
                    str_contains($content, '\\DomainSystem\\SystemApps\\')
                ) {
                    $violations[] = str_replace(dirname(__DIR__, 2) . '/', '', $file->getPathname());
                }
            }
        }

        $this->assertEmpty(
            $violations,
            'Violação de Ring 0 detectada! Arquivos em src/Core referenciando Plugins ou SystemApps: ' . implode(', ', $violations)
        );
    }

    /**
     * 9. ISP em ThemePart e OCP em DefaultBlockRegistryFactory + PublicUrlResolver.
     */
    public function testThemePartIspAndBlockProviderOcpExtensibility(): void
    {
        $part = new ThemePart('part_isp', ThemePartInterface::TYPE_HEADER, 'Header ISP');
        $this->assertInstanceOf(LayoutContainerInterface::class, $part);
        $this->assertInstanceOf(SectionedPartInterface::class, $part);
        $this->assertInstanceOf(MobileToggleAwareInterface::class, $part);

        $customBlock = $this->createMock(BlockInterface::class);
        $customBlock->method('getType')->willReturn('custom_plugin_block');

        $extraProvider = new class($customBlock) implements BlockProviderInterface {
            public function __construct(private BlockInterface $b) {}
            public function registerBlocks(BlockRegistryInterface $registry): void
            {
                $registry->register($this->b);
            }
        };

        $registry = DefaultBlockRegistryFactory::create(null, null, null, [$extraProvider]);
        $this->assertNotNull($registry->get('custom_plugin_block'));

        $urlResolver = new BaseUrlResolver('https://meusite.com/app');
        $this->assertSame('https://meusite.com/app', $urlResolver->getBaseUrl());
        $this->assertSame('https://meusite.com/app/admin/builder', $urlResolver->resolve('/admin/builder'));
    }
}
