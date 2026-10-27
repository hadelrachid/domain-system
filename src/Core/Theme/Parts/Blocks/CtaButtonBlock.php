<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Blocks;

use DomainSystem\Core\Theme\Parts\Blocks\Traits\AllowedPartTypesTrait;
use DomainSystem\Core\Theme\Parts\Contracts\BlockInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ElementStyleRendererInterface;
use DomainSystem\Core\Theme\Parts\Contracts\HtmlSanitizerInterface;
use DomainSystem\Core\Theme\Parts\Styles\ElementStyleRenderer;
use DomainSystem\Core\Theme\Parts\Support\SafeHtmlSanitizer;

/**
 * Bloco de Botão de Ação / CTA (Call to Action).
 */
class CtaButtonBlock implements BlockInterface
{
    use AllowedPartTypesTrait;

    private ElementStyleRendererInterface $styleRenderer;
    private HtmlSanitizerInterface $htmlSanitizer;

    public function __construct(
        ?ElementStyleRendererInterface $styleRenderer = null,
        ?HtmlSanitizerInterface $htmlSanitizer = null
    ) {
        $this->styleRenderer = $styleRenderer ?? new ElementStyleRenderer();
        $this->htmlSanitizer = $htmlSanitizer ?? new SafeHtmlSanitizer();
    }

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
        $safeUrl = $this->htmlSanitizer->sanitizeUrl((string)($data['url'] ?? '#'), '#');
        $url = htmlspecialchars($safeUrl, ENT_QUOTES, 'UTF-8');
        $variant = (string)($data['variant'] ?? $data['style'] ?? 'primary');
        $size = (string)($data['size'] ?? 'medium');
        $align = (string)($data['align'] ?? 'left');
        $rawTarget = (string)($data['target'] ?? '_self');
        $target = in_array($rawTarget, ['_self', '_blank'], true) ? $rawTarget : '_self';
        $hoverAnim = (string)($data['hover_animation'] ?? '');
        $borderRadius = $this->styleRenderer->sanitizeCssValue((string)($data['border_radius'] ?? '8px'));
        $customPadding = $this->styleRenderer->sanitizeCssValue((string)($data['padding'] ?? ''));
        $customBg = $this->styleRenderer->sanitizeCssValue((string)($data['bg_color'] ?? ''));
        $customText = $this->styleRenderer->sanitizeCssValue((string)($data['text_color'] ?? ''));
        $fontFamily = $this->styleRenderer->sanitizeCssValue((string)($data['font_family'] ?? ''));

        $sizeCss = match ($size) {
            'small'  => 'padding: 8px 16px; font-size: 0.85rem;',
            'large'  => 'padding: 14px 28px; font-size: 1.15rem;',
            default  => 'padding: 10px 22px; font-size: 0.95rem;',
        };
        if ($customPadding !== '') {
            $sizeCss .= " padding: {$customPadding};";
        }
        if ($fontFamily !== '') {
            $sizeCss .= " font-family: {$fontFamily};";
        }

        $baseStyles = "display: inline-flex; align-items: center; justify-content: center; gap: 8px; font-weight: 700; border-radius: {$borderRadius}; text-decoration: none; transition: all 0.25s ease; cursor: pointer; {$sizeCss}";

        $variantStyles = match ($variant) {
            'secondary' => "background: var(--color-secondary, #1f2833); color: #fff; border: 1px solid rgba(255,255,255,0.15);",
            'outline'   => "background: transparent; color: var(--primary, #45f3ff); border: 2px solid var(--primary, #45f3ff);",
            'ghost'     => "background: transparent; color: var(--color-text-main, #fff); border: 1px solid transparent;",
            'gradient'  => "background: linear-gradient(135deg, var(--color-primary, #38bdf8), var(--color-accent, #818cf8)); color: #0b0c10; border: none;",
            'glow'      => "background: var(--primary, #45f3ff); color: #0b0c10; border: none; box-shadow: 0 0 20px var(--color-primary, rgba(69, 243, 255, 0.4));",
            default     => "background: var(--primary, #45f3ff); color: #0b0c10; border: 2px solid var(--primary, #45f3ff);",
        };

        if ($customBg !== '') {
            $variantStyles .= " background: {$customBg};";
        }
        if ($customText !== '') {
            $variantStyles .= " color: {$customText};";
        }

        $animClass = match ($hoverAnim) {
            'grow'    => ' ds-btn-grow',
            'lift'    => ' ds-btn-lift',
            'glow'    => ' ds-btn-glow',
            'shimmer' => ' ds-btn-shimmer',
            'bounce'  => ' ds-btn-bounce',
            default   => '',
        };

        $justifyBtn = match ($align) {
            'center' => 'center',
            'right'  => 'flex-end',
            default  => 'flex-start',
        };
        $safeAlign = in_array($align, ['left', 'center', 'right'], true) ? $align : 'left';
        $safeVariant = preg_replace('/[^a-z0-9_-]/i', '', $variant) ?: 'primary';
        $wrapperStyle = "display: flex; justify-content: {$justifyBtn}; width: 100%; text-align: {$safeAlign};";

        return <<<HTML
<div class="ds-block-cta-wrapper" style="{$wrapperStyle}">
    <a href="{$url}" target="{$target}" class="ds-block-cta ds-btn-{$safeVariant}{$animClass}" style="{$baseStyles} {$variantStyles}">
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
