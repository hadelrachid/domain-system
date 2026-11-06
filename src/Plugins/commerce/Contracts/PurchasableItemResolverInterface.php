<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\commerce\Contracts;

/**
 * Contrato para Resolução Dinâmica de Itens Vendáveis (DIP / SOLID).
 */
interface PurchasableItemResolverInterface
{
    /**
     * Localiza e instancia o item vendável a partir do seu tipo e ID.
     */
    public function resolve(string $itemType, int $itemId): ?PurchasableItemInterface;

    /**
     * Registra um provedor de itens para um tipo específico (ex: 'course').
     *
     * @param callable(int): ?PurchasableItemInterface $resolver
     */
    public function registerResolver(string $itemType, callable $resolver): void;
}
