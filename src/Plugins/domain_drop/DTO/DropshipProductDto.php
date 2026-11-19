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
     * Verifica se o produto possui variações (cores, tamanhos, modelos, etc.).
     */
    public function hasVariants(): bool
    {
        return !empty($this->attributes['variants']) && is_array($this->attributes['variants']);
    }

    /**
     * Retorna a lista de variantes estruturadas em instâncias de ProductVariantDto.
     *
     * @return array<int, ProductVariantDto>
     */
    public function getVariants(): array
    {
        if (!$this->hasVariants()) {
            return [];
        }

        $result = [];
        foreach ($this->attributes['variants'] as $item) {
            if ($item instanceof ProductVariantDto) {
                $result[] = $item;
            } elseif (is_array($item)) {
                $result[] = ProductVariantDto::fromArray($item, $this->salePriceBrl, $this->costPriceUsd);
            }
        }

        return $result;
    }

    /**
     * Retorna os eixos de variação e seus valores únicos disponíveis.
     * Exemplo: ['Cor' => ['Preto', 'Vermelho Vinho'], 'Tamanho' => ['P', 'M', 'G']]
     *
     * @return array<string, array<int, string>>
     */
    public function getVariationAxes(): array
    {
        if (!empty($this->attributes['variation_axes']) && is_array($this->attributes['variation_axes'])) {
            return $this->attributes['variation_axes'];
        }

        $axes = [];
        foreach ($this->getVariants() as $variant) {
            foreach ($variant->options as $axis => $val) {
                $axis = trim((string)$axis);
                $val = trim((string)$val);
                if ($axis !== '' && $val !== '') {
                    if (!isset($axes[$axis])) {
                        $axes[$axis] = [];
                    }
                    if (!in_array($val, $axes[$axis], true)) {
                        $axes[$axis][] = $val;
                    }
                }
            }
        }

        return $axes;
    }

    /**
     * Verifica se um determinado valor de opção possui pelo menos uma SKU com estoque ativo.
     */
    public function isOptionAvailable(string $axis, string $value): bool
    {
        $variants = $this->getVariants();
        if (empty($variants)) {
            return true;
        }

        foreach ($variants as $variant) {
            if (isset($variant->options[$axis]) && $variant->options[$axis] === $value && $variant->isAvailable()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Localiza uma variante específica pelo código de SKU.
     */
    public function findVariantBySku(string $skuId): ?ProductVariantDto
    {
        $skuId = trim($skuId);
        if ($skuId === '') {
            return null;
        }

        foreach ($this->getVariants() as $variant) {
            if ($variant->skuId === $skuId) {
                return $variant;
            }
        }

        return null;
    }

    /**
     * Localiza uma variante pela combinação de opções (ex: ['Cor' => 'Vermelho Vinho', 'Tamanho' => 'M']).
     *
     * @param array<string, string> $selectedOptions
     */
    public function findVariantByOptions(array $selectedOptions): ?ProductVariantDto
    {
        if (empty($selectedOptions)) {
            return null;
        }

        foreach ($this->getVariants() as $variant) {
            if ($variant->matchesOptions($selectedOptions)) {
                return $variant;
            }
        }

        return null;
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
