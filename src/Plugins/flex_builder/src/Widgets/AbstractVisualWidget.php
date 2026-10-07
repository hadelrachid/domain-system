<?php
namespace DomainSystem\Plugins\flex_builder\src\Widgets;

use DomainSystem\Plugins\flex_builder\src\Contracts\VisualComponentInterface;

/**
 * A Abstração Pai de todos os componentes do sistema.
 * Resolve lógica de margens, cores, dimensões (O Object Inspector do Delphi).
 */
abstract class AbstractVisualWidget implements VisualComponentInterface
{
    /**
     * Mescla as propriedades universais com as propriedades específicas do componente filho
     */
    public function getPropertiesSchema(): array
    {
        $universalSchema = [
            'width' => ['type' => 'string', 'default' => '100%', 'label' => 'Largura (px, %, vw)'],
            'height' => ['type' => 'string', 'default' => 'auto', 'label' => 'Altura'],
            'bg_color' => ['type' => 'color', 'default' => 'transparent', 'label' => 'Cor de Fundo (Hex, RGBA, Sólida)'],
            'opacity' => ['type' => 'range', 'min' => 0, 'max' => 1, 'step' => 0.1, 'default' => 1, 'label' => 'Opacidade (Transparência)'],
            'border_radius' => ['type' => 'string', 'default' => '0px', 'label' => 'Bordas Arredondadas'],
            'margin' => ['type' => 'string', 'default' => '0px', 'label' => 'Margem Externa'],
            'padding' => ['type' => 'string', 'default' => '0px', 'label' => 'Espaçamento Interno'],
            
            // Controle de Profundidade e Camadas (Traz para a Frente / Envia para Trás)
            'position' => ['type' => 'select', 'default' => 'relative', 'options' => ['relative', 'absolute', 'fixed'], 'label' => 'Tipo de Posição'],
            'z_index' => ['type' => 'number', 'default' => 1, 'label' => 'Camada (Z-Index)']
        ];

        return array_merge($universalSchema, $this->getCustomSchema());
    }

    /**
     * O Componente filho define apenas as suas propriedades exclusivas (ex: texto do botão, url da imagem)
     */
    abstract protected function getCustomSchema(): array;

    /**
     * O Componente filho renderiza apenas o HTML do miolo dele
     */
    abstract protected function renderInnerContent(array $properties): string;

    /**
     * O Componente Pai aplica o CSS dinâmico e renderiza o Container
     */
    public function render(array $properties = []): string
    {
        $style = $this->buildBaseStyle($properties);
        $innerHtml = $this->renderInnerContent($properties);
        
        return "<div class='ds-flex-widget' data-widget-id='{$this->getComponentId()}' style='{$style}'>{$innerHtml}</div>";
    }

    private function buildBaseStyle(array $properties): string
    {
        $css = "";
        
        // Posição e Camadas (Z-Index)
        $position = $properties['position'] ?? 'relative';
        $css .= "position: {$position}; ";
        if (isset($properties['z_index'])) $css .= "z-index: {$properties['z_index']}; ";

        // Dimensões e Visual
        if (!empty($properties['width'])) $css .= "width: {$properties['width']}; ";
        if (!empty($properties['height'])) $css .= "height: {$properties['height']}; ";
        if (!empty($properties['bg_color'])) $css .= "background-color: {$properties['bg_color']}; ";
        if (isset($properties['opacity'])) $css .= "opacity: {$properties['opacity']}; ";
        if (!empty($properties['border_radius'])) $css .= "border-radius: {$properties['border_radius']}; ";
        if (!empty($properties['margin'])) $css .= "margin: {$properties['margin']}; ";
        if (!empty($properties['padding'])) $css .= "padding: {$properties['padding']}; ";
        return $css;
    }
}
