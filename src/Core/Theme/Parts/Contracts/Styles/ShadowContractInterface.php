<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Contracts\Styles;

/**
 * Contrato para sombras de caixa e brilho neon com presets tipados (SOLID - ISP / Ring 0).
 */
interface ShadowContractInterface
{
    public const SHADOW_NONE         = 'none';
    public const SHADOW_SM           = '0 2px 4px rgba(0,0,0,0.15)';
    public const SHADOW_MD           = '0 4px 12px rgba(0,0,0,0.25)';
    public const SHADOW_LG           = '0 10px 25px rgba(0,0,0,0.4)';
    public const SHADOW_GLOW_PRIMARY = '0 0 15px var(--primary, rgba(69,243,255,0.4))';
    public const SHADOW_GLOW_ACCENT  = '0 0 15px var(--tertiary, rgba(96,165,250,0.4))';

    /** Preset da sombra (none, sm, md, lg, glow_primary, glow_accent, custom) */
    public function getShadowPreset(): string;

    /** String CSS bruta da sombra (resolvida via preset ou customizada) */
    public function getBoxShadowCss(): ?string;
}
