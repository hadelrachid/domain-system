<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\flextheme\BuilderFlex\Controllers;

use DomainSystem\Core\Contracts\ThemeManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Http\Responses\ViewResponse;
use DomainSystem\Plugins\flextheme\BuilderFlex\Contracts\WidgetManagerInterface;
use DomainSystem\Plugins\flextheme\BuilderFlex\Core\AssetBundle;
use Throwable;

/**
 * ════════════════════════════════════════════════════════════════════════════
 * CONTROLLER: BuilderAdminController (Editor Visual Full Site Editing)
 * ════════════════════════════════════════════════════════════════════════════
 *
 * Responsável por renderizar a interface do editor visual e expor a API
 * de schema dos widgets (controles de propriedade) para o JavaScript.
 *
 * Desacoplado de classes concretas/interfaces de outros plugins (suporta
 * repositório de páginas opcional via duck-typing/contrato agnóstico).
 */
class BuilderAdminController
{
    private ThemeManagerInterface $theme;
    private WidgetManagerInterface $widgetManager;
    private ?object $pageRepo;

    public function __construct(
        ThemeManagerInterface $theme,
        WidgetManagerInterface $widgetManager,
        ?object $pageRepo = null
    ) {
        $this->theme         = $theme;
        $this->widgetManager = $widgetManager;
        $this->pageRepo      = $pageRepo;
    }

    /**
     * Renderiza a view do editor visual (Full Site Editing) com isolamento estrito de escopo.
     */
    public function index(Request $request): ViewResponse
    {
        $pageId = $request->input('page_id');
        $pageContent = '';

        if ($pageId && $this->pageRepo !== null && method_exists($this->pageRepo, 'findById')) {
            $page = $this->pageRepo->findById((int)$pageId);
            if (is_array($page) && !empty($page['content'])) {
                $pageContent = (string)$page['content'];
            }
        }

        $builderData = [
            'page_id' => $pageId,
            'content' => $pageContent,
        ];

        $viewFile = dirname(__DIR__) . '/views/builder_editor.php';
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

        return new ViewResponse($isolatedInclude($viewFile, $builderData), false);
    }

    /** Entrega o bundle JS (todos os módulos do manifesto, na ordem). */
    public function script(): Response
    {
        return $this->asset('js', 'application/javascript; charset=utf-8');
    }

    /** Entrega o bundle CSS. */
    public function style(): Response
    {
        return $this->asset('css', 'text/css; charset=utf-8');
    }

    private function asset(string $type, string $contentType): Response
    {
        $bundle = new AssetBundle(dirname(__DIR__) . '/assets');
        $content = $bundle->build($type);

        if ($content === null) {
            return new Response('Not found', 404, ['Content-Type' => 'text/plain']);
        }

        return new Response($content, 200, [
            'Content-Type'  => $contentType,
            'Cache-Control' => 'no-cache',
        ]);
    }

    /**
     * API JSON: Retorna o schema de controles de todos os widgets registrados.
     * Consumido pelo JavaScript do editor para montar o painel de propriedades.
     */
    public function getSchema(): Response
    {
        $schema = [];
        foreach ($this->widgetManager->getWidgets() as $tag => $widget) {
            $schema[$tag] = [
                'name'     => $widget->getName(),
                'controls' => $widget->getControls(),
            ];
        }

        return new Response(
            json_encode($schema, JSON_PRETTY_PRINT),
            200,
            ['Content-Type' => 'application/json']
        );
    }

    public function save(Request $request): Response
    {
        $pageId = $request->input('page_id');
        $content = $request->input('content');

        if (!$pageId || empty($content)) {
            return new Response(json_encode(['success' => false, 'error' => 'Missing page_id or content']), 400, ['Content-Type' => 'application/json']);
        }

        if ($this->pageRepo === null || !method_exists($this->pageRepo, 'update')) {
            return new Response(json_encode(['success' => false, 'error' => 'Page repository unavailable']), 503, ['Content-Type' => 'application/json']);
        }

        try {
            $this->pageRepo->update((int)$pageId, ['content' => $content]);
            return new Response(json_encode(['success' => true]), 200, ['Content-Type' => 'application/json']);
        } catch (Throwable $e) {
            return new Response(json_encode(['success' => false, 'error' => $e->getMessage()]), 500, ['Content-Type' => 'application/json']);
        }
    }
}
