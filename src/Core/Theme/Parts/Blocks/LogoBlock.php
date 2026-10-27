<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Blocks;

use DomainSystem\Core\Theme\Parts\Blocks\Traits\AllowedPartTypesTrait;
use DomainSystem\Core\Theme\Parts\Contracts\BlockInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ElementStyleRendererInterface;
use DomainSystem\Core\Theme\Parts\Contracts\HtmlSanitizerInterface;
use DomainSystem\Core\Theme\Parts\Contracts\PublicUrlResolverInterface;
use DomainSystem\Core\Theme\Parts\Styles\ElementStyleRenderer;
use DomainSystem\Core\Theme\Parts\Support\BaseUrlResolver;
use DomainSystem\Core\Theme\Parts\Support\SafeHtmlSanitizer;

/**
 * Bloco de Logotipo (Imagem ou Tipografia da Marca com link).
 */
class LogoBlock implements BlockInterface
{
    use AllowedPartTypesTrait;

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
        $baseUrl = $this->urlResolver->getBaseUrl();
        $rawLink = (string)($data['link'] ?? $data['url'] ?? $baseUrl . '/');
        $link = $this->htmlSanitizer->sanitizeUrl($rawLink, $baseUrl . '/');
        $height = $this->styleRenderer->sanitizeCssValue((string)($data['height'] ?? '40px'));
        $rawImageUrl = (string)($data['image_url'] ?? $baseUrl . '/assets/img/logo.png');
        $imageUrl = $rawImageUrl !== '' ? $this->htmlSanitizer->sanitizeUrl($rawImageUrl, '') : '';
        $brandText = (string)($data['brand_text'] ?? $data['text'] ?? 'Domain<span class="text-primary">System</span>');
        $showImage = (bool)($data['show_image'] ?? true);
        $showText = (bool)($data['show_text'] ?? true);
        $align = (string)($data['align'] ?? 'left');
        $justifyLogo = match ($align) {
            'center' => 'center',
            'right'  => 'flex-end',
            default  => 'flex-start',
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
            $textHtml = "<div class=\"ds-logo-text\" style=\"font-size: 1.4rem; font-weight: 800; letter-spacing: -0.5px; color: #fff;\">{$sanitized}</div>";
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
