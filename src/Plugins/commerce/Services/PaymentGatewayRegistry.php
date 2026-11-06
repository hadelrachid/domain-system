<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\commerce\Services;

use DomainSystem\Plugins\commerce\Contracts\PaymentGatewayInterface;
use DomainSystem\Plugins\commerce\Contracts\PaymentGatewayRegistryInterface;

class PaymentGatewayRegistry implements PaymentGatewayRegistryInterface
{
    /**
     * @var array<string, PaymentGatewayInterface>
     */
    private array $gateways = [];
    private ?string $defaultGatewayId = null;

    public function register(PaymentGatewayInterface $gateway, bool $overwrite = false): void
    {
        $id = $gateway->getId();
        if (isset($this->gateways[$id]) && !$overwrite) {
            throw new \DomainException("O gateway de pagamento com identificador '{$id}' já está registrado.");
        }

        $this->gateways[$id] = $gateway;

        if ($this->defaultGatewayId === null) {
            $this->defaultGatewayId = $id;
        }
    }

    public function get(string $gatewayId): ?PaymentGatewayInterface
    {
        return $this->gateways[$gatewayId] ?? null;
    }

    public function getAll(): array
    {
        return $this->gateways;
    }

    public function getDefault(): ?PaymentGatewayInterface
    {
        if ($this->defaultGatewayId !== null && isset($this->gateways[$this->defaultGatewayId])) {
            return $this->gateways[$this->defaultGatewayId];
        }

        $first = reset($this->gateways);
        return $first instanceof PaymentGatewayInterface ? $first : null;
    }
}
