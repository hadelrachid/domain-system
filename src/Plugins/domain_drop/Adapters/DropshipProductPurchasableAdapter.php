<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Adapters;

use DomainSystem\Plugins\commerce\Contracts\PurchasableItemInterface;
use DomainSystem\Plugins\domain_drop\DTO\DropshipProductDto;

/**
 * Adaptador que transforma um Produto do DomainDrop em um Item Vendável do Commerce (Padrão Adapter / DIP).
 */
class DropshipProductPurchasableAdapter implements PurchasableItemInterface
{
    public function __construct(
        private readonly DropshipProductDto $product
    ) {
    }

    public function getId(): int
    {
        return (int)($this->product->id ?? 0);
    }

    public function getItemType(): string
    {
        return 'dropship_product';
    }

    public function getTitle(): string
    {
        return $this->product->title;
    }

    public function getPrice(): float
    {
        return $this->product->salePriceBrl;
    }

    public function isAvailable(): bool
    {
        return in_array($this->product->status, ['published', 'active'], true);
    }

    public function getDescription(): string
    {
        return $this->product->description;
    }

    public function getThumbnailUrl(): ?string
    {
        return $this->product->mainImageUrl;
    }

    public function getProduct(): DropshipProductDto
    {
        return $this->product;
    }
}
