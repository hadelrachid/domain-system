<?php

namespace DomainSystem\Plugins\flextheme\BuilderFlex\Core;

use DomainSystem\Core\Contracts\PageTreeRendererInterface;
use DomainSystem\Plugins\flextheme\BuilderFlex\Contracts\WidgetManagerInterface;

class JsonRenderer implements PageTreeRendererInterface
{
    private WidgetManagerInterface $widgetManager;
    private DtoRenderer $dtoRenderer;

    public function __construct(WidgetManagerInterface $widgetManager, ?DtoRenderer $dtoRenderer = null)
    {
        $this->widgetManager = $widgetManager;
        $this->dtoRenderer = $dtoRenderer ?? new DtoRenderer();
    }

    public function renderTree(array $tree): string
    {
        $dtos = array_values(array_filter(array_map(fn($node) => is_array($node) ? $this->buildDto($node) : [], $tree)));
        return $this->dtoRenderer->renderTree($dtos);
    }

    public function buildDto(array $node): array
    {
        if (empty($node['type']) || empty($node['id'])) {
            return [];
        }

        $widget = $this->widgetManager->getWidget($node['type']);
        if (!$widget) {
            return [
                'type' => 'div',
                'tag' => 'div',
                'content' => '<!-- Widget não encontrado: ' . htmlspecialchars((string)$node['type'], ENT_QUOTES, 'UTF-8') . ' -->',
                'attrs' => [],
                'styles' => [],
                'children' => [],
            ];
        }

        $props = $node['props'] ?? [];
        if (empty($props['class_css'])) {
            $props['class_css'] = 'bw-' . $node['id'];
        }

        $children = [];
        foreach ($node['children'] ?? [] as $child) {
            if (is_array($child)) {
                $childDto = $this->buildDto($child);
                if (!empty($childDto)) {
                    $children[] = $childDto;
                }
            }
        }

        return $widget->toDto($props, $children);
    }
}
