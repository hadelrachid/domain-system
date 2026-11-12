<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Contracts;

/**
 * Contrato de persistência de reclamações / mediações com fornecedores.
 */
interface DropshipDisputeRepositoryInterface
{
    public function create(array $data): int;

    public function findById(int $id): ?array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findByOrderRef(string $orderRef): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findByCustomerEmail(string $email): array;

    /**
     * @param array<int, string> $orderRefs
     * @return array<string, array<int, array<string, mixed>>> Mapa order_ref => lista de reclamações
     */
    public function mapByOrderRefs(array $orderRefs): array;

    public function markMediationSent(
        int $disputeId,
        string $sellerLanguage,
        string $translatedMessage,
        string $channel = 'email'
    ): bool;
}
