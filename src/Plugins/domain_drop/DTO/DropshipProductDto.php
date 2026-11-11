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
     * Retorna identificação estruturada do vendedor / entreposto responsável por este produto.
     *
     * @return array{seller_id: string, store_name: string, warehouse: string, aliexpress_id: string}
     */
    public function getSellerInfo(): array
    {
        $cleanAe = preg_replace('/\W+/', '', $this->aliexpressId) ?: '';
        $codeSeed = $cleanAe !== '' ? substr($cleanAe, -6) : str_pad((string)($this->id ?? 0), 6, '0', STR_PAD_LEFT);
        $shortSeed = substr($codeSeed, -4);
        $isNational = !empty($this->attributes['origem_brasil'])
            || (($this->attributes['ship_from'] ?? '') === 'BR');

        $sellerId = trim((string)($this->attributes['seller_id'] ?? ($this->attributes['shop_id'] ?? '')));
        if ($sellerId === '') {
            $sellerId = 'VND-' . strtoupper($codeSeed);
        }

        $storeName = trim((string)($this->attributes['seller_name'] ?? ($this->attributes['store_name'] ?? '')));
        if ($storeName === '') {
            $storeName = $isNational
                ? 'Distribuidor Oficial BR #' . $shortSeed
                : 'Fornecedor Oficial Global #' . $shortSeed;
        }

        return [
            'seller_id'     => $sellerId,
            'store_name'    => $storeName,
            'warehouse'     => $isNational ? 'Entreposto Nacional (Brasil)' : 'Centro Internacional',
            'aliexpress_id' => $this->aliexpressId,
        ];
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
