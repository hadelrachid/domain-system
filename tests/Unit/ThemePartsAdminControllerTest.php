<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Core\Contracts\ThemeManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Http\Responses\ViewResponse;
use DomainSystem\Core\Theme\Parts\Blocks\DefaultBlockRegistryFactory;
use DomainSystem\Core\Theme\Parts\Contracts\MobileToggleStyleInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartInterface;
use DomainSystem\Core\Theme\Parts\Grid\LayoutGridRenderer;
use DomainSystem\Core\Theme\Parts\Models\DesignTokens;
use DomainSystem\Core\Theme\Parts\Models\ThemePart;
use DomainSystem\Core\Theme\Parts\Repositories\JsonThemePartAssociation;
use DomainSystem\Core\Theme\Parts\Repositories\JsonThemePartRepository;
use DomainSystem\Core\Theme\Parts\Services\ThemePartRenderer;
use DomainSystem\Core\Theme\ShortcodeManager;
use DomainSystem\Plugins\flextheme\Controllers\ThemePartsAdminController;
use PHPUnit\Framework\TestCase;

class ThemePartsAdminControllerTest extends TestCase
{
    private string $tempDir;
    private JsonThemePartRepository $partRepo;
    private JsonThemePartAssociation $partAssoc;
    private DesignTokens $tokens;
    private LayoutGridRenderer $layoutGrid;
    private ThemePartRenderer $renderer;
    private ShortcodeManager $shortcodeManager;
    private ThemePartsAdminController $controller;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/ds_ctrl_test_' . uniqid('', true);
        @mkdir($this->tempDir . '/config/theme_parts', 0755, true);
        @mkdir($this->tempDir . '/themes/default/partials', 0755, true);
        file_put_contents($this->tempDir . '/themes/default/theme.json', json_encode(['name' => 'Default Theme']));

        $this->partRepo = new JsonThemePartRepository($this->tempDir . '/config/theme_parts');
        $this->partAssoc = new JsonThemePartAssociation($this->tempDir . '/config/associations.json');
        $this->tokens = new DesignTokens($this->tempDir . '/config/design_tokens.json');
        $this->layoutGrid = new LayoutGridRenderer();
        $this->shortcodeManager = new ShortcodeManager();
        $this->shortcodeManager->add('custom_tag', fn() => 'Shortcode Content');

        $blockRegistry = DefaultBlockRegistryFactory::create(null, $this->shortcodeManager);

        $this->renderer = new ThemePartRenderer(
            $this->partRepo,
            $this->partAssoc,
            $this->layoutGrid,
            $blockRegistry,
            $this->tokens,
            $this->tempDir
        );

        $themeManagerMock = $this->createMock(ThemeManagerInterface::class);

        $this->controller = new ThemePartsAdminController(
            $this->partRepo,
            $this->partAssoc,
            $this->tokens,
            $blockRegistry,
            $this->layoutGrid,
            $this->renderer,
            $themeManagerMock,
            $this->shortcodeManager,
            null,
            null,
            $this->tempDir
        );
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    public function testIndexListsGroupedParts(): void
    {
        $this->partRepo->save(new ThemePart('h1', ThemePartInterface::TYPE_HEADER, 'Header Principal'));
        $this->partRepo->save(new ThemePart('f1', ThemePartInterface::TYPE_FOOTER, 'Footer Principal'));
        $this->partRepo->save(new ThemePart('s1', ThemePartInterface::TYPE_SECTION, 'Hero Section'));

        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/builder']);
        $response = $this->controller->index($request);

        $this->assertInstanceOf(ViewResponse::class, $response);
        $this->assertTrue($response->shouldWrapLayout());
        $content = $response->getContent();
        $this->assertStringContainsString('Flex Builder', $content);
        $this->assertStringContainsString('Header Principal', $content);
        $this->assertStringContainsString('Footer Principal', $content);
        $this->assertStringContainsString('Hero Section', $content);
    }

