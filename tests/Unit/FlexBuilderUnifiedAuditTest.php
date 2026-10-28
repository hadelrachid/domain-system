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
use DomainSystem\Core\Theme\Parts\Validation\ThemePartValidator;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Theme\ShortcodeManager;
use DomainSystem\Plugins\flextheme\Controllers\ThemePartsAdminController;
use DomainSystem\Plugins\flextheme\Services\MediaUploadService;
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
     * 3. LayoutGridRenderer higieniza CSS via ElementStyleRendererInterface e NUNCA oculta o container da seção inteira.
     */
    public function testLayoutGridRendererSanitizesCssValuesAndNeverHidesSectionContainer(): void
    {
        $grid = new LayoutGridRenderer();
        $html = $grid->renderSection('1_col', [0 => '<p>Coluna</p>'], [
            'background'   => '#112233; }</style><script>alert(1)</script>',
            'padding'      => '20px; expression(alert(1))',
            'hide_mobile'  => true,
            'hide_tablet'  => true,
            'hide_laptop'  => true,
            'hide_desktop' => true,
        ]);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('</style><script>', $html);
        // O container da seção jamais deve receber ds-hide-*; apenas elementos individuais podem ser ocultados
        $this->assertStringNotContainsString('ds-hide-mobile', $html);
        $this->assertStringNotContainsString('ds-hide-tablet', $html);
        $this->assertStringNotContainsString('ds-hide-laptop', $html);
        $this->assertStringNotContainsString('ds-hide-desktop', $html);
    }

    /**
     * 4. Paridade entre ThemePartPreviewService e ThemePartRenderer (getAllowedPartTypes e Visibilidade Responsiva de 4 Resoluções por Elemento).
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

        // Header com 2 elementos válidos ('logo' oculto em mobile/tablet e 'heading' oculto em laptop 1024px) e 1 inválido ('post_cards_grid')
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
                            ['type' => 'logo', 'data' => ['text' => 'MinhaMarca', 'hide_mobile' => true, 'hide_tablet' => true]],
                            ['type' => 'heading', 'data' => ['text' => 'Título Visível no Mobile', 'hide_laptop' => true]],
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

        // Ambos devem conter os elementos individuais com suas classes de resolução sem ocultar a seção
        $this->assertStringContainsString('MinhaMarca', $prodHtml);
        $this->assertStringContainsString('MinhaMarca', $previewHtml);
        $this->assertStringContainsString('Título Visível no Mobile', $prodHtml);
        $this->assertStringContainsString('ds-element-wrapper ds-hide-tablet ds-hide-mobile', $prodHtml);
        $this->assertStringContainsString('ds-element-wrapper ds-hide-tablet ds-hide-mobile', $previewHtml);
        $this->assertStringContainsString('ds-element-wrapper ds-hide-laptop', $prodHtml);
        $this->assertStringContainsString('ds-element-wrapper ds-hide-laptop', $previewHtml);
        $this->assertStringNotContainsString('ds-section-wrapper ds-hide-', $prodHtml);

        // Ambos devem bloquear post_cards_grid dentro de um Header
        $this->assertStringNotContainsString('ds-post-cards-grid', $prodHtml);
        $this->assertStringNotContainsString('ds-post-cards-grid', $previewHtml);
    }

    /**
     * 4b. Reajuste automático de dimensões do Grid quando uma coluna tem todos os seus elementos ocultados.
     */
    public function testGridReadjustsDimensionsWhenColumnElementsAreHidden(): void
    {
        $repo = new JsonThemePartRepository($this->tempDir . '/parts');
        $assoc = new JsonThemePartAssociation($this->tempDir . '/assoc.json');
        $tokens = new DesignTokens($this->tempDir . '/tokens.json');
        $grid = new LayoutGridRenderer();
        $registry = DefaultBlockRegistryFactory::create();
        $styleRenderer = new ElementStyleRenderer();
        $previewService = new ThemePartPreviewService($registry, $grid, $tokens, $styleRenderer);

        // Header de 3 colunas (1fr 2fr 1fr): Col 0 (Logo) oculto em 1024px (laptop), Col 0 e Col 2 ocultos em 768px (tablet)
        $payload = [
            'id'            => 'part_header_reflow',
            'type'          => ThemePartInterface::TYPE_HEADER,
            'title'         => 'Header Reflow',
            'layout_preset' => '3_cols_header',
            'sections'      => [
                [
                    'id'            => 'sec_1',
                    'layout_preset' => '3_cols_header',
                    'columns_data'  => [
                        0 => [
                            ['type' => 'logo', 'data' => ['text' => 'Logo', 'hide_laptop' => true, 'hide_tablet' => true]],
                        ],
                        1 => [
                            ['type' => 'nav_menu', 'data' => ['align' => 'center']],
                        ],
                        2 => [
                            ['type' => 'cta_button', 'data' => ['label' => 'Entrar', 'hide_tablet' => true]],
                        ],
                    ],
                ],
            ],
        ];

        $html = $previewService->renderPreview($payload);

        // Col 0 recebe ds-hide-laptop e ds-hide-tablet; Col 2 recebe ds-hide-tablet
        $this->assertStringContainsString('ds-grid-col ds-grid-col-0 ds-hide-laptop ds-hide-tablet', $html);
        $this->assertStringContainsString('ds-grid-col ds-grid-col-2 ds-hide-tablet', $html);

        // Em Desktop (todas as 3 visíveis): 1fr 2fr 1fr
        $this->assertStringContainsString('grid-template-columns: 1fr 2fr 1fr;', $html);
        // Em 1024px (Col 0 oculta, restam Col 1 e Col 2): reajusta automaticamente para 2fr 1fr!
        $this->assertStringContainsString('grid-template-columns: 2fr 1fr !important;', $html);
        // Em 768px (Col 0 e Col 2 ocultas, resta apenas Col 1): reajusta automaticamente para 1fr (100% da largura)!
        $this->assertStringContainsString('grid-template-columns: 1fr !important;', $html);
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

    /**
     * 10. Validação Estrutural Completa (Checklist ChatGPT: Itens 1, 2, 3, 4 e 11).
     * Rejeita tipo inválido, preset inexistente, bloco desconhecido, bloco proibido na parte e profundidade excessiva.
     */
    public function testValidatorRejectsInvalidPartTypePresetUnknownBlockForbiddenBlockAndExcessiveDepth(): void
    {
        $registry = DefaultBlockRegistryFactory::create();
        $grid = new LayoutGridRenderer();
        $validator = new ThemePartValidator($registry, $grid);

        // 10.1 Tipo de parte inválido
        try {
            $validator->validate(['type' => 'sidebar_invalido', 'layout_preset' => '1_col']);
            $this->fail('Deveria rejeitar tipo de parte inválido.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Tipo de parte visual inválido', $e->getMessage());
        }

        // 10.2 Preset inexistente
        try {
            $validator->validate(['type' => 'header', 'layout_preset' => '99_cols_inexistente']);
            $this->fail('Deveria rejeitar preset inexistente.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Preset de layout inexistente', $e->getMessage());
        }

        // 10.3 Bloco desconhecido
        try {
            $validator->validate([
                'type'          => 'header',
                'layout_preset' => '1_col',
                'columns_data'  => [
                    0 => [['type' => 'bloco_fantasma_xyz', 'data' => []]],
                ],
            ]);
            $this->fail('Deveria rejeitar bloco desconhecido.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Bloco desconhecido', $e->getMessage());
        }

        // 10.4 Bloco proibido no tipo de parte (post_cards_grid em header)
        try {
            $validator->validate([
                'type'          => 'header',
                'layout_preset' => '1_col',
                'columns_data'  => [
                    0 => [['type' => 'post_cards_grid', 'data' => []]],
                ],
            ]);
            $this->fail('Deveria rejeitar bloco incompatível com header.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('não é compatível com partes do tipo', $e->getMessage());
        }

        // 10.5 Profundidade excessiva nos dados do bloco (> 10 níveis)
        $deepData = ['val' => 'fim'];
        for ($i = 0; $i < 12; $i++) {
            $deepData = ['nested' => $deepData];
        }
        try {
            $validator->validate([
                'type'          => 'section',
                'layout_preset' => '1_col',
                'columns_data'  => [
                    0 => [['type' => 'heading', 'data' => $deepData]],
                ],
            ]);
            $this->fail('Deveria rejeitar estrutura excessivamente profunda.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('profundidade máxima', $e->getMessage());
        }
    }

    /**
     * 11. Compensação Transacional nas Associações (Checklist ChatGPT: Item 6).
     * Se uma atribuição falha após salvar a parte, o estado anterior é restaurado e uma exceção é lançada.
     */
    public function testManagementServiceCompensatesAndRollsBackWhenAssociationFailsAfterSave(): void
    {
        $repo = new JsonThemePartRepository($this->tempDir . '/parts');
        $failingAssoc = $this->createMock(\DomainSystem\Core\Theme\Parts\Contracts\ThemePartAssociationInterface::class);
        $failingAssoc->method('getGlobalDefault')->willReturn(null);
        $failingAssoc->method('getAssignedPartId')->willReturn(null);
        $failingAssoc->method('assignPart')->willReturn(false); // Simula falha ao gravar associação

        $discovery = new ThemeDiscoveryService($this->tempDir);
        $service = new ThemePartManagementService(
            $repo,
            $failingAssoc,
            $discovery,
            new ThemePartValidator(DefaultBlockRegistryFactory::create(), new LayoutGridRenderer())
        );

        try {
            $service->saveFromPayload([
                'id'             => 'part_rollback_test',
                'type'           => 'header',
                'title'          => 'Header que deve sofrer rollback',
                'layout_preset'  => '1_col',
                'assigned_theme' => 'default',
            ]);
            $this->fail('Deveria lançar RuntimeException quando assignPart falha.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Falha ao associar a parte visual ao tema', $e->getMessage());
        }

        // A parte recém-criada deve ter sido removida pela compensação!
        $this->assertNull($repo->findById('part_rollback_test'));
    }

    /**
     * 12. Whitelist Positiva de URLs e Sanitização XML de SVG via DOMDocument (Checklist ChatGPT: Itens 7 e 8).
     */
    public function testPositiveUrlSchemeWhitelistAndDomDocumentSvgSanitization(): void
    {
        $sanitizer = new SafeHtmlSanitizer();

        // Esquemas não listados na whitelist positiva devem ser bloqueados
        $this->assertSame('#', $sanitizer->sanitizeUrl('javascript:alert(1)'));
        $this->assertSame('#', $sanitizer->sanitizeUrl('data:text/html;base64,PHNjcmlwdD4='));
        $this->assertSame('#', $sanitizer->sanitizeUrl('file:///etc/passwd'));
        $this->assertSame('#', $sanitizer->sanitizeUrl('ftp://malicious.local/file'));
        $this->assertSame('#', $sanitizer->sanitizeUrl('customscheme:payload'));
        $this->assertSame('#', $sanitizer->sanitizeUrl('//evil-redirect.com/phish'));

        // Esquemas permitidos e caminhos relativos devem passar intactos
        $this->assertSame('https://rachidd.com/cursos', $sanitizer->sanitizeUrl('https://rachidd.com/cursos'));
        $this->assertSame('mailto:contato@rachidd.com', $sanitizer->sanitizeUrl('mailto:contato@rachidd.com'));
        $this->assertSame('tel:+5511999999999', $sanitizer->sanitizeUrl('tel:+5511999999999'));
        $this->assertSame('/sobre', $sanitizer->sanitizeUrl('/sobre'));
        $this->assertSame('#ancora', $sanitizer->sanitizeUrl('#ancora'));

        // MediaUploadService: bloqueio de XXE / DOCTYPE, XML malformado e tags perigosas em SVG
        $uploader = new MediaUploadService($this->tempDir, 'https://rachidd.com');

        $xxeSvg = '<?xml version="1.0"?><!DOCTYPE svg [ <!ENTITY xxe SYSTEM "file:///etc/passwd"> ]><svg xmlns="http://www.w3.org/2000/svg"><text>&xxe;</text></svg>';
        $this->assertSame('', $uploader->sanitizeSvgContent($xxeSvg));

        $malformedSvg = '<svg xmlns="http://www.w3.org/2000/svg"><g><path d="M0 0"</svg>';
        $this->assertSame('', $uploader->sanitizeSvgContent($malformedSvg));

        $dirtySvg = '<svg xmlns="http://www.w3.org/2000/svg" onload="evil()"><script>alert(1)</script><iframe src="https://evil.com"></iframe><a href="javascript:alert(2)"><path d="M10 10" onclick="hack()"/></a></svg>';
        $cleanSvg = $uploader->sanitizeSvgContent($dirtySvg);
        $this->assertNotEmpty($cleanSvg);
        $this->assertStringNotContainsString('<script', $cleanSvg);
        $this->assertStringNotContainsString('<iframe', $cleanSvg);
        $this->assertStringNotContainsString('onload', $cleanSvg);
        $this->assertStringNotContainsString('onclick', $cleanSvg);
        $this->assertStringNotContainsString('javascript:', $cleanSvg);
        $this->assertStringContainsString('<path d="M10 10"/>', $cleanSvg);
    }

    /**
     * 13. Isolamento HTTP via Request::withJsonBody() e Respostas de Erro Controladas (Checklist ChatGPT: Itens 5, 9 e 12).
     */
    public function testControllerUsesRequestJsonWithoutPhpInputAndReturnsControlledErrorResponses(): void
    {
        $repo = new JsonThemePartRepository($this->tempDir . '/parts');
        $assoc = new JsonThemePartAssociation($this->tempDir . '/assoc.json');
        $tokens = new DesignTokens($this->tempDir . '/tokens.json');
        $grid = new LayoutGridRenderer();
        $registry = DefaultBlockRegistryFactory::create();
        $styleRenderer = new ElementStyleRenderer();
        $renderer = new ThemePartRenderer($repo, $assoc, $grid, $registry, $tokens, $this->tempDir, $styleRenderer);
        $themeManager = $this->createMock(\DomainSystem\Core\Contracts\ThemeManagerInterface::class);
        $shortcodes = new ShortcodeManager();

        $controller = new ThemePartsAdminController(
            $repo,
            $assoc,
            $tokens,
            $registry,
            $grid,
            $renderer,
            $themeManager,
            $shortcodes,
            null,
            null,
            $this->tempDir
        );

        // 13.1 Sucesso via Request::withJsonBody (sem usar php://input)
        $validReq = (new Request())->withJsonBody([
            'id'            => 'part_via_json_body',
            'type'          => 'header',
            'title'         => 'Header Isolado',
            'layout_preset' => '3_cols_header',
            'columns_data'  => [
                0 => [['type' => 'logo', 'data' => ['text' => 'Marca']]],
            ],
        ]);
        $resOk = $controller->savePart($validReq);
        $this->assertSame(200, $resOk->getStatusCode());
        $dataOk = json_decode($resOk->getContent(), true);
        $this->assertTrue($dataOk['success']);
        $this->assertSame('part_via_json_body', $dataOk['id']);

        // 13.2 Erro de validação retorna HTTP 422
        $invalidReq = (new Request())->withJsonBody([
            'type'          => 'header',
            'layout_preset' => '1_col',
            'columns_data'  => [
                0 => [['type' => 'bloco_inexistente', 'data' => []]],
            ],
        ]);
        $res422 = $controller->savePart($invalidReq);
        $this->assertSame(422, $res422->getStatusCode());
        $data422 = json_decode($res422->getContent(), true);
        $this->assertFalse($data422['success']);

        // 13.3 Erro inesperado no Preview retorna HTTP 500 e NÃO vaza detalhes internos da exceção
        $explodingPreview = $this->createMock(\DomainSystem\Plugins\flextheme\Contracts\ThemePartPreviewServiceInterface::class);
        $explodingPreview->method('renderPreview')->willThrowException(new \Error('SegredoInternoDoServidor:/var/www/secret.php'));

        $controllerWithExplodingPreview = new ThemePartsAdminController(
            $repo,
            $assoc,
            $tokens,
            $registry,
            $grid,
            $renderer,
            $themeManager,
            $shortcodes,
            null,
            null,
            $this->tempDir,
            null,
            null,
            null,
            $styleRenderer,
            $explodingPreview
        );

        $resPreview500 = $controllerWithExplodingPreview->preview((new Request())->withJsonBody(['type' => 'section']));
        $this->assertSame(500, $resPreview500->getStatusCode());
        $this->assertStringNotContainsString('SegredoInternoDoServidor', $resPreview500->getContent());
        $this->assertStringContainsString('Não foi possível gerar a pré-visualização.', $resPreview500->getContent());
    }
}
