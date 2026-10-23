<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Contracts\Styles;

use DomainSystem\Core\Theme\Parts\Styles\Values\ColorValue;

/**
 * Contrato para elementos que suportam propriedades cromáticas (SOLID - ISP / Ring 0).
 */
interface ColorContractInterface
{
    /** Cor de fundo do elemento (Token Semântico, Hex, RGBA ou Gradiente) */
    public function getBackgroundColor(): ?ColorValue;

    /** Cor do texto / conteúdo tipográfico do elemento */
    public function getTextColor(): ?ColorValue;

    /** Cor da borda do elemento */
    public function getBorderColor(): ?ColorValue;
}
