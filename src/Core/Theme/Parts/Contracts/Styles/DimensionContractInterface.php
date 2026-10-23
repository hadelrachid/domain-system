<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Contracts\Styles;

use DomainSystem\Core\Theme\Parts\Styles\Values\DimensionValue;

/**
 * Contrato para elementos que suportam dimensionamento de Box Model (SOLID - ISP / Ring 0).
 */
interface DimensionContractInterface
{
    /** Largura do elemento (px, %, vw, cm, auto, etc.) */
    public function getWidth(): ?DimensionValue;

    /** Largura máxima do elemento */
    public function getMaxWidth(): ?DimensionValue;

    /** Altura do elemento (px, vh, rem, etc.) */
    public function getHeight(): ?DimensionValue;

    /** Altura mínima do elemento */
    public function getMinHeight(): ?DimensionValue;
}
