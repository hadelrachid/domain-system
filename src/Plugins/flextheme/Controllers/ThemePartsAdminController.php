<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\flextheme\Controllers;

use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Contracts\ThemeManagerInterface;
use DomainSystem\Core\Contracts\TranslatorInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Http\Responses\ViewResponse;
use DomainSystem\Core\I18n\Translator;
use DomainSystem\Core\Theme\Parts\Contracts\BlockRegistryInterface;
use DomainSystem\Core\Theme\Parts\Contracts\DesignTokensInterface;
use DomainSystem\Core\Theme\Parts\Contracts\LayoutGridInterface;
use DomainSystem\Core\Theme\Parts\Contracts\MenuProviderInterface;
use DomainSystem\Core\Theme\Parts\Contracts\MobileToggleStyleInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartAssociationInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartRendererInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartRepositoryInterface;
use DomainSystem\Core\Theme\Parts\Models\ThemePart;
use DomainSystem\Core\Theme\ShortcodeManager;
use DomainSystem\Plugins\flextheme\Contracts\MediaUploadServiceInterface;
use DomainSystem\Plugins\flextheme\Contracts\ThemeDiscoveryServiceInterface;
use DomainSystem\Plugins\flextheme\Services\MediaUploadService;
use DomainSystem\Plugins\flextheme\Services\ThemeDiscoveryService;
use Throwable;

/**
 * Controller Administrativo do Novo Flex Builder (Motor em Blocos & Design Tokens).
 *
 * Responsável pela gestão de Headers, Footers, Seções e Design Tokens do Domain System.
 * Implementa arquitetura SOLID com contratos Ring 0 / Ring 1 e simulação responsiva.
 */
class ThemePartsAdminController
{
    private ThemePartRepositoryInterface $partRepo;
    private ThemePartAssociationInterface $partAssoc;
    private DesignTokensInterface $tokens;
    private BlockRegistryInterface $blockRegistry;
    private LayoutGridInterface $layoutGrid;
    private ThemePartRendererInterface $renderer;
    private ThemeManagerInterface $themeManager;
    private ShortcodeManager $shortcodeManager;
    private ?MenuProviderInterface $menuProvider;
    private ?SessionManagerInterface $session;
    private string $basePath;
    private ThemeDiscoveryServiceInterface $themeDiscovery;
    private MediaUploadServiceInterface $mediaUpload;
    private TranslatorInterface $translator;

    public function __construct(
        ThemePartRepositoryInterface $partRepo,
        ThemePartAssociationInterface $partAssoc,
        DesignTokensInterface $tokens,
        BlockRegistryInterface $blockRegistry,
        LayoutGridInterface $layoutGrid,
        ThemePartRendererInterface $renderer,
        ThemeManagerInterface $themeManager,
        ShortcodeManager $shortcodeManager,
        ?MenuProviderInterface $menuProvider = null,
        ?SessionManagerInterface $session = null,
        ?string $basePath = null,
        ?ThemeDiscoveryServiceInterface $themeDiscovery = null,
        ?MediaUploadServiceInterface $mediaUpload = null,
        ?TranslatorInterface $translator = null
    ) {
        $this->partRepo         = $partRepo;
        $this->partAssoc        = $partAssoc;
        $this->tokens           = $tokens;
        $this->blockRegistry    = $blockRegistry;
        $this->layoutGrid       = $layoutGrid;
        $this->renderer         = $renderer;
        $this->themeManager     = $themeManager;
        $this->shortcodeManager = $shortcodeManager;
        $this->menuProvider     = $menuProvider;
        $this->session          = $session;
        $this->basePath         = $basePath ?? (defined('DOMAIN_SYSTEM_ROOT') ? DOMAIN_SYSTEM_ROOT : dirname(__DIR__, 4));
        $this->themeDiscovery   = $themeDiscovery ?? new ThemeDiscoveryService($this->basePath);
        $this->mediaUpload      = $mediaUpload ?? new MediaUploadService($this->basePath);
        $this->translator       = $translator ?? new Translator($this->basePath . '/resources/lang');
    }

