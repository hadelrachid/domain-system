<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Contracts;

/**
 * Contrato para provedores modulares de blocos semânticos (SOLID - OCP).
 * Permite que plugins ou extensões registrem conjuntos de blocos sem editar o core.
 */
interface BlockProviderInterface
{
    /**
     * Registra os blocos deste provedor na instância de BlockRegistryInterface.
     */
    public function registerBlocks(BlockRegistryInterface $registry): void;
}
