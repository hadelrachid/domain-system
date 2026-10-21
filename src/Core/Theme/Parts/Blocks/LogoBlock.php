<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Blocks;

use DomainSystem\Core\Theme\Parts\Contracts\BlockInterface;

/**
 * Bloco de Logotipo (Imagem ou Tipografia da Marca com link).
 */
class LogoBlock implements BlockInterface
{
    public function getType(): string
    {
        return 'logo';
    }

    public function getName(): string
    {
        return 'Logotipo / Marca';
    }

    public function getIcon(): string
    {
        return '🛡️';
    }

    public function getCategory(): string
    {
        return 'identity';
    }

    public function render(array $data = [], array $context = []): string
    {
        $baseUrl = defined('BASE_URL') ? BASE_URL : '';
        $link = (string)($data['link'] ?? $baseUrl . '/');
        $height = (string)($data['height'] ?? '40px');
        $imageUrl = (string)($data['image_url'] ?? $baseUrl . '/assets/img/logo.png');
        $brandText = (string)($data['brand_text'] ?? 'Domain<span class="text-primary">System</span>');
        $showImage = (bool)($data['show_image'] ?? true);
        $showText = (bool)($data['show_text'] ?? true);

        $safeLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');

        $imgHtml = '';
        if ($showImage && $imageUrl !== '') {
            $safeImg = htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8');
            $imgHtml = "<img src=\"{$safeImg}\" class=\"ds-logo-img\" alt=\"Logo\" style=\"height: {$height}; width: auto; max-width: 100%; object-fit: contain;\" onerror=\"this.style.display='none'\">";
        }

        $textHtml = '';
        if ($showText && $brandText !== '') {
            $sanitized = strip_tags($brandText, '<span><strong><b><small>');
            $textHtml = "<div class=\"ds-logo-text\" style=\"font-size: 1.4rem; font-weight: 800; letter-spacing: -0.5px; color: #fff;\">{$sanitized}</div>";
        }

        return <<<HTML
<a href="{$safeLink}" class="ds-block-logo" style="display: flex; align-items: center; gap: 10px; text-decoration: none;">
    {$imgHtml}
    {$textHtml}
</a>
HTML;
    }

    public function getSchema(): array
    {
        return [
            'type' => 'logo',
            'fields' => [
                'image_url'  => ['type' => 'media', 'label' => 'URL da Imagem do Logo', 'default' => 'assets/img/logo.png'],
                'brand_text' => ['type' => 'text', 'label' => 'Texto da Marca', 'default' => 'Domain<span class="text-primary">System</span>'],
                'height'     => ['type' => 'dimension', 'label' => 'Altura do Logo', 'units' => ['px', 'rem'], 'default' => '40px'],
                'show_image' => ['type' => 'boolean', 'label' => 'Exibir Imagem', 'default' => true],
                'show_text'  => ['type' => 'boolean', 'label' => 'Exibir Texto da Marca', 'default' => true],
                'link'       => ['type' => 'text', 'label' => 'Link de Destino', 'default' => '/'],
            ],
        ];
    }
}
