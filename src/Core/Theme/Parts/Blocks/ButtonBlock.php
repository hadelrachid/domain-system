<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Blocks;

use DomainSystem\Core\Theme\Parts\Blocks\Traits\AllowedPartTypesTrait;
use DomainSystem\Core\Theme\Parts\Contracts\BlockInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ElementStyleRendererInterface;
use DomainSystem\Core\Theme\Parts\Contracts\HtmlSanitizerInterface;
use DomainSystem\Core\Theme\Parts\Contracts\Styles\StylableElementInterface;
use DomainSystem\Core\Theme\Parts\Styles\ElementStyleRenderer;
use DomainSystem\Core\Theme\Parts\Styles\Traits\StylableElementTrait;
use DomainSystem\Core\Theme\Parts\Styles\Values\ColorValue;
use DomainSystem\Core\Theme\Parts\Styles\Values\DimensionValue;
use DomainSystem\Core\Theme\Parts\Support\SafeHtmlSanitizer;

/**
 * Bloco de Botão Genérico Multi-Ação e Alta Fidelidade Visual (SOLID - SRP / OCP).
 * 
 * Suporta 8 tipos de ações operacionais:
 * - link: Navegação HTTP/HTTPS padrão com target configurável
 * - scroll_to: Rolagem suave para ID de âncora na página (#secao)
 * - whatsapp: Disparo direto para API do WhatsApp com mensagem opcional
 * - tel: Chamada telefônica nativa (tel:)
 * - email: Abertura de cliente de email (mailto:)
 * - modal: Abertura de modal/popup por ID (data-ds-modal)
 * - javascript: Execução de rotina ou evento JS
 * - submit: Submissão de formulário pai mais próximo
 * 
 * Micro-interações e Animações de Hover:
 * - grow, lift, glow, shimmer, bounce, none
 */
class ButtonBlock implements BlockInterface, StylableElementInterface
{
    use StylableElementTrait;
    use AllowedPartTypesTrait;

