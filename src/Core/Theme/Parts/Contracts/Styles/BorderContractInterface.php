<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Contracts\Styles;

use DomainSystem\Core\Theme\Parts\Styles\Values\ColorValue;
use DomainSystem\Core\Theme\Parts\Styles\Values\DimensionValue;

/**
 * Contrato para estilização de bordas e raio de arredondamento (SOLID - ISP / Ring 0).
 */
interface BorderContractInterface
{
    /** Estilo da borda (none, solid, dashed, dotted, double) */
    public function getBorderStyle(): ?string;

    /** Espessura da borda (px, pt, cm) */
    public function getBorderWidth(): ?DimensionValue;

    /** Cor da borda */
    public function getBorderColor(): ?ColorValue;

    /** Raio de arredondamento da borda (border-radius) */
    public function getBorderRadius(): ?DimensionValue;
}
