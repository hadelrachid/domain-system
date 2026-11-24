<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\DTO;

/**
 * DTO Imutável com a representação canônica de uma variação / SKU de produto (Padrão SKU Matrix).
 *
 * Suporta combinações de cores, tamanhos, modelos de celulares, materiais de pulseira, etc.
 * Mapeia de forma 1:1 com as SKUs oficiais da AliExpress Open Platform.
 */
final class ProductVariantDto
{
    /**
     * @param string $skuId Código único da SKU no AliExpress ou gerado internamente
     * @param string $skuAttr Código de atributos concatenados do AliExpress (ex: "14:200000182;5:100014064")
     * @param array<string, string> $options Mapa chave => valor das opções selecionadas (ex: ['Cor' => 'Vermelho Vinho', 'Tamanho' => 'M'])
     * @param float $priceBrl Preço de venda final em BRL para esta combinação
     * @param float $costPriceUsd Preço de custo em USD no fornecedor AliExpress
     * @param string|null $imageUrl Foto específica desta variação (ex: foto da capa na cor selecionada)
     * @param int $stock Estoque disponível específico desta variação
     * @param string $eanCode Código de barras oficial EAN / GTIN específico desta SKU
     */
    public function __construct(
        public readonly string $skuId,
        public readonly string $skuAttr = '',
        public readonly array $options = [],
        public readonly float $priceBrl = 0.0,
        public readonly float $costPriceUsd = 0.0,
        public readonly ?string $imageUrl = null,
        public readonly int $stock = 100,
        public readonly string $eanCode = ''
    ) {
    }

    /**
     * Informa se a variação possui estoque disponível para compra.
     */
    public function isAvailable(): bool
    {
        return $this->stock > 0;
    }

    /**
     * Retorna a descrição formatada das opções selecionadas (ex: "Cor: Vermelho Vinho • Tamanho: M").
     */
    public function getFormattedOptions(): string
    {
        if (empty($this->options)) {
            return '';
        }

        $parts = [];
        foreach ($this->options as $axis => $value) {
            $parts[] = "{$axis}: {$value}";
        }

        return implode(' • ', $parts);
    }

    /**
     * Retorna uma representação curta das opções (ex: "Vermelho Vinho / M").
     */
    public function getShortLabel(): string
    {
        if (empty($this->options)) {
            return '';
        }

        return implode(' / ', array_values($this->options));
    }

    /**
     * Verifica se esta variante atende a um conjunto específico de opções escolhidas pelo comprador.
     *
     * @param array<string, string> $selectedOptions
     */
    public function matchesOptions(array $selectedOptions): bool
    {
        if (empty($selectedOptions)) {
            return false;
        }

        foreach ($selectedOptions as $axis => $val) {
            $curVal = $this->options[$axis] ?? null;
            if ($curVal === null || strcasecmp(trim((string)$curVal), trim((string)$val)) !== 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'sku_id'         => $this->skuId,
            'sku_attr'       => $this->skuAttr,
            'options'        => $this->options,
            'price_brl'      => $this->priceBrl,
            'cost_price_usd' => $this->costPriceUsd,
            'image_url'      => $this->imageUrl,
            'stock'          => $this->stock,
            'ean_code'       => $this->eanCode,
        ];
    }

    /**
     * Cria uma instância a partir de array associativo.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, float $fallbackPriceBrl = 0.0, float $fallbackCostUsd = 0.0): self
    {
        $options = [];
        if (isset($data['options']) && is_array($data['options'])) {
            foreach ($data['options'] as $k => $v) {
                $options[(string)$k] = (string)$v;
            }
        }

        return new self(
            skuId: (string)($data['sku_id'] ?? ($data['skuId'] ?? uniqid('sku_'))),
            skuAttr: (string)($data['sku_attr'] ?? ($data['skuAttr'] ?? '')),
            options: $options,
            priceBrl: (float)($data['price_brl'] ?? ($data['priceBrl'] ?? $fallbackPriceBrl)),
            costPriceUsd: (float)($data['cost_price_usd'] ?? ($data['costPriceUsd'] ?? $fallbackCostUsd)),
            imageUrl: !empty($data['image_url']) ? (string)$data['image_url'] : (!empty($data['imageUrl']) ? (string)$data['imageUrl'] : null),
            stock: (int)($data['stock'] ?? 100),
            eanCode: trim((string)($data['ean_code'] ?? ($data['gtin'] ?? ($data['ean'] ?? ''))))
        );
    }
}
