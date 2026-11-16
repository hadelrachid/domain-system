<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Contracts;

use DomainSystem\Plugins\domain_drop\DTO\DemandEvent;

/**
 * Contrato de captura e despacho de eventos de demanda (SRP).
 */
interface DemandTrackerInterface
{
    /**
     * Registra o evento no rastreador aplicando filtro anti-ruído.
     */
    public function recordInteraction(DemandEvent $event): bool;
}
