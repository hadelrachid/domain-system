<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Services;

use DomainSystem\Plugins\domain_drop\Contracts\AiProductEnricherInterface;
use DomainSystem\Plugins\domain_drop\Contracts\CurrencyConverterInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipCatalogRepositoryInterface;
use DomainSystem\Plugins\domain_drop\Contracts\ProductLinkScraperInterface;
use DomainSystem\Plugins\domain_drop\Contracts\ProductMediaSyncInterface;
use DomainSystem\Plugins\domain_drop\DTO\DropshipProductDto;
use RuntimeException;
use Throwable;

/**
 * Serviço de Sincronização e Integridade de Mídia (SRP / DIP).
 * 
 * Garante que produtos no catálogo tenham suas imagens (capa e galeria)
 * permanentemente alinhadas aos links oficiais de origem do fornecedor.
 */
class ProductMediaSyncService implements ProductMediaSyncInterface
{
    public function __construct(
        private readonly DropshipCatalogRepositoryInterface $repository,
        private readonly ProductLinkScraperInterface $scraper,
        private readonly AiProductEnricherInterface $aiEnricher,
        private readonly CurrencyConverterInterface $currencyConverter
    ) {
    }

    public function syncProductMedia(int $productId): DropshipProductDto
    {
        $existing = $this->repository->findById($productId);
        if ($existing === null) {
            throw new RuntimeException("Produto ID {$productId} não encontrado no catálogo.");
        }

        $sourceId = $existing->aliexpressId;
        if (empty($sourceId)) {
            throw new RuntimeException("O produto ID {$productId} não possui identificador de origem do fornecedor.");
        }

        $scraped = $this->scraper->scrape($sourceId);
        if ($scraped === null) {
            throw new RuntimeException("Não foi possível acessar a página oficial do produto {$sourceId} para sincronização.");
        }

        // Título polido a partir do título oficial raspado do link
        $polishedTitle = $this->aiEnricher->polishTitle($scraped->rawTitle, $scraped->category);
        $slug = $existing->slug ?: $this->slugify($polishedTitle . '-' . substr($sourceId, -4));

        // Conversão de preço caso o scraper tenha pego cotação atualizada
        $costUsd = $scraped->costPriceUsd > 0 ? $scraped->costPriceUsd : $existing->costPriceUsd;
        $saleBrl = $this->currencyConverter->calculateSalePrice($costUsd);

        // Galeria com todas as imagens reais raspadas
        $allImages = $scraped->getAllImages();
        $mainImage = $scraped->mainImageUrl ?: ($allImages[0] ?? $existing->mainImageUrl);

        $updatedDto = new DropshipProductDto(
            aliexpressId: $sourceId,
            title: $polishedTitle,
            slug: $slug,
            costPriceUsd: $costUsd,
            salePriceBrl: $saleBrl,
            mainImageUrl: $mainImage,
            images: $allImages,
            category: $scraped->category ?: $existing->category,
            description: $existing->description,
            rating: $scraped->rating > 0 ? $scraped->rating : $existing->rating,
            ordersCount: $scraped->ordersCount > 0 ? $scraped->ordersCount : $existing->ordersCount,
            attributes: !empty($scraped->attributes) ? $scraped->attributes : $existing->attributes,
            status: $existing->status,
            id: $existing->id
        );

        $this->repository->save($updatedDto);

        return $this->repository->findById($productId) ?? $updatedDto;
    }

    public function syncAllCatalogMedia(): array
    {
        $products = $this->repository->list([], 200, 0);
        $synced = 0;
        $failed = 0;
        $errors = [];

        foreach ($products as $prod) {
            if ($prod->id === null) {
                continue;
            }

            // Apenas sincroniza produtos com ID real numérico do fornecedor
            if (!preg_match('/^\d{10,20}$/', $prod->aliexpressId)) {
                continue;
            }

            try {
                $this->syncProductMedia((int)$prod->id);
                $synced++;
            } catch (Throwable $e) {
                $failed++;
                $errors[] = "Produto #{$prod->id} ({$prod->title}): " . $e->getMessage();
            }
        }

        return [
            'total'  => count($products),
            'synced' => $synced,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    private function slugify(string $text): string
    {
        $str = mb_strtolower(trim($text), 'UTF-8');
        $str = preg_replace(
            ['/[áàãâä]/u', '/[éèêë]/u', '/[íìîï]/u', '/[óòõôö]/u', '/[úùûü]/u', '/[ç]/u', '/[ñ]/u'],
            ['a', 'e', 'i', 'o', 'u', 'c', 'n'],
            $str
        ) ?? $str;
        $str = (string)preg_replace('/[^a-z0-9]+/i', '-', $str);

        return trim($str, '-') ?: 'produto-dropship';
    }
}
