<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Contracts\Responsive;

/**
 * Contrato de responsividade multidispositivo com herança em cascata (SOLID - ISP / Ring 0).
 */
interface ResponsiveContractInterface
{
    public const BREAKPOINT_DESKTOP = 'desktop'; // >= 1200px (100%)
    public const BREAKPOINT_LAPTOP  = 'laptop';  // 1024px
    public const BREAKPOINT_TABLET  = 'tablet';  // 768px
    public const BREAKPOINT_MOBILE  = 'mobile';  // 375px

    /**
     * Retorna o valor resolvido de uma propriedade para um breakpoint específico,
     * aplicando o fallback em cascata (Desktop -> Laptop -> Tablet -> Mobile).
     */
    public function resolveStyleForDevice(string $device, string $property): mixed;

    /**
     * Retorna todos os estilos definidos para um breakpoint.
     * @return array<string, mixed>
     */
    public function getStylesForBreakpoint(string $device): array;
}
