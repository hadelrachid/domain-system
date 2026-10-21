<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Blocks;

use DomainSystem\Core\Theme\Parts\Contracts\BlockInterface;

/**
 * Bloco de Redes Sociais e Contatos.
 */
class SocialIconsBlock implements BlockInterface
{
    public function getType(): string
    {
        return 'social_icons';
    }

    public function getName(): string
    {
        return 'Ícones de Redes Sociais';
    }

    public function getIcon(): string
    {
        return '🌐';
    }

    public function getCategory(): string
    {
        return 'contact';
    }

    public function render(array $data = [], array $context = []): string
    {
        $networks = (array)($data['networks'] ?? [
            'github'   => 'https://github.com',
            'linkedin' => 'https://linkedin.com',
        ]);
        $align = (string)($data['align'] ?? 'center');
        $color = (string)($data['color'] ?? 'var(--primary, #45f3ff)');

        $icons = [
            'github'    => '🐙 GitHub',
            'linkedin'  => '💼 LinkedIn',
            'youtube'   => '▶ YouTube',
            'instagram' => '📷 Instagram',
            'whatsapp'  => '💬 WhatsApp',
            'x'         => '𝕏 Twitter',
        ];

        $linksHtml = [];
        foreach ($networks as $net => $url) {
            $trimmedUrl = trim((string)$url);
            if ($trimmedUrl === '') {
                continue;
            }
            $safeUrl = htmlspecialchars($trimmedUrl, ENT_QUOTES, 'UTF-8');
            $label = $icons[strtolower((string)$net)] ?? ucfirst((string)$net);

            $linksHtml[] = "<a href=\"{$safeUrl}\" target=\"_blank\" rel=\"noopener noreferrer\" class=\"ds-social-link\" style=\"color: {$color}; text-decoration: none; font-size: 0.9rem; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; border-radius: 4px; background: rgba(255,255,255,0.05); transition: opacity 0.2s;\">{$label}</a>";
        }

        if (empty($linksHtml)) {
            return '';
        }

        $items = implode(' ', $linksHtml);
        $justify = match ($align) {
            'left'   => 'flex-start',
            'right'  => 'flex-end',
            default  => 'center',
        };

        return <<<HTML
<div class="ds-block-social" style="display: flex; gap: 8px; justify-content: {$justify}; align-items: center; flex-wrap: wrap;">
    {$items}
</div>
HTML;
    }

    public function getSchema(): array
    {
        return [
            'type' => 'social_icons',
            'fields' => [
                'networks' => ['type' => 'key_value', 'label' => 'Redes Sociais (Rede => URL)', 'default' => ['github' => 'https://github.com']],
                'align'    => ['type' => 'select', 'label' => 'Alinhamento', 'options' => ['left', 'center', 'right'], 'default' => 'center'],
                'color'    => ['type' => 'color', 'label' => 'Cor dos Ícones', 'default' => 'var(--primary)'],
            ],
        ];
    }
}
