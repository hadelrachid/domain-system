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
 * Bloco de Logotipo Responsivo (Imagem e Tipografia com suporte a Desktop e Mobile).
 * Segue a abstração pai AbstractBlock (SOLID - OCP / LSP / DIP / ISP).
 */
class LogoBlock extends AbstractBlock
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
        $this->styleData = $data;
        $baseUrl = $this->urlResolver->getBaseUrl();
        $rawLink = (string)($data['link'] ?? $data['url'] ?? $baseUrl . '/');
        $link = $this->htmlSanitizer->sanitizeUrl($rawLink, $baseUrl . '/');
        $safeLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');

        $logoId = $this->generateBlockId($data, 'ds_logo');

        // 1. Dimensões Desktop & Mobile
        $heightDesktop = $this->styleRenderer->sanitizeCssValue((string)($data['height'] ?? '40px'));
        $heightMobile = $this->styleRenderer->sanitizeCssValue((string)($data['height_mobile'] ?? $heightDesktop));

        // 2. Alinhamento e Flex Direction Desktop & Mobile
        $alignDesktop = (string)($data['align'] ?? $data['text_align'] ?? 'left');
        $justifyDesktop = match ($alignDesktop) {
            'center' => 'center',
            'right'  => 'flex-end',
            default  => 'flex-start',
        };

        $alignMobile = (string)($data['align_mobile'] ?? $alignDesktop);
        $justifyMobile = match ($alignMobile) {
            'center' => 'center',
            'right'  => 'flex-end',
            default  => 'flex-start',
        };

        $flexDirDesktop = (string)($data['flex_direction'] ?? 'row');
        $flexDirMobile = (string)($data['flex_direction_mobile'] ?? $flexDirDesktop);

        $alignItemsDesktop = ($flexDirDesktop === 'column')
            ? match ($alignDesktop) {
                'center' => 'center',
                'right'  => 'flex-end',
                default  => 'flex-start',
            }
            : 'center';

        $alignItemsMobile = ($flexDirMobile === 'column')
            ? match ($alignMobile) {
                'center' => 'center',
                'right'  => 'flex-end',
                default  => 'flex-start',
            }
            : 'center';

        // 3. Imagens Desktop & Mobile
        $rawImageUrl = (string)($data['image_url'] ?? $baseUrl . '/assets/img/logo.png');
        $imageUrl = $rawImageUrl !== '' ? $this->htmlSanitizer->sanitizeUrl($rawImageUrl, '') : '';
        $rawImageUrlMobile = (string)($data['image_url_mobile'] ?? '');
        $imageUrlMobile = $rawImageUrlMobile !== '' ? $this->htmlSanitizer->sanitizeUrl($rawImageUrlMobile, '') : $imageUrl;

        $showImage = (bool)($data['show_image'] ?? true);
        $showImageMobile = (bool)($data['show_image_mobile'] ?? $showImage);

        // 4. Tipografia & Textos Desktop & Mobile
        $brandText = (string)($data['brand_text'] ?? $data['text'] ?? 'Domain<span class="text-primary">System</span>');
        $brandTextMobile = (string)($data['text_mobile'] ?? $data['brand_text_mobile'] ?? '');

        $showText = (bool)($data['show_text'] ?? true);
        $showTextMobile = (bool)($data['show_text_mobile'] ?? $showText);

        $textColor = $this->styleRenderer->sanitizeCssValue((string)($data['text_color'] ?? $data['color'] ?? 'var(--ds-local-heading-color, var(--ds-local-text-color, var(--color-text-main, #ffffff)))'));
        $textColorMobile = $this->styleRenderer->sanitizeCssValue((string)($data['text_color_mobile'] ?? $data['color_mobile'] ?? $textColor));
        $fontSizeDesktop = $this->styleRenderer->sanitizeCssValue((string)($data['font_size'] ?? $data['size'] ?? '1.4rem'));
        $fontSizeMobile = $this->styleRenderer->sanitizeCssValue((string)($data['font_size_mobile'] ?? $fontSizeDesktop));
        $fontWeightDesktop = $this->styleRenderer->sanitizeCssValue((string)($data['font_weight'] ?? '800'));
        $fontWeightMobile = $this->styleRenderer->sanitizeCssValue((string)($data['font_weight_mobile'] ?? $fontWeightDesktop));
        $fontFamily = $this->styleRenderer->sanitizeCssValue((string)($data['font_family'] ?? ''));

        $resolvedFont = $this->resolveFontFamily($fontFamily);

        // Montagem das tags de Imagem
        $imgDesktopHtml = '';
        if ($showImage && $imageUrl !== '') {
            $safeImg = htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8');
            $imgDesktopHtml = "<img src=\"{$safeImg}\" class=\"ds-logo-img ds-logo-img-desktop\" alt=\"Logo\" onerror=\"this.style.display='none'\">";
        }
        $imgMobileHtml = '';
        if ($showImageMobile && $imageUrlMobile !== '') {
            $safeImgMobile = htmlspecialchars($imageUrlMobile, ENT_QUOTES, 'UTF-8');
            $imgMobileHtml = "<img src=\"{$safeImgMobile}\" class=\"ds-logo-img ds-logo-img-mobile\" alt=\"Logo\" onerror=\"this.style.display='none'\">";
        }

        // Montagem das tags de Texto
        $sanitizedDesktop = $this->htmlSanitizer->sanitizeInlineHtml($brandText);
        $sanitizedMobile = $brandTextMobile !== '' ? $this->htmlSanitizer->sanitizeInlineHtml($brandTextMobile) : $sanitizedDesktop;

        $hasDistinctMobileText = ($brandTextMobile !== '' && $brandTextMobile !== $brandText);
        $hasDistinctMobileImg = ($imageUrlMobile !== '' && $imageUrlMobile !== $imageUrl);

        $textHtml = '';
        if ($showText || $showTextMobile) {
            if ($hasDistinctMobileText) {
                $textHtml = "<div class=\"ds-logo-text\"><span class=\"ds-logo-text-desktop\">{$sanitizedDesktop}</span><span class=\"ds-logo-text-mobile\">{$sanitizedMobile}</span></div>";
            } else {
                $textHtml = "<div class=\"ds-logo-text\"><span>{$sanitizedDesktop}</span></div>";
            }
        }

        $imgHtml = $hasDistinctMobileImg ? ($imgDesktopHtml . $imgMobileHtml) : $imgDesktopHtml;

        $showImgMobileCss = !$showImageMobile ? 'display: none !important;' : 'display: block !important;';
        $showTxtMobileCss = !$showTextMobile ? 'display: none !important;' : 'display: block !important;';

        $distinctImgCssDesktop = $hasDistinctMobileImg ? "#{$logoId} .ds-logo-img-mobile { display: none !important; }" : "";
        $distinctImgCssMobile = $hasDistinctMobileImg ? "#{$logoId} .ds-logo-img-desktop { display: none !important; } #{$logoId} .ds-logo-img-mobile { display: block !important; }" : "";

        return <<<HTML
