<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\commerce\Contracts;

/**
 * Contrato de Registro e Resolução de Provedores de Pagamento (DIP / SOLID).
 */
interface PaymentGatewayRegistryInterface
{
    public function register(PaymentGatewayInterface $gateway, bool $overwrite = false): void;

    public function get(string $gatewayId): ?PaymentGatewayInterface;

    /**
     * @return array<string, PaymentGatewayInterface>
     */
    public function getAll(): array;

    public function getDefault(): ?PaymentGatewayInterface;
}
