<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Contracts;

/**
 * Subcontrato para partes estruturais compostas por múltiplas seções (SOLID - ISP).
 */
interface SectionedPartInterface
{
    /**
     * Lista de seções estruturais acopladas a esta parte (cada seção com sua grade e blocos).
     * @return array<int, array<string, mixed>>
     */
    public function getSections(): array;
}
