<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\DTO;

/**
 * DTO Imutável com a representação canônica de um produto minerado do Dropshipping.
 */
final class DropshipProductDto
{
    /**
     * @param array<int, string> $images Lista de URLs de imagens do produto
     * @param array<string, mixed> $attributes Especificações técnicas e variações
     */
    public function __construct(
        public readonly string $aliexpressId,
        public readonly string $title,
        public readonly string $slug,
        public readonly float $costPriceUsd,
        public readonly float $salePriceBrl,
        public readonly string $mainImageUrl,
        public readonly array $images = [],
        public readonly string $category = 'Geral',
        public readonly string $description = '',
        public readonly float $rating = 4.8,
        public readonly int $ordersCount = 0,
        public readonly array $attributes = [],
        public readonly string $status = 'published',
        public readonly ?int $id = null
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'               => $this->id,
            'aliexpress_id'    => $this->aliexpressId,
            'title'            => $this->title,
            'slug'             => $this->slug,
            'cost_price_usd'   => $this->costPriceUsd,
            'sale_price_brl'   => $this->salePriceBrl,
            'main_image_url'   => $this->mainImageUrl,
            'images'           => json_encode($this->images, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'category'         => $this->category,
            'description'      => $this->description,
            'rating'           => $this->rating,
            'orders_count'     => $this->ordersCount,
            'attributes'       => json_encode($this->attributes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'status'           => $this->status,
        ];
    }
}