    /**
     * Lista todas as partes (Headers, Footers, Seções) e a matriz de associação de temas.
     */
    public function index(Request $request): ViewResponse
    {
        $allParts = $this->partRepo->all();

        $headers = [];
        $footers = [];
        $sections = [];

        foreach ($allParts as $part) {
            match ($part->getType()) {
                ThemePartInterface::TYPE_HEADER  => $headers[] = $part,
                ThemePartInterface::TYPE_FOOTER  => $footers[] = $part,
                ThemePartInterface::TYPE_SECTION => $sections[] = $part,
                default => null,
            };
        }

        $themes = $this->getAvailableThemes();
        $activeTheme = $this->getActiveThemeFolder();
        $assignments = $this->partAssoc->getAllAssignments();
        $globalHeader = $this->partAssoc->getGlobalDefault(ThemePartInterface::TYPE_HEADER);
        $globalFooter = $this->partAssoc->getGlobalDefault(ThemePartInterface::TYPE_FOOTER);

        return $this->renderView('admin/parts_list', [
            'headers'      => $headers,
            'footers'      => $footers,
            'sections'     => $sections,
            'themes'       => $themes,
            'activeTheme'  => $activeTheme,
            'assignments'  => $assignments,
            'globalHeader' => $globalHeader,
            'globalFooter' => $globalFooter,
            'baseUrl'      => defined('BASE_URL') ? BASE_URL : '',
            'session'      => $this->session,
            'csrfToken'    => $this->getCsrfToken(),
        ], true);
    }

    /**
     * Inicia a criação de uma nova parte (Header, Footer ou Seção).
     */
    public function create(Request $request): ViewResponse
    {
        $type = (string)$request->input('type', ThemePartInterface::TYPE_HEADER);
        if (!in_array($type, [ThemePartInterface::TYPE_HEADER, ThemePartInterface::TYPE_FOOTER, ThemePartInterface::TYPE_SECTION], true)) {
            $type = ThemePartInterface::TYPE_HEADER;
        }

        $defaultPreset = match ($type) {
            ThemePartInterface::TYPE_HEADER  => '3_cols_header',
            ThemePartInterface::TYPE_FOOTER  => '4_cols_footer',
            ThemePartInterface::TYPE_SECTION => '1_col',
            default => '1_col',
        };

        $defaultSettings = [
            'is_full_width' => false,
            'max_width'     => '1280px',
            'gap'           => '24px',
            'padding'       => '20px 24px',
            'align_items'   => 'center',
            'background'    => '',
        ];

        // Colunas iniciais vazias de acordo com o preset padrão
        $initialColumns = match ($defaultPreset) {
            '3_cols_header' => [
                0 => [['type' => 'logo', 'data' => ['text' => 'Domain<span style="color:var(--color-primary);">System</span>', 'height' => '32px']]],
                1 => [['type' => 'nav_menu', 'data' => ['align' => 'center']]],
                2 => [['type' => 'cta_button', 'data' => ['label' => 'Acessar', 'url' => '/login', 'variant' => 'primary', 'align' => 'right']]],
            ],
            '4_cols_footer' => [
                0 => [['type' => 'logo', 'data' => ['text' => 'DomainSystem', 'height' => '28px']], ['type' => 'paragraph', 'data' => ['text' => 'Ecossistema modular de alto desempenho.', 'font_size' => '0.85rem']]],
                1 => [['type' => 'heading', 'data' => ['text' => 'Navegação', 'level' => 'h4', 'font_size' => '1rem']], ['type' => 'paragraph', 'data' => ['text' => '<a href="/" style="color:inherit;">Início</a><br><a href="/cursos" style="color:inherit;">Cursos</a>', 'font_size' => '0.85rem']]],
                2 => [['type' => 'heading', 'data' => ['text' => 'Comunidade', 'level' => 'h4', 'font_size' => '1rem']], ['type' => 'social_icons', 'data' => []]],
                3 => [['type' => 'heading', 'data' => ['text' => 'Legal', 'level' => 'h4', 'font_size' => '1rem']], ['type' => 'copyright', 'data' => ['site_name' => 'Domain-System']]],
            ],
            default => [
                0 => [['type' => 'heading', 'data' => ['text' => 'Título da Seção', 'level' => 'h2']], ['type' => 'paragraph', 'data' => ['text' => 'Insira seu texto e blocos aqui.']]],
            ]
        };

        $newPart = new ThemePart(
            uniqid('part_', true),
            $type,
            'Novo ' . ucfirst($type),
            $defaultPreset,
            $defaultSettings,
            $initialColumns,
            MobileToggleStyleInterface::STYLE_CLASSIC,
            false
        );

        return $this->renderEditorView($newPart, true);
    }

