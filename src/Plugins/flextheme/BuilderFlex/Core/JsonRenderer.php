<?php

namespace DomainSystem\Plugins\flextheme\BuilderFlex\Core;

use DomainSystem\Plugins\flextheme\BuilderFlex\Contracts\WidgetManagerInterface;

class JsonRenderer
{
    private WidgetManagerInterface $widgetManager;

    public function __construct(WidgetManagerInterface $widgetManager)
    {
        $this->widgetManager = $widgetManager;
    }

    public function renderTree(array $tree): string
    {
        $this->css = '';
        $html = '';
        foreach ($tree as $node) {
            $this->extractCss($node);
            $html .= $this->renderNode($node);
        }
        
        if (!empty($this->css)) {
            $html = "<style>\n" . $this->css . "\n</style>\n" . $html;
        }
        return $html;
    }

    private string $css = '';

    private function extractCss(array $node)
    {
        if (empty($node['id'])) return;

        $id = $node['id'];
        $cssClass = '.bw-' . $id;

        // Base styles
        if (!empty($node['props'])) {
            $baseRules = $this->generateCssRules($node['props']);
            if ($baseRules) {
                $this->css .= "{$cssClass} { {$baseRules} }\n";
            }
        }

        // Tablet styles
        if (!empty($node['tablet'])) {
            $tabletRules = $this->generateCssRules($node['tablet']);
            if ($tabletRules) {
                $this->css .= "@media (max-width: 1024px) { {$cssClass} { {$tabletRules} } }\n";
            }
        }

        // Mobile styles
        if (!empty($node['mobile'])) {
            $mobileRules = $this->generateCssRules($node['mobile']);
            if ($mobileRules) {
                $this->css .= "@media (max-width: 480px) { {$cssClass} { {$mobileRules} } }\n";
            }
        }

        if (!empty($node['children'])) {
            foreach ($node['children'] as $child) {
                $this->extractCss($child);
            }
        }
    }

    private function generateCssRules(array $props): string
    {
        // Re-usa a lógica do AbstractWidget, mas injetando num array "props"
        $widget = new class extends AbstractWidget {
            public function getName(): string { return ''; }
            public function getShortcodeTag(): string { return ''; }
            public function render(array $a, string $c=''): string { return ''; }
            public function getBaseStyles(array $props) { return $this->generateBaseStyles($props); }
        };
        return $widget->getBaseStyles($props);
    }

    private function renderNode(array $node): string
    {
        if (empty($node['type']) || empty($node['id'])) {
            return '';
        }

        $widget = $this->widgetManager->getWidget($node['type']);
        if (!$widget) {
            return '<!-- Widget não encontrado: ' . htmlspecialchars($node['type']) . ' -->';
        }

        $props = $node['props'] ?? [];
        // Em vez de usar extractCommonAttributes (que geraria style inline),
        // passamos a classe e o ID limpos.
        $props['id'] = ''; // não queremos id="", queremos usar a classe bw-
        $props['class_css'] = 'bw-' . $node['id'];
        // Remove as props que geram inline style no AbstractWidget, para não duplicar!
        // Na verdade, AbstractWidget gera inline styles lendo props['width'] etc.
        // Se a gente apagar as props visuais, ele não gera inline styles.
        $cleanProps = [
            'class_css' => 'bw-' . $node['id'],
            'layout' => $props['layout'] ?? 'absolute'
        ];
        
        $innerContent = '';
        if (!empty($node['children'])) {
            foreach ($node['children'] as $child) {
                $innerContent .= $this->renderNode($child);
            }
        }

        return $widget->render($cleanProps, $innerContent);
    }
}
