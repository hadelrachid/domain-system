<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Blocks;

use DomainSystem\Core\Theme\Parts\Contracts\BlockInterface;

/**
 * Bloco de Botão de Ação / CTA (Call to Action).
 */
class CtaButtonBlock implements BlockInterface
{
    public function getType(): string
    {
        return 'cta_button';
    }

    public function getName(): string
    {
        return 'Botão de Ação (CTA)';
    }

    public function getIcon(): string
    {
        return '🚀';
    }

    public function getCategory(): string
    {
        return 'action';
    }

    public function render(array $data = [], array $context = []): string
    {
        $label = htmlspecialchars((string)($data['label'] ?? 'Clique Aqui'), ENT_QUOTES, 'UTF-8');
        $url = htmlspecialchars((string)($data['url'] ?? '#'), ENT_QUOTES, 'UTF-8');
        $variant = (string)($data['variant'] ?? 'primary');
        $size = (string)($data['size'] ?? 'medium');
        $align = (string)($data['align'] ?? 'left');
        $target = (string)($data['target'] ?? '_self');

        $padding = match ($size) {
            'small'  => '8px 16px; font-size: 0.85rem;',
            'large'  => '14px 28px; font-size: 1.15rem;',
            default  => '10px 22px; font-size: 0.95rem;',
        };

        $baseStyles = "display: inline-flex; align-items: center; justify-content: center; gap: 8px; font-weight: 700; border-radius: 8px; text-decoration: none; transition: all 0.25s ease; cursor: pointer; padding: {$padding};";

        $variantStyles = match ($variant) {
            'secondary' => "background: var(--color-secondary, #1f2833); color: #fff; border: 1px solid rgba(255,255,255,0.15);",
            'outline'   => "background: transparent; color: var(--primary, #45f3ff); border: 2px solid var(--primary, #45f3ff);",
            'glow'      => "background: var(--primary, #45f3ff); color: #0b0c10; border: none; box-shadow: 0 0 20px var(--color-primary, rgba(69, 243, 255, 0.4));",
            default     => "background: var(--primary, #45f3ff); color: #0b0c10; border: 2px solid var(--primary, #45f3ff);",
        };

        $justifyBtn = match ($align) {
            'center' => 'center',
            'right'  => 'flex-end',
            default  => 'flex-start',
        };
        $wrapperStyle = "display: flex; justify-content: {$justifyBtn}; width: 100%; text-align: {$align};";

        return <<<HTML
<div class="ds-block-cta-wrapper" style="{$wrapperStyle}">
    <a href="{$url}" target="{$target}" class="ds-block-cta ds-btn-{$variant}" style="{$baseStyles} {$variantStyles}">
        <span>{$label}</span>
    </a>
</div>
HTML;
    }

    public function getSchema(): array
    {
        return [
            'type' => 'cta_button',
            'fields' => [
                'label'   => ['type' => 'text', 'label' => 'Texto do Botão', 'default' => 'Começar Agora'],
                'url'     => ['type' => 'text', 'label' => 'Link de Destino', 'default' => '#'],
                'variant' => ['type' => 'select', 'label' => 'Estilo Visual', 'options' => ['primary', 'secondary', 'outline', 'glow'], 'default' => 'primary'],
                'size'    => ['type' => 'select', 'label' => 'Tamanho', 'options' => ['small', 'medium', 'large'], 'default' => 'medium'],
                'align'   => ['type' => 'select', 'label' => 'Alinhamento', 'options' => ['left', 'center', 'right'], 'default' => 'left'],
                'target'  => ['type' => 'select', 'label' => 'Abrir em', 'options' => ['_self', '_blank'], 'default' => '_self'],
            ],
        ];
    }
}
