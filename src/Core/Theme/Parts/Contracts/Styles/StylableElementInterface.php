<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Contracts\Styles;

use DomainSystem\Core\Theme\Parts\Contracts\Responsive\ResponsiveContractInterface;

/**
 * Contrato de composição mestre para elementos totalmente estilizáveis no Flex Builder (SOLID - ISP / OCP).
 * Implementado tanto por Blocos visuais quanto por Seções estruturais.
 */
interface StylableElementInterface extends
    ColorContractInterface,
    DimensionContractInterface,
    BorderContractInterface,
    ShadowContractInterface,
    AnimationContractInterface
{
    /**
     * Retorna todas as propriedades de estilo compiladas em array para renderização CSS.
     * @return array<string, mixed>
     */
    public function getStyleProperties(): array;
}
