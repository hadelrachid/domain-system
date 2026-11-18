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

    /**
     * Promove retroativamente interações anônimas de uma sessão e/ou endereço de IP para um cliente recém-identificado.
     * Atualiza o log em arquivo JSON e a trilha de auditoria em banco relacional.
     *
     * @param string $sessionId Identificador da sessão PHP a promover
     * @param int $customerId ID do cliente autenticado/cadastrado
     * @param string $customerName Nome do cliente
     * @param string $customerEmail E-mail do cliente
     * @param string|null $ipAddress Endereço IP para correlação de dispositivo/rede
     * @return int Quantidade total de registros promovidos
     */
    public function promoteSession(
        string $sessionId,
        int $customerId,
        string $customerName,
        string $customerEmail,
        ?string $ipAddress = null
    ): int;
}