    public function testCreateRendersStudioForNewPart(): void
    {
        $request = new Request(['type' => 'header'], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/builder/create']);
        $response = $this->controller->create($request);

        $this->assertInstanceOf(ViewResponse::class, $response);
        $this->assertFalse($response->shouldWrapLayout()); // Studio full screen
        $content = $response->getContent();
        $this->assertStringContainsString('Flex Builder Studio', $content);
        $this->assertStringContainsString('3_cols_header', $content);
    }

    public function testCreateWithMenuProviderPopulatesMenus(): void
    {
        $menuMock = $this->createMock(\DomainSystem\Core\Theme\Parts\Contracts\MenuProviderInterface::class);
        $menuMock->method('getMenus')->willReturn([
            ['id' => 1, 'name' => 'Menu Principal', 'location' => 'header'],
        ]);

        $ctrlWithMenu = new ThemePartsAdminController(
            $this->partRepo,
            $this->partAssoc,
            $this->tokens,
            DefaultBlockRegistryFactory::create(null, $this->shortcodeManager),
            $this->layoutGrid,
            $this->renderer,
            $this->createMock(ThemeManagerInterface::class),
            $this->shortcodeManager,
            $menuMock,
            null,
            $this->tempDir
        );

        $request = new Request(['type' => 'header'], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/builder/create']);
        $response = $ctrlWithMenu->create($request);

        $this->assertInstanceOf(ViewResponse::class, $response);
        $content = $response->getContent();
        $this->assertStringContainsString('Menu Principal', $content);
    }

    public function testEditLoadsExistingPart(): void
    {
        $part = new ThemePart(
            'header_custom_1',
            ThemePartInterface::TYPE_HEADER,
            'Meu Header Especial',
            '3_cols_header'
        );
        $this->partRepo->save($part);

        $request = new Request(['id' => 'header_custom_1'], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/builder/edit']);
        $response = $this->controller->edit($request);

        $this->assertInstanceOf(ViewResponse::class, $response);
        $content = $response->getContent();
        $this->assertStringContainsString('Meu Header Especial', $content);
        $this->assertStringContainsString('header_custom_1', $content);
    }

    public function testEditRedirectsWhenNotFound(): void
    {
        $request = new Request(['id' => 'inexistente'], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/builder/edit']);
        $response = $this->controller->edit($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(302, $response->getStatusCode());
    }

    public function testSavePartPersistsAndUpdatesAssociations(): void
    {
        $payload = [
            'id'                  => 'header_novo_123',
            'type'                => 'header',
            'title'               => 'Header Teste Ajax',
            'layout_preset'       => '3_cols_header',
            'container_settings'  => ['max_width' => '1200px'],
            'columns_data'        => [
                0 => [['type' => 'logo', 'data' => ['text' => 'DomainSystem']]],
                1 => [['type' => 'heading', 'data' => ['text' => 'Bem-vindo', 'level' => 'h2']]],
            ],
            'mobile_toggle_style' => MobileToggleStyleInterface::STYLE_MINIMAL,
            'is_default'          => true,
            'assigned_theme'      => 'default',
        ];

        $request = new Request([], $payload, ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/admin/builder/save-part']);
        $response = $this->controller->savePart($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertSame('header_novo_123', $data['id']);

        // Verifica se persistiu no repositório
        $saved = $this->partRepo->findById('header_novo_123');
        $this->assertNotNull($saved);
        $this->assertSame('Header Teste Ajax', $saved->getTitle());
        $this->assertSame(MobileToggleStyleInterface::STYLE_MINIMAL, $saved->getMobileToggleStyle());

        // Verifica se associou como padrão global e ao tema default
        $this->assertSame('header_novo_123', $this->partAssoc->getGlobalDefault('header'));
        $this->assertSame('header_novo_123', $this->partAssoc->getAssignedPartId('default', 'header'));
    }

    public function testDeletePartRemovesFromRepository(): void
    {
        $part = new ThemePart('part_to_delete', ThemePartInterface::TYPE_FOOTER, 'Footer Antigo');
        $this->partRepo->save($part);
        $this->assertNotNull($this->partRepo->findById('part_to_delete'));

        $request = new Request([], ['id' => 'part_to_delete'], [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/admin/builder/delete-part',
            'HTTP_ACCEPT'    => 'application/json',
        ]);
        $response = $this->controller->deletePart($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNull($this->partRepo->findById('part_to_delete'));
    }

    public function testAssignPartUpdatesThemeAndGlobal(): void
    {
        $request = new Request([], [
            'theme_folder' => 'default',
            'part_type'    => 'header',
            'part_id'      => 'h_assigned_1',
            'is_global'    => false,
        ], [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/admin/builder/assign',
            'HTTP_ACCEPT'    => 'application/json',
        ]);

        $response = $this->controller->assign($request);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('h_assigned_1', $this->partAssoc->getAssignedPartId('default', 'header'));

        // Testa definir como padrão global
        $requestGlobal = new Request([], [
            'part_type' => 'footer',
            'part_id'   => 'f_global_1',
            'is_global' => true,
        ], [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/admin/builder/assign',
            'HTTP_ACCEPT'    => 'application/json',
        ]);

        $this->controller->assign($requestGlobal);
        $this->assertSame('f_global_1', $this->partAssoc->getGlobalDefault('footer'));
    }

    public function testTokensViewAndSave(): void
    {
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/builder/tokens']);
        $view = $this->controller->tokens($request);
        $this->assertInstanceOf(ViewResponse::class, $view);
        $this->assertStringContainsString('Design Tokens Globais', $view->getContent());

        // Salvar novos tokens
        $saveRequest = new Request([], [
            'colors' => [
                'primary'   => '#ff0055',
                'secondary' => '#00ffcc',
            ],
            'typography' => [
                'primary_font'   => 'Roboto',
                'secondary_font' => 'Poppins',
            ],
        ], [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/admin/builder/tokens/save',
            'HTTP_ACCEPT'    => 'application/json',
        ]);

        $saveResponse = $this->controller->saveTokens($saveRequest);
        $this->assertSame(200, $saveResponse->getStatusCode());

        $palette = $this->tokens->getColorPalette();
        $this->assertSame('#ff0055', $palette['primary']);
        $this->assertSame('Roboto', $this->tokens->getTypography()['primary_font']);
    }

    public function testPreviewRendersHtmlWithDesignTokens(): void
    {
        $payload = [
            'layout_preset'       => '2_cols_equal',
            'container_settings'  => ['max_width' => '1000px'],
            'columns_data'        => [
                0 => [['type' => 'heading', 'data' => ['text' => 'Título do Preview', 'level' => 'h2']]],
                1 => [['type' => 'cta_button', 'data' => ['label' => 'Clique Aqui', 'url' => '/teste']]],
            ],
            'mobile_toggle_style' => 'pill',
        ];

        $request = new Request([], $payload, ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/admin/builder/preview']);
        $response = $this->controller->preview($request);

        $this->assertSame(200, $response->getStatusCode());
        $html = $response->getContent();
        $this->assertStringContainsString('<style id="ds-design-tokens">', $html);
        $this->assertStringContainsString('Título do Preview', $html);
        $this->assertStringContainsString('Clique Aqui', $html);
    }

    public function testSchemaExposesAll12BlocksPresetsShortcodesAndMenus(): void
    {
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/builder/schema']);
        $response = $this->controller->schema($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('blocks', $data);
        $this->assertCount(12, $data['blocks']);
        $this->assertArrayHasKey('heading', $data['blocks']);
        $this->assertArrayHasKey('nav_menu', $data['blocks']);
        $this->assertArrayHasKey('shortcode', $data['blocks']);

        $this->assertArrayHasKey('presets', $data);
        $this->assertArrayHasKey('3_cols_header', $data['presets']);

        $this->assertArrayHasKey('shortcodes', $data);
        $this->assertArrayHasKey('toggle_styles', $data);
        $this->assertArrayHasKey('tokens', $data);
    }
}
