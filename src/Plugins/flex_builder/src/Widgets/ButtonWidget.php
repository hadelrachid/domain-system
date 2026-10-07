<?php
namespace DomainSystem\Plugins\flex_builder\src\Widgets;

use DomainSystem\Plugins\flex_builder\src\Widgets\AbstractVisualWidget;

/**
 * Componente Botão (Filho da Classe Abstrata)
 * 
 * Demonstra o poder da herança: este arquivo não precisa saber o que é width, height, 
 * background-color ou opacity. Ele só se preocupa com a Lógica Exclusiva de um Botão!
 */
class ButtonWidget extends AbstractVisualWidget
{
    public function getComponentId(): string
    {
        return 'basic_button';
    }

    public function getName(): string
    {
        return 'Botão de Ação';
    }

    /**
     * Define APENAS as propriedades exclusivas de um botão.
     * O núcleo (Kernel) vai mesclar isso com largura, cor de fundo e opacidade automaticamente.
     */
    protected function getCustomSchema(): array
    {
        return [
            'text' => ['type' => 'string', 'default' => 'Clique Aqui', 'label' => 'Texto do Botão'],
            'text_color' => ['type' => 'color', 'default' => '#ffffff', 'label' => 'Cor da Letra'],
            'font_size' => ['type' => 'string', 'default' => '16px', 'label' => 'Tamanho da Fonte'],
            'action_url' => ['type' => 'url', 'default' => '#', 'label' => 'URL de Destino'],
            'new_tab' => ['type' => 'boolean', 'default' => false, 'label' => 'Abrir em nova guia?']
        ];
    }

    /**
     * Renderiza EXCLUSIVAMENTE o conteúdo interno do Botão.
     * A classe Pai se encarregará de criar a div ao redor e injetar as cores de fundo.
     */
    protected function renderInnerContent(array $properties): string
    {
        // Valores default por segurança (fallback)
        $text = $properties['text'] ?? 'Clique Aqui';
        $color = $properties['text_color'] ?? '#ffffff';
        $fontSize = $properties['font_size'] ?? '16px';
        $url = $properties['action_url'] ?? '#';
        $target = (!empty($properties['new_tab']) && $properties['new_tab'] === true) ? 'target="_blank"' : '';

        // Renderiza uma tag 'a' perfeitamente estilizada para atuar como botão interno
        return "<a href='{$url}' {$target} style='
            display: flex; 
            justify-content: center; 
            align-items: center; 
            width: 100%; 
            height: 100%; 
            text-decoration: none; 
            color: {$color}; 
            font-size: {$fontSize}; 
            font-weight: bold;
            box-sizing: border-box;
        '>{$text}</a>";
    }
}
