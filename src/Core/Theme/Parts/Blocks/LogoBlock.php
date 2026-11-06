<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Blocks;

use DomainSystem\Core\Theme\Parts\Blocks\Traits\AllowedPartTypesTrait;
use DomainSystem\Core\Theme\Parts\Contracts\BlockInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ElementStyleRendererInterface;
use DomainSystem\Core\Theme\Parts\Contracts\HtmlSanitizerInterface;
use DomainSystem\Core\Theme\Parts\Contracts\PublicUrlResolverInterface;
use DomainSystem\Core\Theme\Parts\Contracts\Styles\StylableElementInterface;
use DomainSystem\Core\Theme\Parts\Styles\ElementStyleRenderer;
use DomainSystem\Core\Theme\Parts\Styles\Traits\StylableElementTrait;
use DomainSystem\Core\Theme\Parts\Support\BaseUrlResolver;
use DomainSystem\Core\Theme\Parts\Support\SafeHtmlSanitizer;

/**
 * Bloco de Logotipo (Imagem ou Tipografia da Marca com link).
 */
class LogoBlock implements BlockInterface, StylableElementInterface
{
    use AllowedPartTypesTrait;
    use StylableElementTrait;

    private HtmlSanitizerInterface $htmlSanitizer;
    private PublicUrlResolverInterface $urlResolver;
    private ElementStyleRendererInterface $styleRenderer;

    public function __construct(
        ?HtmlSanitizerInterface $htmlSanitizer = null,
        ?PublicUrlResolverInterface $urlResolver = null,
        ?ElementStyleRendererInterface $styleRenderer = null
    ) {
        $this->htmlSanitizer = $htmlSanitizer ?? new SafeHtmlSanitizer();
        $this->urlResolver = $urlResolver ?? new BaseUrlResolver();
        $this->styleRenderer = $styleRenderer ?? new ElementStyleRenderer();
    }

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
        $this->styleData = $data;
        $baseUrl = $this->urlResolver->getBaseUrl();
        $rawLink = (string)($data['link'] ?? $data['url'] ?? $baseUrl . '/');
        $link = $this->htmlSanitizer->sanitizeUrl($rawLink, $baseUrl . '/');
        $height = $this->styleRenderer->sanitizeCssValue((string)($data['height'] ?? '40px'));
        $rawImageUrl = (string)($data['image_url'] ?? $baseUrl . '/assets/img/logo.png');
        $imageUrl = $rawImageUrl !== '' ? $this->htmlSanitizer->sanitizeUrl($rawImageUrl, '') : '';
        $brandText = (string)($data['brand_text'] ?? $data['text'] ?? 'Domain<span class="text-primary">System</span>');
        $showImage = (bool)($data['show_image'] ?? true);
        $showText = (bool)($data['show_text'] ?? true);
        $align = (string)($data['align'] ?? $data['text_align'] ?? 'left');
        $justifyLogo = match ($align) {
            'center' => 'center',
            'right'  => 'flex-end',
            default  => 'flex-start',
        };

        $textColor = $this->styleRenderer->sanitizeCssValue((string)($data['text_color'] ?? $data['color'] ?? 'var(--ds-local-heading-color, var(--ds-local-text-color, var(--color-text-main, #ffffff)))'));
        $fontSize = $this->styleRenderer->sanitizeCssValue((string)($data['font_size'] ?? $data['size'] ?? '1.4rem'));
        $fontWeight = $this->styleRenderer->sanitizeCssValue((string)($data['font_weight'] ?? '800'));
        $fontFamily = $this->styleRenderer->sanitizeCssValue((string)($data['font_family'] ?? ''));

        $resolvedFont = match ($fontFamily) {
            'secondary' => 'var(--font-secondary, monospace)',
            'tertiary'  => 'var(--font-tertiary, sans-serif)',
            'primary'   => 'var(--font-primary, sans-serif)',
            default     => $fontFamily !== '' ? $fontFamily : 'var(--font-primary, inherit)',
        };

        $safeLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');

        $imgHtml = '';
        if ($showImage && $imageUrl !== '') {
            $safeImg = htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8');
            $imgHtml = "<img src=\"{$safeImg}\" class=\"ds-logo-img\" alt=\"Logo\" style=\"height: {$height}; width: auto; max-width: 100%; object-fit: contain;\" onerror=\"this.style.display='none'\">";
        }

        $textHtml = '';
        if ($showText && $brandText !== '') {
            $sanitized = $this->htmlSanitizer->sanitizeInlineHtml($brandText);
            $textHtml = "<div class=\"ds-logo-text\" style=\"font-size: {$fontSize}; font-weight: {$fontWeight}; font-family: {$resolvedFont}; letter-spacing: -0.5px; color: {$textColor};\">{$sanitized}</div>";
        }

        return <<<HTML
<div class="ds-block-logo-wrapper" style="display: flex; justify-content: {$justifyLogo}; width: 100%;">
    <a href="{$safeLink}" class="ds-block-logo" style="display: inline-flex; align-items: center; gap: 10px; text-decoration: none;">
        {$imgHtml}
        {$textHtml}
    </a>
</div>
HTML;
    }

    public function getSchema(): array
    {
        return [
            'type' => 'logo',
            'fields' => [
                'image_url'   => ['type' => 'media', 'label' => 'URL da Imagem do Logo', 'default' => 'assets/img/logo.png'],
                'brand_text'  => ['type' => 'text', 'label' => 'Texto da Marca', 'default' => 'Domain<span class="text-primary">System</span>'],
                'text_color'  => ['type' => 'color', 'label' => 'Cor do Texto da Marca', 'default' => 'var(--color-text-main)'],
                'font_family' => ['type' => 'font', 'label' => 'Família da Fonte', 'default' => 'var(--font-primary)'],
                'font_size'   => ['type' => 'dimension', 'label' => 'Tamanho do Texto', 'units' => ['rem', 'px'], 'default' => '1.4rem'],
                'font_weight' => ['type' => 'select', 'label' => 'Peso da Fonte', 'options' => ['400', '500', '600', '700', '800', '900'], 'default' => '800'],
                'height'      => ['type' => 'dimension', 'label' => 'Altura do Logo', 'units' => ['px', 'rem'], 'default' => '40px'],
                'show_image'  => ['type' => 'boolean', 'label' => 'Exibir Imagem', 'default' => true],
                'show_text'   => ['type' => 'boolean', 'label' => 'Exibir Texto da Marca', 'default' => true],
                'link'        => ['type' => 'text', 'label' => 'Link de Destino', 'default' => '/'],
            ],
        ];
    }
}
