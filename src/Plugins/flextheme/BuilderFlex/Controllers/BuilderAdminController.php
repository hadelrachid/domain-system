<?php

namespace DomainSystem\Plugins\flextheme\BuilderFlex\Controllers;

use DomainSystem\Core\Contracts\ContainerInterface;
use DomainSystem\Core\Contracts\ThemeManagerInterface;
use DomainSystem\Plugins\flextheme\BuilderFlex\Contracts\WidgetManagerInterface;
use DomainSystem\Plugins\flextheme\BuilderFlex\Core\AssetBundle;
use DomainSystem\Core\Http\Response;

/**
 * ════════════════════════════════════════════════════════════════════════════
 * CONTROLLER: BuilderAdminController (Editor Visual Full Site Editing)
 * ════════════════════════════════════════════════════════════════════════════
 *
 * Responsável por renderizar a interface do editor visual e expor a API 
 * de schema dos widgets (controles de propriedade) para o JavaScript.
 *
 * NOTA: Este controller recebe o WidgetManagerInterface via Injeção de 
 * Dependência no construtor, eliminando o anti-pattern Application::getInstance().
 */
class BuilderAdminController
{
    private ThemeManagerInterface $theme;
    private WidgetManagerInterface $widgetManager;

    public function __construct(ThemeManagerInterface $theme, WidgetManagerInterface $widgetManager)
    {
        $this->theme         = $theme;
        $this->widgetManager = $widgetManager;
    }

    /**
     * Renderiza a view do editor visual (Full Site Editing).
     */
    public function index(): \DomainSystem\Core\Http\Responses\ViewResponse
    {
        ob_start();
        include dirname(__DIR__) . '/views/builder_editor.php';
        return new \DomainSystem\Core\Http\Responses\ViewResponse(ob_get_clean(), false);
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
    public function getSchema()
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
}
