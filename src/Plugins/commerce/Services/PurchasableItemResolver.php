<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\commerce\Services;

use DomainSystem\Plugins\commerce\Contracts\PurchasableItemInterface;
use DomainSystem\Plugins\commerce\Contracts\PurchasableItemResolverInterface;

class PurchasableItemResolver implements PurchasableItemResolverInterface
{
    /**
     * @var array<string, callable(int): ?PurchasableItemInterface>
     */
    private array $resolvers = [];

    public function registerResolver(string $itemType, callable $resolver): void
    {
        $this->resolvers[$itemType] = $resolver;
    }

    public function resolve(string $itemType, int $itemId): ?PurchasableItemInterface
    {
        if (!isset($this->resolvers[$itemType])) {
            return null;
        }

        $resolver = $this->resolvers[$itemType];
        return $resolver($itemId);
    }
}
