<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Blocks;

use DomainSystem\Core\Theme\Parts\Contracts\BlockInterface;
use DomainSystem\Core\Theme\Parts\Contracts\PublicUrlResolverInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartInterface;
use DomainSystem\Core\Theme\Parts\Support\BaseUrlResolver;

/**
 * Bloco de Rodapé / Direitos Autorais e Links Institucionais.
 */
class CopyrightBlock implements BlockInterface
{
    private PublicUrlResolverInterface $urlResolver;

    public function __construct(?PublicUrlResolverInterface $urlResolver = null)
    {
        $this->urlResolver = $urlResolver ?? new BaseUrlResolver();
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
        $siteName = defined('DOMAIN_SYSTEM_SITE_NAME') ? DOMAIN_SYSTEM_SITE_NAME : 'Domain System';
        $year = date('Y');
        $rawText = (string)($data['text'] ?? '© {year} {site_name}. Todos os direitos reservados.');

        $text = str_replace(['{year}', '{site_name}'], [$year, $siteName], $rawText);
        $safeText = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        $showLgpd = (bool)($data['show_lgpd_links'] ?? true);
        $baseUrl = $this->urlResolver->getBaseUrl();

        $linksHtml = '';
        if ($showLgpd) {
            $linksHtml = <<<HTML
<div style="display: flex; gap: 16px; justify-content: center; margin-bottom: 12px; font-size: 0.88rem;">
    <a href="{$baseUrl}/sobre" style="color: var(--color-text-muted, #8b8c8d); text-decoration: none;">Sobre Nós</a>
    <a href="{$baseUrl}/privacidade" style="color: var(--color-text-muted, #8b8c8d); text-decoration: none;">Privacidade</a>
    <a href="{$baseUrl}/termos" style="color: var(--color-text-muted, #8b8c8d); text-decoration: none;">Termos de Uso</a>
</div>
HTML;
        }

        return <<<HTML
<div class="ds-block-copyright" style="text-align: center; color: var(--color-text-muted, #8b8c8d); font-size: 0.88rem; line-height: 1.6;">
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
            ],
        ];
    }
}
