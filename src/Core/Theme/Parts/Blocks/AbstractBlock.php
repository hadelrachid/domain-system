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
 * Abstração Pai de Todos os Blocos Semânticos (SOLID - OCP / LSP / DIP).
 *
 * Garante que cada bloco do sistema:
 * 1. Siga rigorosamente o mesmo contrato e abstração pai.
 * 2. Seja 100% independente, desacoplado e autônomo (SRP).
 * 3. Tenha suas dependências (sanitizador, url resolver, renderizador de estilos) injetadas de forma desacoplada.
 */
abstract class AbstractBlock implements BlockInterface, StylableElementInterface
{
    use AllowedPartTypesTrait;
    use StylableElementTrait;

    protected HtmlSanitizerInterface $htmlSanitizer;
    protected PublicUrlResolverInterface $urlResolver;
    protected ElementStyleRendererInterface $styleRenderer;

    public function __construct(
        ?HtmlSanitizerInterface $htmlSanitizer = null,
        ?PublicUrlResolverInterface $urlResolver = null,
        ?ElementStyleRendererInterface $styleRenderer = null
    ) {
        $this->htmlSanitizer = $htmlSanitizer ?? new SafeHtmlSanitizer();
        $this->urlResolver = $urlResolver ?? new BaseUrlResolver();
        $this->styleRenderer = $styleRenderer ?? new ElementStyleRenderer();
    }

    /**
     * Resolve um valor responsivo com fallback em cascata (Mobile -> Tablet -> Laptop -> Desktop).
     */
    protected function getResponsiveValue(array $data, string $key, string $device = 'desktop', mixed $default = null): mixed
    {
        if ($device === 'mobile') {
            return $data[$key . '_mobile'] ?? ($data[$key . '_tablet'] ?? ($data[$key . '_laptop'] ?? ($data[$key] ?? $default)));
        }
        if ($device === 'tablet') {
            return $data[$key . '_tablet'] ?? ($data[$key . '_laptop'] ?? ($data[$key] ?? $default));
        }
        if ($device === 'laptop') {
            return $data[$key . '_laptop'] ?? ($data[$key] ?? $default);
        }
        return $data[$key] ?? $default;
    }

    /**
     * Resolve a família tipográfica para a variável CSS correspondente de forma segura.
     */
    protected function resolveFontFamily(string $fontFamily): string
    {
        return match ($fontFamily) {
            'secondary' => 'var(--font-secondary, monospace)',
            'tertiary'  => 'var(--font-tertiary, sans-serif)',
            'primary'   => 'var(--font-primary, sans-serif)',
            default     => $fontFamily !== '' ? $fontFamily : 'var(--font-primary, inherit)',
        };
    }

    /**
     * Gera um identificador único para o bloco, preservando _id existente caso informado.
     */
    protected function generateBlockId(array $data, string $prefix = 'ds_block'): string
    {
        if (!empty($data['_id'])) {
            return (string)$data['_id'];
        }
        return $prefix . '_' . substr(md5(uniqid((string)mt_rand(), true)), 0, 8);
    }
}