<style>
#{$logoId}.ds-block-logo-root {
    width: 100%;
    box-sizing: border-box;
}
#{$logoId} .ds-block-logo-wrapper {
    display: flex;
    width: 100%;
    justify-content: {$justifyDesktop};
    text-align: {$alignDesktop};
    box-sizing: border-box;
}
#{$logoId} .ds-block-logo {
    display: inline-flex;
    flex-direction: {$flexDirDesktop};
    align-items: {$alignItemsDesktop};
    justify-content: {$justifyDesktop};
    gap: 10px;
    text-decoration: none;
    line-height: 1.2;
}
#{$logoId} .ds-logo-img {
    height: {$heightDesktop};
    width: auto;
    max-width: 100%;
    object-fit: contain;
    flex-shrink: 0;
}
#{$logoId} .ds-logo-text {
    font-size: {$fontSizeDesktop};
    font-weight: {$fontWeightDesktop};
    font-family: {$resolvedFont};
    letter-spacing: -0.5px;
    color: {$textColor};
    line-height: 1.2;
    white-space: nowrap;
    display: inline-block;
}
#{$logoId} .ds-logo-text span {
    font-weight: inherit;
    font-size: inherit;
    font-family: inherit;
}
#{$logoId} .ds-logo-text .text-primary {
    color: var(--color-primary, #38bdf8);
}
#{$logoId} .ds-logo-text-mobile {
    display: none !important;
}
{$distinctImgCssDesktop}

/* ========================================================
   📱 REGRAS MOBILE (≤ 768px) & SIMULADOR LIVE PREVIEW
   ======================================================== */
@media (max-width: 768px) {
    #{$logoId} .ds-block-logo-wrapper {
        justify-content: {$justifyMobile} !important;
        text-align: {$alignMobile} !important;
    }
    #{$logoId} .ds-block-logo {
        flex-direction: {$flexDirMobile} !important;
        align-items: {$alignItemsMobile} !important;
        justify-content: {$justifyMobile} !important;
        gap: 6px !important;
    }
    #{$logoId} .ds-logo-img {
        height: {$heightMobile} !important;
        {$showImgMobileCss}
    }
    #{$logoId} .ds-logo-text {
        font-size: {$fontSizeMobile} !important;
        font-weight: {$fontWeightMobile} !important;
        color: {$textColorMobile} !important;
        {$showTxtMobileCss}
    }
    #{$logoId} .ds-logo-text-desktop {
        display: none !important;
    }
    #{$logoId} .ds-logo-text-mobile {
        display: inline-block !important;
    }
    {$distinctImgCssMobile}
}

