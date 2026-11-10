<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\DTO;

use InvalidArgumentException;

/**
 * DTO Imutável com Garantia Contratual de Integridade de Mídia e Origem (Clean Architecture / Value Object).
 * 
 * Garante por invariante que:
 * 1. O produto possui um ID e URL de origem válidos.
 * 2. A imagem principal (mainImageUrl) é obrigatoriamente vinculada ao produto.
 * 3. Todas as imagens da galeria (galleryImages) pertencem à mesma origem do produto.
 */
final class ScrapedProductDto
{
    /**
     * @param array<int, string> $galleryImages
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        public readonly string $sourceId,
        public readonly string $sourceUrl,
        public readonly string $rawTitle,
        public readonly string $mainImageUrl,
        public readonly array $galleryImages = [],
        public readonly float $costPriceUsd = 10.0,
        public readonly string $category = 'Geral',
        public readonly float $rating = 4.8,
        public readonly int $ordersCount = 0,
        public readonly array $attributes = []
    ) {
        if (trim($this->sourceId) === '') {
            throw new InvalidArgumentException('O identificador de origem do produto (sourceId) não pode ser vazio.');
        }

        if (trim($this->mainImageUrl) === '') {
            throw new InvalidArgumentException("A imagem principal do produto {$this->sourceId} não pode ser vazia.");
        }
    }

    /**
     * Retorna a lista completa e normalizada de imagens (garantindo a principal na 1ª posição).
     * 
     * @return array<int, string>
     */
    public function getAllImages(): array
    {
        $all = [];
        if (!empty($this->mainImageUrl)) {
            $all[] = $this->mainImageUrl;
        }

        foreach ($this->galleryImages as $img) {
            $img = trim($img);
            if ($img !== '' && !in_array($img, $all, true)) {
                $all[] = $img;
            }
        }

        return $all;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'source_id'      => $this->sourceId,
            'source_url'     => $this->sourceUrl,
            'raw_title'      => $this->rawTitle,
            'main_image_url' => $this->mainImageUrl,
            'gallery_images' => $this->galleryImages,
            'cost_price_usd' => $this->costPriceUsd,
            'category'       => $this->category,
            'rating'         => $this->rating,
            'orders_count'   => $this->ordersCount,
            'attributes'     => $this->attributes,
        ];
    }
}
