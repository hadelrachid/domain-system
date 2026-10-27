<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Contracts;

/**
 * Subcontrato para partes (como Headers) que configuram o botão hamburguer/toggle mobile (SOLID - ISP).
 */
interface MobileToggleAwareInterface
{
    /** Estilo do botão de toggle mobile do menu (classic, pill, minimal, dots) */
    public function getMobileToggleStyle(): string;

    /** Posição do botão de toggle mobile do menu (left, center, right) */
    public function getMobileToggleAlign(): string;
}