.ds-mobile-mode #{$logoId} .ds-block-logo-wrapper,
.ds-device-375 #{$logoId} .ds-block-logo-wrapper,
.ds-device-768 #{$logoId} .ds-block-logo-wrapper {
    justify-content: {$justifyMobile} !important;
    text-align: {$alignMobile} !important;
}
.ds-mobile-mode #{$logoId} .ds-block-logo,
.ds-device-375 #{$logoId} .ds-block-logo,
.ds-device-768 #{$logoId} .ds-block-logo {
    flex-direction: {$flexDirMobile} !important;
    align-items: {$alignItemsMobile} !important;
    justify-content: {$justifyMobile} !important;
    gap: 6px !important;
}
.ds-mobile-mode #{$logoId} .ds-logo-img,
.ds-device-375 #{$logoId} .ds-logo-img,
.ds-device-768 #{$logoId} .ds-logo-img {
    height: {$heightMobile} !important;
    {$showImgMobileCss}
}
.ds-mobile-mode #{$logoId} .ds-logo-text,
.ds-device-375 #{$logoId} .ds-logo-text,
.ds-device-768 #{$logoId} .ds-logo-text {
    font-size: {$fontSizeMobile} !important;
    font-weight: {$fontWeightMobile} !important;
    color: {$textColorMobile} !important;
    {$showTxtMobileCss}
}
.ds-mobile-mode #{$logoId} .ds-logo-text-desktop,
.ds-device-375 #{$logoId} .ds-logo-text-desktop,
.ds-device-768 #{$logoId} .ds-logo-text-desktop {
    display: none !important;
}
.ds-mobile-mode #{$logoId} .ds-logo-text-mobile,
.ds-device-375 #{$logoId} .ds-logo-text-mobile,
.ds-device-768 #{$logoId} .ds-logo-text-mobile {
    display: inline-block !important;
}
</style>
<div id="{$logoId}" class="ds-block-logo-root">
    <div class="ds-block-logo-wrapper">
        <a href="{$safeLink}" class="ds-block-logo">
            {$imgHtml}
            {$textHtml}
        </a>
    </div>
</div>
HTML;
    }

    public function getSchema(): array
    {
        return [
            'type' => 'logo',
            'fields' => [
                'image_url'             => ['type' => 'media', 'label' => 'URL da Imagem do Logo', 'default' => 'assets/img/logo.png'],
                'image_url_mobile'      => ['type' => 'media', 'label' => 'URL da Imagem do Logo (Mobile)', 'default' => ''],
                'brand_text'            => ['type' => 'text', 'label' => 'Texto da Marca', 'default' => 'Domain<span class="text-primary">System</span>'],
                'text_mobile'           => ['type' => 'text', 'label' => 'Texto da Marca (Mobile)', 'default' => ''],
                'text_color'            => ['type' => 'color', 'label' => 'Cor do Texto da Marca', 'default' => 'var(--color-text-main)'],
                'font_family'           => ['type' => 'font', 'label' => 'Família da Fonte', 'default' => 'var(--font-primary)'],
                'font_size'             => ['type' => 'dimension', 'label' => 'Tamanho do Texto', 'units' => ['rem', 'px'], 'default' => '1.4rem'],
                'font_size_mobile'      => ['type' => 'dimension', 'label' => 'Tamanho do Texto (Mobile)', 'units' => ['rem', 'px'], 'default' => '1.1rem'],
                'font_weight'           => ['type' => 'select', 'label' => 'Peso da Fonte', 'options' => ['400', '500', '600', '700', '800', '900'], 'default' => '800'],
                'font_weight_mobile'    => ['type' => 'select', 'label' => 'Peso da Fonte (Mobile)', 'options' => ['400', '500', '600', '700', '800', '900'], 'default' => '500'],
                'height'                => ['type' => 'dimension', 'label' => 'Altura do Logo', 'units' => ['px', 'rem'], 'default' => '40px'],
                'height_mobile'         => ['type' => 'dimension', 'label' => 'Altura do Logo (Mobile)', 'units' => ['px', 'rem'], 'default' => '30px'],
                'align'                 => ['type' => 'select', 'label' => 'Alinhamento', 'options' => ['left', 'center', 'right'], 'default' => 'left'],
                'align_mobile'          => ['type' => 'select', 'label' => 'Alinhamento (Mobile)', 'options' => ['left', 'center', 'right'], 'default' => 'center'],
                'flex_direction'        => ['type' => 'select', 'label' => 'Direção Flex', 'options' => ['row', 'column'], 'default' => 'row'],
                'flex_direction_mobile' => ['type' => 'select', 'label' => 'Direção Flex (Mobile)', 'options' => ['row' => 'Lado a Lado (Horizontal)', 'column' => 'Empilhado (Vertical)'], 'default' => 'row'],
                'show_image'            => ['type' => 'boolean', 'label' => 'Exibir Imagem', 'default' => true],
                'show_image_mobile'     => ['type' => 'boolean', 'label' => 'Exibir Imagem (Mobile)', 'default' => true],
                'show_text'             => ['type' => 'boolean', 'label' => 'Exibir Texto da Marca', 'default' => true],
                'show_text_mobile'      => ['type' => 'boolean', 'label' => 'Exibir Texto da Marca (Mobile)', 'default' => true],
                'link'                  => ['type' => 'text', 'label' => 'Link de Destino', 'default' => '/'],
            ],
        ];
    }
}