    public const ACTION_LINK = 'link';
    public const ACTION_SCROLL = 'scroll_to';
    public const ACTION_WHATSAPP = 'whatsapp';
    public const ACTION_TEL = 'tel';
    public const ACTION_EMAIL = 'email';
    public const ACTION_MODAL = 'modal';
    public const ACTION_JAVASCRIPT = 'javascript';
    public const ACTION_SUBMIT = 'submit';

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
        return 'button';
    }

    public function getName(): string
    {
        return 'Botão Genérico';
    }

    public function getIcon(): string
    {
        return '🔘';
    }

    public function getCategory(): string
    {
        return 'action';
    }

    public function render(array $data = [], array $context = []): string
    {
        $this->styleData = $data;
        $label = htmlspecialchars((string)($data['label'] ?? 'Clique Aqui'), ENT_QUOTES, 'UTF-8');
        $subtext = trim((string)($data['subtext'] ?? ''));
        $actionType = (string)($data['action_type'] ?? self::ACTION_LINK);
        $rawUrl = (string)($data['url'] ?? '#');
        $target = (string)($data['target'] ?? '_self');
        $align = (string)($data['align'] ?? 'left');
        $hoverAnim = (string)($data['hover_animation'] ?? 'lift');
        $icon = trim((string)($data['icon'] ?? ''));
        $iconPos = (string)($data['icon_position'] ?? 'left');

        // Resolução de cores via Value Object ColorValue
        $bgColor = $this->styleRenderer->sanitizeCssValue(
            ColorValue::fromString((string)($data['bg_color'] ?? 'var(--color-primary, #38bdf8)'))->getCssValue()
        );
        $textColor = $this->styleRenderer->sanitizeCssValue(
            ColorValue::fromString((string)($data['text_color'] ?? '#0f172a'))->getCssValue()
        );
        $borderRadius = $this->styleRenderer->sanitizeCssValue((string)($data['border_radius'] ?? '8px'));
        $padding = $this->styleRenderer->sanitizeCssValue((string)($data['padding'] ?? '12px 24px'));
        $fontFamily = $this->styleRenderer->sanitizeCssValue(
            !empty($data['font_family']) ? (string)$data['font_family'] : 'var(--font-primary, inherit)'
        );
        $fontWeight = $this->styleRenderer->sanitizeCssValue((string)($data['font_weight'] ?? '700'));
        $fontSize = $this->styleRenderer->sanitizeCssValue((string)($data['font_size'] ?? '1rem'));
        $boxShadow = $this->styleRenderer->sanitizeCssValue((string)($data['box_shadow'] ?? '0 4px 12px rgba(0,0,0,0.15)'));
        $width = $this->styleRenderer->sanitizeCssValue((string)($data['width'] ?? 'auto'));

        // Classe de animação de micro-interação
        $animClass = match ($hoverAnim) {
            'grow'    => 'ds-btn-grow',
            'lift'    => 'ds-btn-lift',
            'glow'    => 'ds-btn-glow',
            'shimmer' => 'ds-btn-shimmer',
            'bounce'  => 'ds-btn-bounce',
            default   => 'ds-btn-static',
        };

        // Alinhamento do container flex wrapper
        $justifyBtn = match ($align) {
            'center'  => 'center',
            'right'   => 'flex-end',
            'justify' => 'stretch',
            default   => 'flex-start',
        };

        $wrapperWidth = ($align === 'justify' || $width === '100%') ? 'width: 100%;' : '';
        $btnWidth = ($align === 'justify' || $width === '100%') ? 'width: 100%;' : 'width: auto;';

        $btnStyle = "display: inline-flex; flex-direction: column; align-items: center; justify-content: center; text-decoration: none; cursor: pointer; transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); border: none; outline: none; background: {$bgColor}; color: {$textColor}; border-radius: {$borderRadius}; padding: {$padding}; font-family: {$fontFamily}; font-weight: {$fontWeight}; font-size: {$fontSize}; box-shadow: {$boxShadow}; {$btnWidth}";

        // Montagem do conteúdo interno do botão (Ícone + Label + Subtexto)
        $iconHtml = '';
        if ($icon !== '') {
            $iconHtml = "<span class=\"ds-btn-icon\" style=\"font-size: 1.1em; line-height: 1;\">" . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . "</span>";
        }

        $mainTextRow = "<span style=\"display: inline-flex; align-items: center; gap: 8px;\">";
        if ($icon !== '' && $iconPos === 'left') {
            $mainTextRow .= $iconHtml;
        }
        $mainTextRow .= "<span class=\"ds-btn-label\">{$label}</span>";
        if ($icon !== '' && $iconPos === 'right') {
            $mainTextRow .= $iconHtml;
        }
        $mainTextRow .= "</span>";

        $subtextHtml = '';
        if ($subtext !== '') {
            $subtextEscaped = htmlspecialchars($subtext, ENT_QUOTES, 'UTF-8');
            $subtextHtml = "<span class=\"ds-btn-subtext\" style=\"font-size: 0.75em; opacity: 0.85; font-weight: 500; margin-top: 2px;\">{$subtextEscaped}</span>";
        }

        $innerContent = $mainTextRow . $subtextHtml;

        // Resolução do Markup de acordo com action_type
        return match ($actionType) {
            self::ACTION_SUBMIT => <<<HTML
<div class="ds-block-button-wrapper" style="display: flex; justify-content: {$justifyBtn}; {$wrapperWidth}">
    <button type="submit" class="ds-block-btn {$animClass}" style="{$btnStyle}">
        {$innerContent}
    </button>
</div>
HTML,

            self::ACTION_JAVASCRIPT => (function() use ($justifyBtn, $wrapperWidth, $animClass, $btnStyle, $rawUrl, $innerContent) {
                $jsCode = htmlspecialchars($rawUrl, ENT_QUOTES, 'UTF-8');
                return <<<HTML
<div class="ds-block-button-wrapper" style="display: flex; justify-content: {$justifyBtn}; {$wrapperWidth}">
    <button type="button" onclick="{$jsCode}" class="ds-block-btn {$animClass}" style="{$btnStyle}">
        {$innerContent}
    </button>
</div>
HTML;
            })(),

            self::ACTION_MODAL => (function() use ($justifyBtn, $wrapperWidth, $animClass, $btnStyle, $rawUrl, $innerContent) {
                $modalId = htmlspecialchars(ltrim($rawUrl, '#'), ENT_QUOTES, 'UTF-8');
                return <<<HTML
<div class="ds-block-button-wrapper" style="display: flex; justify-content: {$justifyBtn}; {$wrapperWidth}">
    <button type="button" data-ds-modal="{$modalId}" class="ds-block-btn {$animClass}" style="{$btnStyle}">
        {$innerContent}
    </button>
</div>
HTML;
            })(),

            self::ACTION_SCROLL => (function() use ($justifyBtn, $wrapperWidth, $animClass, $btnStyle, $rawUrl, $innerContent) {
                $anchor = '#' . ltrim(htmlspecialchars($rawUrl, ENT_QUOTES, 'UTF-8'), '#');
                return <<<HTML
<div class="ds-block-button-wrapper" style="display: flex; justify-content: {$justifyBtn}; {$wrapperWidth}">
    <a href="{$anchor}" class="ds-block-btn {$animClass}" style="{$btnStyle}">
        {$innerContent}
    </a>
</div>
HTML;
            })(),

            self::ACTION_WHATSAPP => (function() use ($justifyBtn, $wrapperWidth, $animClass, $btnStyle, $rawUrl, $data, $innerContent) {
                $cleanedPhone = preg_replace('/[^0-9]/', '', $rawUrl);
                $msg = !empty($data['whatsapp_message']) ? urlencode((string)$data['whatsapp_message']) : '';
                $waUrl = "https://wa.me/{$cleanedPhone}" . ($msg !== '' ? "?text={$msg}" : '');
                return <<<HTML
<div class="ds-block-button-wrapper" style="display: flex; justify-content: {$justifyBtn}; {$wrapperWidth}">
    <a href="{$waUrl}" target="_blank" rel="noopener noreferrer" class="ds-block-btn {$animClass}" style="{$btnStyle}">
        {$innerContent}
    </a>
</div>
HTML;
            })(),

            self::ACTION_TEL => (function() use ($justifyBtn, $wrapperWidth, $animClass, $btnStyle, $rawUrl, $innerContent) {
                $cleanedTel = preg_replace('/[^0-9+]/', '', $rawUrl);
                return <<<HTML
<div class="ds-block-button-wrapper" style="display: flex; justify-content: {$justifyBtn}; {$wrapperWidth}">
    <a href="tel:{$cleanedTel}" class="ds-block-btn {$animClass}" style="{$btnStyle}">
        {$innerContent}
    </a>
</div>
HTML;
            })(),

            self::ACTION_EMAIL => (function() use ($justifyBtn, $wrapperWidth, $animClass, $btnStyle, $rawUrl, $innerContent) {
                $emailEscaped = htmlspecialchars($rawUrl, ENT_QUOTES, 'UTF-8');
                return <<<HTML
<div class="ds-block-button-wrapper" style="display: flex; justify-content: {$justifyBtn}; {$wrapperWidth}">
    <a href="mailto:{$emailEscaped}" class="ds-block-btn {$animClass}" style="{$btnStyle}">
        {$innerContent}
    </a>
</div>
HTML;
            })(),

            default => (function() use ($justifyBtn, $wrapperWidth, $animClass, $btnStyle, $rawUrl, $target, $innerContent) {
                $safeLink = $this->htmlSanitizer->sanitizeUrl($rawUrl, '#');
                $urlEscaped = htmlspecialchars($safeLink, ENT_QUOTES, 'UTF-8');
                $targetEscaped = htmlspecialchars($target, ENT_QUOTES, 'UTF-8');
                $relAttr = ($target === '_blank') ? ' rel="noopener noreferrer"' : '';
                return <<<HTML
<div class="ds-block-button-wrapper" style="display: flex; justify-content: {$justifyBtn}; {$wrapperWidth}">
    <a href="{$urlEscaped}" target="{$targetEscaped}"{$relAttr} class="ds-block-btn {$animClass}" style="{$btnStyle}">
        {$innerContent}
    </a>
</div>
HTML;
            })(),
        };
    }

    public function getSchema(): array
    {
        return [
            'type' => 'button',
            'fields' => [
                'label'            => ['type' => 'text', 'label' => 'Texto do Botão', 'default' => 'Clique Aqui'],
                'subtext'          => ['type' => 'text', 'label' => 'Subtexto (Opcional)', 'default' => ''],
                'action_type'      => ['type' => 'select', 'label' => 'Tipo de Ação', 'options' => [
                    self::ACTION_LINK       => 'Link URL Externo/Interno',
                    self::ACTION_SCROLL     => 'Rolar até Âncora (#secao)',
                    self::ACTION_WHATSAPP   => 'Conversa no WhatsApp',
                    self::ACTION_TEL        => 'Ligação Telefônica (tel:)',
                    self::ACTION_EMAIL      => 'Enviar Email (mailto:)',
                    self::ACTION_MODAL      => 'Abrir Janela Modal',
                    self::ACTION_JAVASCRIPT => 'Executar JavaScript',
                    self::ACTION_SUBMIT     => 'Submeter Formulário',
                ], 'default' => self::ACTION_LINK],
                'url'              => ['type' => 'text', 'label' => 'Destino da Ação (URL, Tel, WhatsApp, ID ou JS)', 'default' => '#'],
                'whatsapp_message' => ['type' => 'text', 'label' => 'Mensagem Pré-definida (WhatsApp)', 'default' => ''],
                'target'           => ['type' => 'select', 'label' => 'Abrir em', 'options' => ['_self' => 'Mesma Janela (_self)', '_blank' => 'Nova Aba (_blank)'], 'default' => '_self'],
                'hover_animation'  => ['type' => 'select', 'label' => 'Animação no Hover', 'options' => [
                    'lift'    => 'Elevar com Sombra (Lift)',
                    'grow'    => 'Aumentar Escala (Grow)',
                    'glow'    => 'Brilho Fluorescente (Glow)',
                    'shimmer' => 'Reflexo Metálico (Shimmer)',
                    'bounce'  => 'Salto Suave (Bounce)',
                    'none'    => 'Nenhuma Animação',
                ], 'default' => 'lift'],
                'bg_color'         => ['type' => 'color', 'label' => 'Cor de Fundo', 'default' => 'var(--color-primary, #38bdf8)'],
                'text_color'       => ['type' => 'color', 'label' => 'Cor do Texto', 'default' => '#0f172a'],
                'font_family'      => ['type' => 'font', 'label' => 'Família da Fonte', 'default' => 'var(--font-primary)'],
                'font_size'        => ['type' => 'dimension', 'label' => 'Tamanho da Fonte', 'default' => '1rem'],
                'font_weight'      => ['type' => 'select', 'label' => 'Peso da Fonte', 'options' => ['400', '500', '600', '700', '800'], 'default' => '700'],
                'border_radius'    => ['type' => 'dimension', 'label' => 'Arredondamento de Borda', 'default' => '8px'],
                'padding'          => ['type' => 'dimension', 'label' => 'Padding Interno', 'default' => '12px 24px'],
                'align'            => ['type' => 'select', 'label' => 'Alinhamento', 'options' => ['left', 'center', 'right', 'justify'], 'default' => 'left'],
                'icon'             => ['type' => 'text', 'label' => 'Ícone (Emoji ou Símbolo)', 'default' => ''],
                'icon_position'    => ['type' => 'select', 'label' => 'Posição do Ícone', 'options' => ['left' => 'Esquerda', 'right' => 'Direita'], 'default' => 'left'],
            ],
        ];
    }
}
