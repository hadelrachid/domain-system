<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Blocks;

use DomainSystem\Core\Theme\Parts\Blocks\Traits\AllowedPartTypesTrait;
use DomainSystem\Core\Theme\Parts\Contracts\BlockInterface;

/**
 * Bloco de Código Customizado (HTML, CSS e JS inline seguro).
 */
class CustomCodeBlock implements BlockInterface
{
    use AllowedPartTypesTrait;
    public function getType(): string
    {
        return 'custom_code';
    }

    public function getName(): string
    {
        return 'Código Customizado (HTML/CSS)';
    }

    public function getIcon(): string
    {
        return '💻';
    }

    public function getCategory(): string
    {
        return 'advanced';
    }

    public function render(array $data = [], array $context = []): string
    {
        $html = (string)($data['html'] ?? '');
        $css = (string)($data['css'] ?? '');

        $output = '';
        if (trim($css) !== '') {
            $output .= "<style>{$css}</style>\n";
        }
        if (trim($html) !== '') {
            $output .= $html;
        }

        return $output;
    }

    public function getSchema(): array
    {
        return [
            'type' => 'custom_code',
            'fields' => [
                'html' => ['type' => 'code_html', 'label' => 'Código HTML', 'default' => '<div>Código customizado</div>'],
                'css'  => ['type' => 'code_css', 'label' => 'Estilos CSS Inline', 'default' => ''],
            ],
        ];
    }
}
