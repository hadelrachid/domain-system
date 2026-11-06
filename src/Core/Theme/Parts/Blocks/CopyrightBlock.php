<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Blocks;

use DomainSystem\Core\Theme\Parts\Contracts\BlockInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ElementStyleRendererInterface;
use DomainSystem\Core\Theme\Parts\Contracts\PublicUrlResolverInterface;
use DomainSystem\Core\Theme\Parts\Contracts\Styles\StylableElementInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartInterface;
use DomainSystem\Core\Theme\Parts\Styles\ElementStyleRenderer;
use DomainSystem\Core\Theme\Parts\Styles\Traits\StylableElementTrait;
use DomainSystem\Core\Theme\Parts\Support\BaseUrlResolver;

/**
 * Bloco de Rodapé / Direitos Autorais e Links Institucionais.
 */
class CopyrightBlock implements BlockInterface, StylableElementInterface
{
    use StylableElementTrait;

    private PublicUrlResolverInterface $urlResolver;
    private ElementStyleRendererInterface $styleRenderer;

    public function __construct(
        ?PublicUrlResolverInterface $urlResolver = null,
        ?ElementStyleRendererInterface $styleRenderer = null
    ) {
        $this->urlResolver = $urlResolver ?? new BaseUrlResolver();
        $this->styleRenderer = $styleRenderer ?? new ElementStyleRenderer();
    }

    public function getType(): string
    {
        return 'copyright';
    }

    public function getName(): string
    {
        return 'Direitos Autorais (Copyright)';
    }

    public function getIcon(): string
    {
        return '©';
    }

    public function getCategory(): string
    {
        return 'footer';
    }

    public function getAllowedPartTypes(): array
    {
        return [ThemePartInterface::TYPE_FOOTER];
    }

    public function render(array $data = [], array $context = []): string
    {
        $this->styleData = $data;
        $defaultSiteName = defined('DOMAIN_SYSTEM_SITE_NAME') ? DOMAIN_SYSTEM_SITE_NAME : 'Domain System';
        $siteName = !empty($data['site_name']) ? trim((string)$data['site_name']) : $defaultSiteName;
        $year = date('Y');
        $rawText = (string)($data['text'] ?? '© {year} {site_name}. Todos os direitos reservados.');

        $text = str_replace(['{year}', '{site_name}'], [$year, $siteName], $rawText);
        if (!empty($data['site_name']) && !str_contains($rawText, '{site_name}') && !str_contains($text, $siteName)) {
            $text .= ' — ' . $siteName;
        }
        $safeText = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        $showLgpd = (bool)($data['show_lgpd_links'] ?? true);
        $baseUrl = $this->urlResolver->getBaseUrl();

        $textColor = $this->styleRenderer->sanitizeCssValue((string)($data['text_color'] ?? $data['color'] ?? 'var(--ds-local-text-color, var(--color-text-muted, #8b8c8d))'));
        $linkColor = $this->styleRenderer->sanitizeCssValue((string)($data['link_color'] ?? $data['text_color'] ?? $data['color'] ?? 'var(--ds-local-link-color, var(--color-text-muted, #8b8c8d))'));
        $fontSize = $this->styleRenderer->sanitizeCssValue((string)($data['font_size'] ?? $data['size'] ?? '0.88rem'));
        $align = $this->styleRenderer->sanitizeCssValue((string)($data['align'] ?? $data['text_align'] ?? 'center'));
        $fontFamily = $this->styleRenderer->sanitizeCssValue((string)($data['font_family'] ?? ''));

        $resolvedFont = match ($fontFamily) {
            'secondary' => 'var(--font-secondary, monospace)',
            'tertiary'  => 'var(--font-tertiary, sans-serif)',
            'primary'   => 'var(--font-primary, sans-serif)',
            default     => $fontFamily !== '' ? $fontFamily : 'var(--font-primary, inherit)',
        };

        $justifyLinks = match ($align) {
            'left'  => 'flex-start',
            'right' => 'flex-end',
            default => 'center',
        };

        $linksHtml = '';
        if ($showLgpd) {
            $linksHtml = <<<HTML
<div style="display: flex; gap: 16px; justify-content: {$justifyLinks}; margin-bottom: 12px; font-size: {$fontSize};">
    <a href="{$baseUrl}/sobre" style="color: {$linkColor}; text-decoration: none;">Sobre Nós</a>
    <a href="{$baseUrl}/privacidade" style="color: {$linkColor}; text-decoration: none;">Privacidade</a>
    <a href="{$baseUrl}/termos" style="color: {$linkColor}; text-decoration: none;">Termos de Uso</a>
</div>
HTML;
        }

        return <<<HTML
<div class="ds-block-copyright" style="text-align: {$align}; color: {$textColor}; font-size: {$fontSize}; font-family: {$resolvedFont}; line-height: 1.6;">
    {$linksHtml}
    <div>{$safeText}</div>
</div>
HTML;
    }

    public function getSchema(): array
    {
        return [
            'type' => 'copyright',
            'fields' => [
                'text'            => ['type' => 'text', 'label' => 'Texto (usa {year} e {site_name})', 'default' => '© {year} {site_name}. Todos os direitos reservados.'],
                'show_lgpd_links' => ['type' => 'boolean', 'label' => 'Exibir Links (Sobre, Privacidade, Termos)', 'default' => true],
                'text_color'      => ['type' => 'color', 'label' => 'Cor do Texto', 'default' => 'var(--color-text-muted)'],
                'link_color'      => ['type' => 'color', 'label' => 'Cor dos Links', 'default' => 'var(--color-text-muted)'],
                'font_size'       => ['type' => 'dimension', 'label' => 'Tamanho da Fonte', 'units' => ['rem', 'px'], 'default' => '0.88rem'],
                'align'           => ['type' => 'select', 'label' => 'Alinhamento', 'options' => ['left', 'center', 'right'], 'default' => 'center'],
            ],
        ];
    }
}
