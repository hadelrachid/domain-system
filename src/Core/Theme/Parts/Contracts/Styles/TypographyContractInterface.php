<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Contracts\Styles;

use DomainSystem\Core\Theme\Parts\Styles\Values\DimensionValue;

/**
 * Contrato para elementos com propriedades tipográficas editáveis (SOLID - ISP / Ring 0).
 */
interface TypographyContractInterface
{
    /** Família da fonte (Token: var(--font-primary) ou família específica) */
    public function getFontFamily(): ?string;

    /** Tamanho da fonte (16px, 1.25rem, 24pt, etc.) */
    public function getFontSize(): ?DimensionValue;

    /** Peso da fonte (100 a 900) */
    public function getFontWeight(): ?string;

    /** Altura de linha / entrelinha (1.5, 24px, 120%) */
    public function getLineHeight(): ?string;

    /** Espaçamento entre letras (letter-spacing) */
    public function getLetterSpacing(): ?DimensionValue;

    /** Transformação de texto (none, uppercase, lowercase, capitalize) */
    public function getTextTransform(): ?string;

    /** Alinhamento do texto (left, center, right, justify) */
    public function getTextAlign(): ?string;
}
