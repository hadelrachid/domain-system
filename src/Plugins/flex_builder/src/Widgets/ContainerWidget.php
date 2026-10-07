<?php
namespace DomainSystem\Plugins\flex_builder\src\Widgets;

use DomainSystem\Plugins\flex_builder\src\Widgets\AbstractVisualWidget;
use DomainSystem\Plugins\flex_builder\src\Registry\ComponentRegistry;

/**
 * Componente TPanel (Container)
 * 
 * Ele herda cor, tamanho e z-index da classe mãe, mas o seu super-poder
 * é ter um laço de repetição que renderiza "Filhos" dentro de si mesmo!
 */
class ContainerWidget extends AbstractVisualWidget
{
    public function getComponentId(): string
    {
        return 'tpanel_container';
    }

    public function getName(): string
    {
        return 'Painel (TPanel)';
    }

    protected function getCustomSchema(): array
    {
        return [
            // Propriedades exclusivas de layout de grade do CSS Flexbox
            'flex_direction' => ['type' => 'select', 'default' => 'row', 'options' => ['row', 'column'], 'label' => 'Direção (Linha ou Coluna)'],
            'align_items' => ['type' => 'select', 'default' => 'flex-start', 'options' => ['flex-start', 'center', 'flex-end', 'stretch'], 'label' => 'Alinhamento Vertical'],
            'justify_content' => ['type' => 'select', 'default' => 'flex-start', 'options' => ['flex-start', 'center', 'flex-end', 'space-between'], 'label' => 'Justificação Horizontal'],
            'gap' => ['type' => 'string', 'default' => '10px', 'label' => 'Espaçamento entre os filhos (Gap)']
        ];
    }

    /**
     * O TPanel não tem texto ou imagem própria. 
     * O corpo dele é formado pela soma dos corpos dos seus filhos!
     */
    protected function renderInnerContent(array $properties): string
    {
        $html = '';

        // Se este painel tiver componentes filhos injetados no JSON...
        if (!empty($properties['children']) && is_array($properties['children'])) {
            $registry = ComponentRegistry::getInstance();
            
            // Fazemos um loop em cada filho, pedimos para o Registro instanciar a classe dele, e renderizamos.
            foreach ($properties['children'] as $childData) {
                $childWidget = $registry->getComponent($childData['widget']);
                if ($childWidget) {
                    $html .= $childWidget->render($childData['props'] ?? []);
                }
            }
        }

        // Aplicamos o CSS Flexbox exclusivo do Painel para alinhar os filhos dentro dele
        $dir = $properties['flex_direction'] ?? 'row';
        $align = $properties['align_items'] ?? 'flex-start';
        $justify = $properties['justify_content'] ?? 'flex-start';
        $gap = $properties['gap'] ?? '10px';

        return "<div class='ds-tpanel-inner' style='
            display: flex;
            flex-direction: {$dir};
            align-items: {$align};
            justify-content: {$justify};
            gap: {$gap};
            width: 100%;
            height: 100%;
        '>{$html}</div>";
    }
}
