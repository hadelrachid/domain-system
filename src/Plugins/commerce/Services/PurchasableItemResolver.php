<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\commerce\Services;

use DomainSystem\Plugins\commerce\Contracts\PurchasableItemInterface;
use DomainSystem\Plugins\commerce\Contracts\PurchasableItemResolverInterface;

class PurchasableItemResolver implements PurchasableItemResolverInterface
{
    /**
     * @var array<string, callable(int, array<string, mixed>=): ?PurchasableItemInterface>
     */
    private array $resolvers = [];

    /**
     * Mapa de aliases para normalização de tipos canônicos (ex: dropship -> dropship_product).
     * @var array<string, string>
     */
    private static array $aliasMap = [
        'dropship' => 'dropship_product',
        'product'  => 'dropship_product',
    ];

    public function registerResolver(string $itemType, callable $resolver): void
    {
        $this->resolvers[$itemType] = $resolver;
    }

    public function resolve(string $itemType, int $itemId, array $context = []): ?PurchasableItemInterface
    {
        $canonicalType = self::$aliasMap[$itemType] ?? $itemType;

        if (!isset($this->resolvers[$canonicalType])) {
            return null;
        }

        $resolver = $this->resolvers[$canonicalType];
        try {
            return $resolver($itemId, $context);
        } catch (\ArgumentCountError) {
            return $resolver($itemId);
        }
    }
}