    /**
     * Abre o construtor visual para editar uma parte existente.
     */
    public function edit(Request $request): ViewResponse|Response
    {
        $id = (string)$request->input('id', '');
        if ($id === '') {
            return Response::redirect((defined('BASE_URL') ? BASE_URL : '') . '/admin/builder');
        }

        $part = $this->partRepo->findById($id);
        if ($part === null) {
            if ($this->session) {
                $this->session->setFlash('error', 'Parte visual não encontrada.');
            }
            return Response::redirect((defined('BASE_URL') ? BASE_URL : '') . '/admin/builder');
        }

        return $this->renderEditorView($part, false);
    }

    /**
     * API AJAX / POST: Salva ou atualiza uma ThemePart.
     */
    public function savePart(Request $request): Response
    {
        try {
            $input = $this->extractInput($request);

            $id = trim((string)($input['id'] ?? ''));
            if ($id === '') {
                $id = uniqid('part_', true);
            }

            $type = (string)($input['type'] ?? ThemePartInterface::TYPE_HEADER);
            $title = trim((string)($input['title'] ?? 'Sem Título'));
            $preset = (string)($input['layout_preset'] ?? '1_col');
            $settings = (array)($input['container_settings'] ?? []);
            $columnsData = (array)($input['columns_data'] ?? []);
            $mobileToggleStyle = (string)($input['mobile_toggle_style'] ?? MobileToggleStyleInterface::STYLE_CLASSIC);
            $isDefault = !empty($input['is_default']);
            $sections = (array)($input['sections'] ?? []);

            $part = new ThemePart(
                $id,
                $type,
                $title !== '' ? $title : 'Sem Título',
                $preset,
                $settings,
                $columnsData,
                $mobileToggleStyle,
                $isDefault,
                isset($input['created_at']) ? (int)$input['created_at'] : null,
                time(),
                $sections
            );

            $this->partRepo->save($part);

            // Atualiza Padrão Global se solicitado
            if ($isDefault) {
                $this->partAssoc->setGlobalDefault($type, $id);
            }

            // Atualiza Atribuição a Tema Específico se informado
            if (!empty($input['assigned_theme'])) {
                $this->partAssoc->assignPart((string)$input['assigned_theme'], $type, $id);
            }

            return new Response(json_encode([
                'success' => true,
                'id'      => $part->getId(),
                'title'   => $part->getTitle(),
                'message' => 'Parte "' . $part->getTitle() . '" salva com sucesso no Flex Builder!',
            ], JSON_UNESCAPED_UNICODE), 200, ['Content-Type' => 'application/json']);

        } catch (Throwable $e) {
            return new Response(json_encode([
                'success' => false,
                'message' => 'Erro ao salvar parte: ' . $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE), 400, ['Content-Type' => 'application/json']);
        }
    }

    /**
     * API AJAX / POST: Exclui uma parte pelo ID.
     */
    public function deletePart(Request $request): Response
    {
        $input = $this->extractInput($request);
        $id = trim((string)($input['id'] ?? ''));

        if ($id === '') {
            return new Response(json_encode(['success' => false, 'message' => 'ID inválido.'], JSON_UNESCAPED_UNICODE), 400, ['Content-Type' => 'application/json']);
        }

        $deleted = $this->partRepo->delete($id);

        if ($this->isAjax($request)) {
            return new Response(json_encode([
                'success' => $deleted,
                'message' => $deleted ? 'Parte excluída com sucesso.' : 'Falha ao excluir parte.',
            ], JSON_UNESCAPED_UNICODE), 200, ['Content-Type' => 'application/json']);
        }

        if ($this->session) {
            $this->session->setFlash($deleted ? 'success' : 'error', $deleted ? 'Parte excluída com sucesso.' : 'Falha ao excluir parte.');
        }

        return Response::redirect((defined('BASE_URL') ? BASE_URL : '') . '/admin/builder');
    }

    /**
     * API AJAX / POST: Define atribuição de uma parte a um tema ou ao padrão global.
     */
    public function assign(Request $request): Response
    {
        $input = $this->extractInput($request);
        $themeFolder = trim((string)($input['theme_folder'] ?? ''));
        $partType    = trim((string)($input['part_type'] ?? ''));
        $partId      = trim((string)($input['part_id'] ?? ''));
        $isGlobal    = !empty($input['is_global']);

        $resolvedPartId = ($partId !== '' && $partId !== 'none' && $partId !== 'fallback') ? $partId : null;

        if ($isGlobal) {
            $this->partAssoc->setGlobalDefault($partType, $resolvedPartId);
        } else {
            $this->partAssoc->assignPart($themeFolder, $partType, $resolvedPartId);
        }

        if ($this->isAjax($request)) {
            return new Response(json_encode([
                'success' => true,
                'message' => 'Atribuição atualizada com sucesso!',
            ], JSON_UNESCAPED_UNICODE), 200, ['Content-Type' => 'application/json']);
        }

        if ($this->session) {
            $this->session->setFlash('success', 'Atribuição atualizada com sucesso!');
        }

        return Response::redirect((defined('BASE_URL') ? BASE_URL : '') . '/admin/builder');
    }

    /**
     * Interface de Edição de Design Tokens (Paleta de Cores e Tipografia).
     */
    public function tokens(Request $request): ViewResponse
    {
        return $this->renderView('admin/tokens_editor', [
            'palette'    => $this->tokens->getColorPalette(),
            'typography' => $this->tokens->getTypography(),
            'cssVars'    => $this->tokens->generateCssVariables(),
            'baseUrl'    => defined('BASE_URL') ? BASE_URL : '',
            'session'    => $this->session,
            'csrfToken'  => $this->getCsrfToken(),
        ], true);
    }

    /**
     * Salva as alterações nos Design Tokens globais.
     */
    public function saveTokens(Request $request): Response
    {
        $input = $this->extractInput($request);

        $colors = (array)($input['palette'] ?? ($input['colors'] ?? []));
        $typography = (array)($input['typography'] ?? []);

        $success = $this->tokens->updateTokens([
            'palette'    => $colors,
            'typography' => $typography,
        ]);

        if ($this->isAjax($request)) {
            return new Response(json_encode([
                'success' => $success,
                'message' => $success ? 'Design Tokens atualizados com sucesso!' : 'Erro ao atualizar tokens.',
                'css'     => $this->tokens->generateCssVariables(),
            ], JSON_UNESCAPED_UNICODE), 200, ['Content-Type' => 'application/json']);
        }

        if ($this->session) {
            $this->session->setFlash($success ? 'success' : 'error', $success ? 'Design Tokens atualizados com sucesso!' : 'Erro ao atualizar tokens.');
        }

        return Response::redirect((defined('BASE_URL') ? BASE_URL : '') . '/admin/builder/tokens');
    }

    /**
     * API AJAX: Renderiza pré-visualização ao vivo da parte visual.
     */
    public function preview(Request $request): Response
    {
        try {
            $input = $this->extractInput($request);

            $preset = (string)($input['layout_preset'] ?? '1_col');
            $settings = (array)($input['container_settings'] ?? []);
            $columnsData = (array)($input['columns_data'] ?? []);
            $mobileToggleStyle = (string)($input['mobile_toggle_style'] ?? MobileToggleStyleInterface::STYLE_CLASSIC);
            $sections = (array)($input['sections'] ?? []);

            if (!empty($sections)) {
                $sectionsHtml = [];
                foreach ($sections as $sec) {
                    $secPreset = (string)($sec['layout_preset'] ?? '1_col');
                    $secSettings = (array)($sec['container_settings'] ?? []);
                    $secColsData = (array)($sec['columns_data'] ?? []);

                    $colsHtml = [];
                    foreach ($secColsData as $colIndex => $blocksList) {
                        $colBlocks = [];
                        if (is_array($blocksList)) {
                            foreach ($blocksList as $bDef) {
                                if (!is_array($bDef)) continue;
                                $bType = (string)($bDef['type'] ?? '');
                                $bData = (array)($bDef['data'] ?? []);

                                if ($bType === 'nav_menu' && !isset($bData['toggle_style'])) {
                                    $bData['toggle_style'] = $mobileToggleStyle;
                                }

                                $block = $this->blockRegistry->get($bType);
                                if ($block !== null) {
                                    $colBlocks[] = $block->render($bData, ['preview' => true]);
                                }
                            }
                        }
                        $colsHtml[(int)$colIndex] = implode("\n", $colBlocks);
                    }
                    $sectionsHtml[] = $this->layoutGrid->renderSection($secPreset, $colsHtml, $secSettings);
                }
                $renderedGrid = implode("\n", $sectionsHtml);
            } else {
                $columnsHtml = [];
                foreach ($columnsData as $colIndex => $blocksList) {
                    $colBlocks = [];
                    if (is_array($blocksList)) {
                        foreach ($blocksList as $bDef) {
                            if (!is_array($bDef)) continue;
                            $bType = (string)($bDef['type'] ?? '');
                            $bData = (array)($bDef['data'] ?? []);

                            if ($bType === 'nav_menu' && !isset($bData['toggle_style'])) {
                                $bData['toggle_style'] = $mobileToggleStyle;
                            }

                            $block = $this->blockRegistry->get($bType);
                            if ($block !== null) {
                                $colBlocks[] = $block->render($bData, ['preview' => true]);
                            }
                        }
                    }
                    $columnsHtml[(int)$colIndex] = implode("\n", $colBlocks);
                }
                $renderedGrid = $this->layoutGrid->renderSection($preset, $columnsHtml, $settings);
            }
            $cssVars = '<style id="ds-design-tokens">' . $this->tokens->generateCssVariables() . '</style>';

            return new Response($cssVars . $renderedGrid, 200, ['Content-Type' => 'text/html; charset=utf-8']);
        } catch (Throwable $e) {
            return new Response('<div style="padding:20px; color:#f87171; background:rgba(239,68,68,0.1); border-radius:6px;">Erro no preview: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>', 200, ['Content-Type' => 'text/html; charset=utf-8']);
        }
    }

    /**
     * API AJAX: Upload seguro de imagens para logos e blocos de imagem.
     */
    public function uploadImage(Request $request): Response
    {
        $file = $request->file('image') ?? ($request->file('file') ?? null);
        if (!$file) {
            return new Response(json_encode([
                'success' => false,
                'message' => 'Nenhum arquivo enviado ou erro no upload.',
            ], JSON_UNESCAPED_UNICODE), 400, ['Content-Type' => 'application/json']);
        }

        $result = $this->mediaUpload->upload($file);
        $status = $result['success'] ? 200 : 400;

        return new Response(json_encode($result, JSON_UNESCAPED_UNICODE), $status, ['Content-Type' => 'application/json']);
    }

    /**
     * API AJAX: Schema completo dos 12 blocos, presets, shortcodes do sistema e menus.
     */
    public function schema(Request $request): Response
    {
        $blocksData = [];
        foreach ($this->blockRegistry->all() as $block) {
            $blocksData[$block->getType()] = [
                'type'     => $block->getType(),
                'name'     => $block->getName(),
                'icon'     => $block->getIcon(),
                'category' => $block->getCategory(),
                'schema'   => $block->getSchema(),
            ];
        }

        $shortcodes = [];
        foreach ($this->shortcodeManager->getRegisteredShortcodes() as $tag => $info) {
            $shortcodes[] = [
                'tag'         => '[' . $tag . ']',
                'description' => $info['description'] ?? '',
                'category'    => $info['category'] ?? 'Geral',
            ];
        }

        $menus = [];
        if ($this->menuProvider !== null && method_exists($this->menuProvider, 'getMenus')) {
            $menus = $this->menuProvider->getMenus();
        }

        return new Response(json_encode([
            'blocks'        => $blocksData,
            'presets'       => $this->layoutGrid->getPresets(),
            'shortcodes'    => $shortcodes,
            'menus'         => $menus,
            'toggle_styles' => [
                MobileToggleStyleInterface::STYLE_CLASSIC => 'Clássico ☰ (3 Barras)',
                MobileToggleStyleInterface::STYLE_PILL    => 'Pílula MENU ☰ (Texto + Ícone)',
                MobileToggleStyleInterface::STYLE_MINIMAL => 'Minimalista ✕ (2 Barras Modernas)',
                MobileToggleStyleInterface::STYLE_DOTS    => 'Pontos ⋮ (3 Pontinhos Verticais)',
            ],
            'tokens'        => [
                'palette'    => $this->tokens->getColorPalette(),
                'typography' => $this->tokens->getTypography(),
            ],
            'locale'        => $this->translator->getLocale(),
            'i18n'          => $this->translator->getCatalog(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 200, ['Content-Type' => 'application/json']);
    }

    /**
     * Helper para carregar a tela do Studio do Construtor Visual.
     */
    private function renderEditorView(ThemePartInterface $part, bool $isNew): ViewResponse
    {
        $presets = $this->layoutGrid->getPresets();
        $blocks = $this->blockRegistry->all();
        $shortcodes = $this->shortcodeManager->getRegisteredShortcodes();
        $menus = ($this->menuProvider && method_exists($this->menuProvider, 'getMenus')) ? $this->menuProvider->getMenus() : [];
        $tokens = [
            'palette'    => $this->tokens->getColorPalette(),
            'typography' => $this->tokens->getTypography(),
        ];
        $themes = $this->getAvailableThemes();
        $activeTheme = $this->getActiveThemeFolder();

        return $this->renderView('admin/builder_editor', [
            'part'        => $part,
            'isNew'       => $isNew,
            'presets'     => $presets,
            'blocks'      => $blocks,
            'shortcodes'  => $shortcodes,
            'menus'       => $menus,
            'tokens'      => $tokens,
            'themes'      => $themes,
            'activeTheme' => $activeTheme,
            'baseUrl'     => defined('BASE_URL') ? BASE_URL : '',
            'session'     => $this->session,
            'csrfToken'   => $this->getCsrfToken(),
            'locale'      => $this->translator->getLocale(),
            'i18n'        => $this->translator->getCatalog(),
        ], false); // wrapLayout false para o Studio Full Screen
    }

    private function getCsrfToken(): string
    {
        return $this->session !== null ? $this->session->getCsrfToken() : '';
    }

    private function renderView(string $viewName, array $data = [], bool $wrapLayout = true): ViewResponse
    {
        $viewFile = dirname(__DIR__) . '/views/' . ltrim($viewName, '/\\') . '.php';
        if (!is_file($viewFile)) {
            throw new \RuntimeException("View não encontrada: {$viewFile}");
        }

        ob_start();
        extract($data, EXTR_SKIP);
        include $viewFile;
        $html = ob_get_clean();

        return new ViewResponse($html, $wrapLayout);
    }

    /**
     * Extrai dados enviados via JSON payload ou form POST.
     */
    private function extractInput(Request $request): array
    {
        $raw = file_get_contents('php://input');
        if (!empty($raw)) {
            $json = json_decode($raw, true);
            if (is_array($json)) {
                return $json;
            }
        }

        return $request->all();
    }

    private function isAjax(Request $request): bool
    {
        $accept = (string)($request->server['HTTP_ACCEPT'] ?? '');
        $xrw = (string)($request->server['HTTP_X_REQUESTED_WITH'] ?? '');
        $ct = (string)($request->server['CONTENT_TYPE'] ?? '');

        return str_contains($accept, 'application/json')
            || strtolower($xrw) === 'xmlhttprequest'
            || str_contains($ct, 'application/json');
    }

    /**
     * Descobre os temas instalados no sistema via ThemeDiscoveryService.
     * @return array<int, array<string, string>>
     */
    public function getAvailableThemes(): array
    {
        return $this->themeDiscovery->getAvailableThemes();
    }

    public function getActiveThemeFolder(): string
    {
        return $this->themeDiscovery->getActiveThemeFolder();
    }
}
