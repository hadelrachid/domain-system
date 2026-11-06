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

    /** Cor do texto / conteúdo tipográfico do elemento (var(--color-primary), var(--color-secondary), etc.) */
    public function getTextColor(): ?ColorValue;

    /** Cor de títulos / cabeçalhos internos do elemento ou container */
    public function getHeadingColor(): ?ColorValue;

    /** Cor de links internos do elemento ou container */
    public function getLinkColor(): ?ColorValue;

    /** Cor de links ao passar o mouse (hover) ou ativos */
    public function getLinkHoverColor(): ?ColorValue;

    /** Cor da borda do elemento */
    public function getBorderColor(): ?ColorValue;
}
