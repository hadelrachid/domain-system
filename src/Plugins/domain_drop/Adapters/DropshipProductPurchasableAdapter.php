<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Adapters;

use DomainSystem\Plugins\commerce\Contracts\PurchasableItemInterface;
use DomainSystem\Plugins\domain_drop\DTO\DropshipProductDto;
use DomainSystem\Plugins\domain_drop\DTO\ProductVariantDto;

/**
 * Adaptador que transforma um Produto do DomainDrop (ou uma SKU específica) em um Item Vendável do Commerce (Padrão Adapter / DIP).
 */
class DropshipProductPurchasableAdapter implements PurchasableItemInterface
{
    private readonly ?ProductVariantDto $variant;

    public function __construct(
        private readonly DropshipProductDto $product,
        ProductVariantDto|string|null $variantOrSku = null
    ) {
        if ($variantOrSku instanceof ProductVariantDto) {
            $this->variant = $variantOrSku;
        } elseif (is_string($variantOrSku) && trim($variantOrSku) !== '') {
            $this->variant = $this->product->findVariantBySku(trim($variantOrSku));
        } else {
            $this->variant = null;
        }
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
        if ($this->variant !== null) {
            $label = $this->variant->getShortLabel();
            if ($label !== '') {
                return $this->product->title . ' — ' . $label;
            }
        }

        return $this->product->title;
    }

    public function getPrice(): float
    {
        if ($this->variant !== null && $this->variant->priceBrl > 0.0) {
            return $this->variant->priceBrl;
        }

        return $this->product->salePriceBrl;
    }

    public function isAvailable(): bool
    {
        if ($this->variant !== null && $this->variant->stock <= 0) {
            return false;
        }

        return in_array($this->product->status, ['published', 'active'], true);
    }

    public function getDescription(): string
    {
        if ($this->variant !== null) {
            $optionsDesc = $this->variant->getFormattedOptions();
            if ($optionsDesc !== '') {
                return "[Variação: {$optionsDesc}]\n\n" . $this->product->description;
            }
        }

        return $this->product->description;
    }

    public function getThumbnailUrl(): ?string
    {
        if ($this->variant !== null && !empty($this->variant->imageUrl)) {
            return $this->variant->imageUrl;
        }

        return $this->product->mainImageUrl;
    }

    public function getProduct(): DropshipProductDto
    {
        return $this->product;
    }

    public function getVariant(): ?ProductVariantDto
    {
        return $this->variant;
    }
}
