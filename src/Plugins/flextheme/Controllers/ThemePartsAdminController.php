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
use DomainSystem\Core\Theme\Parts\Contracts\ElementStyleRendererInterface;
use DomainSystem\Core\Theme\Parts\Contracts\LayoutGridInterface;
use DomainSystem\Core\Theme\Parts\Contracts\MenuProviderInterface;
use DomainSystem\Core\Theme\Parts\Contracts\MobileToggleStyleInterface;
use DomainSystem\Core\Theme\Parts\Contracts\PublicUrlResolverInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartAssociationInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartRendererInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartRepositoryInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartValidatorInterface;
use DomainSystem\Core\Theme\Parts\Styles\ElementStyleRenderer;
use DomainSystem\Core\Theme\Parts\Support\BaseUrlResolver;
use DomainSystem\Core\Theme\Parts\Validation\ThemePartValidator;
use DomainSystem\Core\Theme\ShortcodeManager;
use DomainSystem\Plugins\flextheme\Contracts\MediaUploadServiceInterface;
use DomainSystem\Plugins\flextheme\Contracts\ThemeDiscoveryServiceInterface;
use DomainSystem\Plugins\flextheme\Contracts\ThemePartPreviewServiceInterface;
use DomainSystem\Plugins\flextheme\Services\MediaUploadService;
use DomainSystem\Plugins\flextheme\Services\ThemeDiscoveryService;
use DomainSystem\Plugins\flextheme\Services\ThemePartManagementService;
use DomainSystem\Plugins\flextheme\Services\ThemePartPreviewService;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Controller Administrativo do Flex Builder (Motor em Blocos & Design Tokens).
 *
 * Responsável apenas pela coordenação HTTP, delegando descoberta de temas,
 * persistência/atribuição e compilação de preview para serviços dedicados (SOLID - SRP / DIP).
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
    private ElementStyleRendererInterface $styleRenderer;
    private ThemePartPreviewServiceInterface $previewService;
    private ThemePartManagementService $managementService;
    private PublicUrlResolverInterface $urlResolver;

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
        ?TranslatorInterface $translator = null,
        ?ElementStyleRendererInterface $styleRenderer = null,
        ?ThemePartPreviewServiceInterface $previewService = null,
        ?ThemePartManagementService $managementService = null,
        ?PublicUrlResolverInterface $urlResolver = null
    ) {
        $this->partRepo          = $partRepo;
        $this->partAssoc         = $partAssoc;
        $this->tokens            = $tokens;
        $this->blockRegistry     = $blockRegistry;
        $this->layoutGrid        = $layoutGrid;
        $this->renderer          = $renderer;
        $this->themeManager      = $themeManager;
        $this->shortcodeManager  = $shortcodeManager;
        $this->menuProvider      = $menuProvider;
        $this->session           = $session;
        $this->basePath          = $basePath ?? (defined('DOMAIN_SYSTEM_ROOT') ? DOMAIN_SYSTEM_ROOT : dirname(__DIR__, 4));
        $this->themeDiscovery    = $themeDiscovery ?? new ThemeDiscoveryService($this->basePath);
        $this->mediaUpload       = $mediaUpload ?? new MediaUploadService($this->basePath);
        $this->translator        = $translator ?? new Translator($this->basePath . '/resources/lang');
        $this->styleRenderer     = $styleRenderer ?? new ElementStyleRenderer();
        $this->previewService    = $previewService ?? new ThemePartPreviewService($this->renderer);
        $this->managementService = $managementService ?? new ThemePartManagementService(
            $this->partRepo,
            $this->partAssoc,
            $this->themeDiscovery,
            new ThemePartValidator($this->blockRegistry, $this->layoutGrid),
            $this->blockRegistry,
            $this->layoutGrid
        );
        $this->urlResolver       = $urlResolver ?? new BaseUrlResolver();
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
        $allAssignments = $this->partAssoc->getAllAssignments();
        $assignments = $allAssignments['themes'] ?? $allAssignments;
        $globalHeader = $this->partAssoc->getGlobalDefault(ThemePartInterface::TYPE_HEADER);
        $globalFooter = $this->partAssoc->getGlobalDefault(ThemePartInterface::TYPE_FOOTER);

        return $this->renderView('admin/parts_list', [
            'headers'        => $headers,
            'footers'        => $footers,
            'sections'       => $sections,
            'themes'         => $themes,
            'activeTheme'    => $activeTheme,
            'assignments'    => $assignments,
            'allAssignments' => $allAssignments,
            'globalHeader'   => $globalHeader,
            'globalFooter'   => $globalFooter,
            'templates'      => $this->getThemeTemplates($activeTheme),
            'baseUrl'        => $this->urlResolver->getBaseUrl(),
            'session'        => $this->session,
            'csrfToken'      => $this->getCsrfToken(),
        ], true);
    }

    /**
     * Inicia a criação de uma nova parte (Header, Footer ou Seção).
     */
    public function create(Request $request): ViewResponse
    {
        $type = (string)$request->input('type', ThemePartInterface::TYPE_HEADER);
        $newPart = $this->managementService->createDefaultPart($type);

        return $this->renderEditorView($newPart, true);
    }

    /**
     * Abre o construtor visual para editar uma parte existente.
     */
    public function edit(Request $request): ViewResponse|Response
    {
        $id = (string)$request->input('id', '');
        if ($id === '') {
            return Response::redirect($this->urlResolver->resolve('/admin/builder'));
        }

        $part = $this->partRepo->findById($id);
        if ($part === null) {
            if ($this->session) {
                $this->session->setFlash('error', 'Parte visual não encontrada.');
            }
            return Response::redirect($this->urlResolver->resolve('/admin/builder'));
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
            $part = $this->managementService->saveFromPayload($input);

            return new Response(json_encode([
                'success' => true,
                'id'      => $part->getId(),
                'title'   => $part->getTitle(),
                'message' => 'Parte "' . $part->getTitle() . '" salva com sucesso no Flex Builder!',
            ], JSON_UNESCAPED_UNICODE), 200, ['Content-Type' => 'application/json']);
        } catch (InvalidArgumentException $e) {
            return new Response(json_encode([
                'success' => false,
                'message' => 'Erro de validação: ' . $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE), 422, ['Content-Type' => 'application/json']);
        } catch (RuntimeException $e) {
            return new Response(json_encode([
                'success' => false,
                'message' => 'Erro ao salvar parte: ' . $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE), 400, ['Content-Type' => 'application/json']);
        } catch (Throwable $e) {
            if (!class_exists(\PHPUnit\Framework\TestCase::class, false)) {
                error_log('[FlexBuilder][savePart] ' . $e->getMessage());
            }
            return new Response(json_encode([
                'success' => false,
                'message' => 'Erro interno ao processar o salvamento da parte visual.',
            ], JSON_UNESCAPED_UNICODE), 500, ['Content-Type' => 'application/json']);
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

        $deleted = $this->managementService->deletePart($id);

        if ($this->isAjax($request)) {
            return new Response(json_encode([
                'success' => $deleted,
                'message' => $deleted ? 'Parte excluída com sucesso.' : 'Falha ao excluir parte.',
            ], JSON_UNESCAPED_UNICODE), 200, ['Content-Type' => 'application/json']);
        }

        if ($this->session) {
            $this->session->setFlash($deleted ? 'success' : 'error', $deleted ? 'Parte excluída com sucesso.' : 'Falha ao excluir parte.');
        }

        return Response::redirect($this->urlResolver->resolve('/admin/builder'));
    }

    /**
     * API AJAX / POST: Define atribuição de uma parte a um tema, padrão global ou template.
     */
    public function assign(Request $request): Response
    {
        $input = $this->extractInput($request);
        $assigned = $this->managementService->assignFromPayload($input);

        if ($this->isAjax($request)) {
            return new Response(json_encode([
                'success' => $assigned,
                'message' => $assigned ? 'Atribuição atualizada com sucesso!' : 'Falha ao atualizar atribuição.',
            ], JSON_UNESCAPED_UNICODE), $assigned ? 200 : 400, ['Content-Type' => 'application/json']);
        }

        if ($this->session) {
            $this->session->setFlash($assigned ? 'success' : 'error', $assigned ? 'Atribuição atualizada com sucesso!' : 'Falha ao atualizar atribuição.');
        }

        return Response::redirect($this->urlResolver->resolve('/admin/builder'));
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
            'baseUrl'    => $this->urlResolver->getBaseUrl(),
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

        return Response::redirect($this->urlResolver->resolve('/admin/builder/tokens'));
    }

    /**
     * API AJAX: Renderiza pré-visualização ao vivo da parte visual via ThemePartPreviewService.
     */
    public function preview(Request $request): Response
    {
        try {
            $input = $this->extractInput($request);
            $html = $this->previewService->renderPreview($input);

            return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
        } catch (InvalidArgumentException $e) {
            return new Response(
                '<div style="padding:20px; color:#f87171; background:rgba(239,68,68,0.1); border-radius:6px;">Dados de pré-visualização inválidos.</div>',
                422,
                ['Content-Type' => 'text/html; charset=utf-8']
            );
        } catch (Throwable $e) {
            if (!class_exists(\PHPUnit\Framework\TestCase::class, false)) {
                error_log('[FlexBuilder][preview] ' . $e->getMessage());
            }
            return new Response(
                '<div style="padding:20px; color:#f87171; background:rgba(239,68,68,0.1); border-radius:6px;">Não foi possível gerar a pré-visualização.</div>',
                500,
                ['Content-Type' => 'text/html; charset=utf-8']
            );
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
     * API AJAX: Schema completo dos blocos, presets, shortcodes do sistema e menus.
     */
    public function schema(Request $request): Response
    {
        $partType = (string)($request->input('part_type') ?? '');
        $blocks = $partType !== '' ? $this->blockRegistry->allForPartType($partType) : $this->blockRegistry->all();

        $blocksData = [];
        foreach ($blocks as $block) {
            $blocksData[$block->getType()] = [
                'type'               => $block->getType(),
                'name'               => $block->getName(),
                'icon'               => $block->getIcon(),
                'category'           => $block->getCategory(),
                'schema'             => $block->getSchema(),
                'allowed_part_types' => $block->getAllowedPartTypes(),
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
            'part'             => $part,
            'isNew'            => $isNew,
            'presets'          => $presets,
            'blocks'           => $blocks,
            'shortcodes'       => $shortcodes,
            'menus'            => $menus,
            'tokens'           => $tokens,
            'themes'           => $themes,
            'activeTheme'      => $activeTheme,
            'templates'        => $this->getThemeTemplates($activeTheme),
            'assignedTemplate' => ($part->getType() === ThemePartInterface::TYPE_SECTION) ? $this->getAssignedTemplateForSection($activeTheme, $part->getId()) : '',
            'baseUrl'          => $this->urlResolver->getBaseUrl(),
            'session'          => $this->session,
            'csrfToken'        => $this->getCsrfToken(),
            'locale'           => $this->translator->getLocale(),
            'i18n'             => $this->translator->getCatalog(),
        ], false);
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

        $html = $isolatedInclude($viewFile, $data);

        return new ViewResponse($html, $wrapLayout);
    }

    /**
     * Extrai dados enviados via objeto Request (JSON ou form POST) sem depender de leitura direta de php://input quando Request já foi populado.
     */
    private function extractInput(Request $request): array
    {
        if (method_exists($request, 'json')) {
            $jsonBody = $request->json();
            if (!empty($jsonBody)) {
                return $jsonBody;
            }
        }

        $all = $request->all();
        if (!empty($all)) {
            return $all;
        }

        $raw = file_get_contents('php://input');
        if (!empty($raw)) {
            $json = json_decode($raw, true);
            if (is_array($json)) {
                return $json;
            }
        }

        return [];
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

    public function getAssignedTemplateForSection(string $themeFolder, string $sectionId): string
    {
        return $this->partAssoc->getAssignedTemplateForSection($themeFolder, $sectionId) ?? '';
    }

    /**
     * @return array<string, string>
     */
    public function getThemeTemplates(string $themeFolder): array
    {
        return $this->themeDiscovery->getThemeTemplates($themeFolder);
    }
}
