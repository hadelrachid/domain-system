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
     * Extrai e calcula o peso estimado do produto em gramas (g).
     * Analisa com prioridade:
     * 1. Atributos diretos ('weight', 'peso', 'package_weight', etc.)
     * 2. Especificações técnicas nas chaves ou valores de $attributes
     * 3. Descrição textual/HTML do vendedor (regex inteligente para 'Peso: ...' ou '...g' / '...kg')
     * 4. Categorização por segmento (tênis, casacos, mochilas, drones, etc.)
     * 5. Padrão de 200g para itens leves (vestuário íntimo, lingeries, acessórios, pulseiras, cosméticos)
     */
    public function getEstimatedWeightInGrams(): int
    {
        // 1. Chaves prioritárias diretas em attributes
        $directKeys = ['weight', 'peso', 'package_weight', 'net_weight', 'gross_weight', 'peso_embalagem', 'peso_liquido', 'peso_bruto'];
        foreach ($directKeys as $key) {
            if (isset($this->attributes[$key])) {
                $parsed = $this->parseWeightStringToGrams((string)$this->attributes[$key]);
                if ($parsed > 0) {
                    return $parsed;
                }
            }
        }

        // 2. Chaves e valores de especificações dentro de attributes
        if (!empty($this->attributes) && is_array($this->attributes)) {
            foreach ($this->attributes as $key => $val) {
                if (is_string($val) && preg_match('/(?:peso|weight)/i', (string)$key)) {
                    $parsed = $this->parseWeightStringToGrams($val);
                    if ($parsed > 0) {
                        return $parsed;
                    }
                } elseif (is_array($val)) {
                    foreach ($val as $subKey => $subVal) {
                        if (is_string($subVal) && preg_match('/(?:peso|weight)/i', (string)$subKey)) {
                            $parsed = $this->parseWeightStringToGrams($subVal);
                            if ($parsed > 0) {
                                return $parsed;
                            }
                        }
                    }
                }
            }
        }

        // 3. Descrição do vendedor (texto ou HTML)
        if (!empty($this->description)) {
            $textDesc = strip_tags(html_entity_decode($this->description, ENT_QUOTES, 'UTF-8'));

            // Regex 1: Menção com rótulo de peso do fornecedor (ex: "Peso: 450g", "Peso do produto: 1.2 kg", "Package weight: 350 g")
            if (preg_match('/(?:peso(?:\s+(?:l[ií]quido|bruto|embalagem|pacote|aproximado|do\s+produto))?|weight(?:\s+(?:net|gross|package|item))?)\s*[:=-]?\s*(\d+(?:[.,]\d+)?)\s*(kg|kilos?|quilos?|g|gr|gramas?)\b/iu', $textDesc, $m)) {
                $val = (float)str_replace(',', '.', $m[1]);
                $unit = strtolower($m[2]);
                return (int)round(str_starts_with($unit, 'k') ? $val * 1000 : $val);
            }

            // Regex 2: Menção explícita de peso em especificações técnicas (ex: "Especificações: 350g")
            if (preg_match('/\b(\d+(?:[.,]\d+)?)\s*(kg|kilos?|quilos?|g|gramas?)\b/iu', $textDesc, $m)) {
                $val = (float)str_replace(',', '.', $m[1]);
                $unit = strtolower($m[2]);
                $grams = (int)round(str_starts_with($unit, 'k') ? $val * 1000 : $val);
                if ($grams >= 20 && $grams <= 50000) {
                    return $grams;
                }
            }
        }

        // 4. Categorias com perfis de peso conhecidos
        $catLower = mb_strtolower($this->category, 'UTF-8');
        if (str_contains($catLower, 'tênis') || str_contains($catLower, 'calçado') || str_contains($catLower, 'sapato') || str_contains($catLower, 'bota')) {
            return 800;
        }
        if (str_contains($catLower, 'casaco') || str_contains($catLower, 'jaqueta') || str_contains($catLower, 'moletom')) {
            return 650;
        }
        if (str_contains($catLower, 'mochila') || str_contains($catLower, 'bolsa')) {
            return 500;
        }
        if (str_contains($catLower, 'drone') || str_contains($catLower, 'projetor') || str_contains($catLower, 'computador')) {
            return 1200;
        }

        // 5. Padrão para itens leves (vestuário íntimo, lingerie, relógio de pulso, fone de ouvido, bijuterias)
        return 200;
    }

    /**
     * Calcula o valor do frete expresso (Sedex / Prioritário) com base no peso total da remessa.
     * Regra oficial dos Correios:
     * - Até 300g: R$ 19,90 (tarifa base expressa)
     * - De 301g até 1.000g (1 kg): R$ 29,90
     * - De 1.001g até 2.000g (2 kg): R$ 39,90
     * - Acima de 2.000g: R$ 39,90 + R$ 10,00 por kg adicional (ou fração de 1kg)
     */
    public function calculateExpressShippingPrice(int $quantity = 1): float
    {
        $quantity = max(1, $quantity);
        $totalWeight = $this->getEstimatedWeightInGrams() * $quantity;

        if ($totalWeight <= 300) {
            return 19.90;
        }

        if ($totalWeight <= 1000) {
            return 29.90;
        }

        if ($totalWeight <= 2000) {
            return 39.90;
        }

        $extraKg = (int)ceil(($totalWeight - 2000) / 1000);
        return round(39.90 + ($extraKg * 10.00), 2);
    }

    /**
     * Retorna o peso formatado para exibição legível (ex: "200g", "1,2 kg").
     */
    public function getFormattedWeight(int $quantity = 1): string
    {
        $quantity = max(1, $quantity);
        $totalGrams = $this->getEstimatedWeightInGrams() * $quantity;
        if ($totalGrams >= 1000) {
            $kg = $totalGrams / 1000;
            return number_format($kg, $totalGrams % 1000 === 0 ? 0 : 1, ',', '.') . ' kg';
        }
        return $totalGrams . 'g';
    }

    /**
     * Retorna a descrição do produto enriquecida para exibição na loja.
     * Insere o Peso Estimado nas Especificações Técnicas e limpa termos de controle interno do sistema.
     */
    public function getEnrichedDescription(): string
    {
        $desc = $this->description;
        $weightInGrams = $this->getEstimatedWeightInGrams();
        $formattedWeight = $this->getFormattedWeight(1);

        $weightLabel = $weightInGrams <= 300
            ? "{$formattedWeight} (Envio Leve até 300g)"
            : "{$formattedWeight}";

        $weightLi = '<li><strong>Peso Estimado:</strong> ' . htmlspecialchars($weightLabel) . '</li>';

        $isNational = !empty($this->attributes['origem_brasil']) || (($this->attributes['ship_from'] ?? '') === 'BR');
        $origemLi = $isNational
            ? '<li><strong>Origem do Envio:</strong> Estoque Nacional (Brasil) 🇧🇷</li>'
            : '<li><strong>Origem do Envio:</strong> Importação Oficial com Rastreio 🌐</li>';

        // 1. Se já contém bloco de Especificações Técnicas no HTML:
        if (str_contains($desc, 'Especificações Técnicas') && preg_match('/<ul[^>]*>(.*?)<\/ul>/is', $desc, $ulMatch)) {
            $ulContent = $ulMatch[1];

            // Remove chaves de controle interno que não devem aparecer cruas para o comprador
            $cleanUl = preg_replace('/<li><strong>(?:curadoria_nota|termometro_demanda|ship_from|origem_brasil|vendas_informadas)\s*:?<\/strong>.*?<\/li>\s*/iu', '', $ulContent);

            // Se ainda não tiver Peso / Weight na lista, insere o Peso Estimado
            if (!preg_match('/(?:peso|weight)/iu', $cleanUl)) {
                $cleanUl = rtrim($cleanUl) . "\n" . $weightLi;
            }

            // Se ainda não tiver Origem do Envio na lista, insere
            if (!preg_match('/Origem do Envio/iu', $cleanUl)) {
                $cleanUl = rtrim($cleanUl) . "\n" . $origemLi;
            }

            return str_replace($ulMatch[0], '<ul style="margin:0; padding-left:20px; font-size:13px; color:#cbd5e1; line-height:1.7;">' . $cleanUl . '</ul>', $desc);
        }

        // 2. Se não contém bloco de especificações mas tem descrição
        if (!empty($desc)) {
            $specsBlock = '
    <div style="margin: 20px 0; padding: 16px; background: rgba(255,255,255,0.03); border-radius: 8px; border: 1px solid rgba(255,255,255,0.08);">
        <h4 style="margin-top:0; color:#45f3ff; font-size:14px; text-transform:uppercase;">📐 Especificações Técnicas</h4>
        <ul style="margin:0; padding-left:20px; font-size:13px; color:#cbd5e1; line-height:1.7;">
            ' . $weightLi . '
            ' . $origemLi . '
        </ul>
    </div>';
            return $desc . $specsBlock;
        }

        return $desc;
    }

    /**
     * Converte string com número e unidade de peso para gramas inteiras.
     */
    private function parseWeightStringToGrams(string $weightStr): int
    {
        $weightStr = trim(strip_tags(html_entity_decode($weightStr, ENT_QUOTES, 'UTF-8')));
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*(kg|kilos?|quilos?|g|gr|gramas?)?/iu', $weightStr, $matches)) {
            $num = (float)str_replace(',', '.', $matches[1]);
            $unit = strtolower($matches[2] ?? '');
            if (str_starts_with($unit, 'k')) {
                return (int)round($num * 1000);
            }
            if ($unit === '' && $num <= 10.0) {
                return (int)round($num * 1000);
            }
            return (int)round($num);
        }
        return 0;
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
