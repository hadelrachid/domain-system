<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\commerce\Contracts;

/**
 * Contrato para Resolução Dinâmica de Itens Vendáveis (DIP / SOLID).
 */
interface PurchasableItemResolverInterface
{
    /**
     * Localiza e instancia o item vendável a partir do seu tipo, ID e contexto opcional.
     *
     * @param array<string, mixed> $context
     */
    public function resolve(string $itemType, int $itemId, array $context = []): ?PurchasableItemInterface;

    /**
     * Registra um provedor de itens para um tipo específico (ex: 'course').
     *
     * @param callable(int, array<string, mixed>=): ?PurchasableItemInterface $resolver
     */
    public function registerResolver(string $itemType, callable $resolver): void;
}
