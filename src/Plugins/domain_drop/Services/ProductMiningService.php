<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Services;

use DomainSystem\Plugins\domain_drop\Contracts\AiProductEnricherInterface;
use DomainSystem\Plugins\domain_drop\Contracts\AliExpressApiClientInterface;
use DomainSystem\Plugins\domain_drop\Contracts\CurrencyConverterInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipCatalogRepositoryInterface;
use DomainSystem\Plugins\domain_drop\Contracts\ProductMiningServiceInterface;
use DomainSystem\Plugins\domain_drop\DTO\DropshipProductDto;
use RuntimeException;
use Throwable;

/**
 * Orquestrador do Motor de Mineração e Enriquecimento Inteligente (SRP / DIP).
 */
class ProductMiningService implements ProductMiningServiceInterface
{
    public function __construct(
        private readonly AliExpressApiClientInterface $apiClient,
        private readonly CurrencyConverterInterface $currencyConverter,
        private readonly AiProductEnricherInterface $aiEnricher,
        private readonly DropshipCatalogRepositoryInterface $repository
    ) {
    }

    public function mineTrendingProducts(string $category = 'Eletrônicos', int $limit = 10, bool $autoPersist = true): array
    {
        $rawProducts = $this->apiClient->searchProducts([
            'keywords'  => $category,
            'page_size' => (string)min($limit, 50),
            'sort'      => 'volume_desc',
        ]);

        $mined = [];
        foreach ($rawProducts as $item) {
            try {
                $dto = $this->transformRawItemToDto($item, $category);
                if ($autoPersist) {
                    $savedId = $this->repository->save($dto);
                    $dto = $this->repository->findById($savedId) ?? $dto;
                }
                $mined[] = $dto;
            } catch (Throwable $e) {
                // Isolamento de falha individual de produto malformado
            }
        }

        return $mined;
    }

    public function importSingleProduct(string|int $productIdOrUrl): DropshipProductDto
    {
        $raw = $this->apiClient->getProductDetails($productIdOrUrl);

        $productData = $raw['result'] 
            ?? ($raw['aliexpress_ds_product_get_response']['result'] ?? []);

        if (empty($productData)) {
            throw new RuntimeException("Não foi possível carregar os detalhes do produto no AliExpress.");
        }

        $dto = $this->transformRawItemToDto($productData);
        $savedId = $this->repository->save($dto);

        return $this->repository->findById($savedId) ?? $dto;
    }

    /**
     * Transforma o payload bruto do AliExpress em um DTO limpo e precificado.
     * 
     * @param array<string, mixed> $item
     */
    private function transformRawItemToDto(array $item, string $defaultCategory = 'Geral'): DropshipProductDto
    {
        $aeId = (string)($item['product_id'] ?? ($item['item_id'] ?? uniqid('ae_')));
        $rawTitle = (string)($item['product_title'] ?? ($item['title'] ?? 'Produto Internacional'));
        $category = (string)($item['category_name'] ?? $defaultCategory);

        // 1. Higienização por I.A.
        $polishedTitle = $this->aiEnricher->polishTitle($rawTitle, $category);
        $slug = $this->slugify($polishedTitle . '-' . substr($aeId, -4));

        // 2. Extração de Imagens
        $mainImage = (string)($item['product_main_image_url'] ?? ($item['image_url'] ?? ''));
        $images = [];
        if (!empty($mainImage)) {
            $images[] = $mainImage;
        }
        if (!empty($item['product_small_image_urls']['string'])) {
            $smallUrls = (array)$item['product_small_image_urls']['string'];
            foreach ($smallUrls as $img) {
                if (!empty($img) && !in_array($img, $images, true)) {
                    $images[] = (string)$img;
                }
            }
        }

        // 3. Precificação em Dólar e Conversão em Reais
        $costPriceUsd = (float)($item['target_sale_price'] ?? ($item['sale_price'] ?? ($item['original_price'] ?? 10.0)));
        $salePriceBrl = $this->currencyConverter->calculateSalePrice($costPriceUsd);

        // 4. Métricas e Atributos
        $rating = (float)($item['evaluate_rate'] ?? 4.8);
        $ordersCount = (int)($item['volume'] ?? ($item['orders_count'] ?? 0));
        $rawDesc = (string)($item['product_description'] ?? ($item['description'] ?? ''));
        $salesCopy = $this->aiEnricher->generateSalesCopy($rawDesc, $item['properties'] ?? []);

        return new DropshipProductDto(
            aliexpressId: $aeId,
            title: $polishedTitle,
            slug: $slug,
            costPriceUsd: $costPriceUsd,
            salePriceBrl: $salePriceBrl,
            mainImageUrl: $mainImage,
            images: $images,
            category: $category,
            description: $salesCopy,
            rating: $rating > 0 ? $rating : 4.8,
            ordersCount: $ordersCount,
            attributes: $item['properties'] ?? [],
            status: 'published'
        );
    }

    private function slugify(string $text): string
    {
        $str = strtolower(trim($text));
        $str = (string)preg_replace('/[^a-z0-9]+/i', '-', $str);
        return trim($str, '-') ?: 'produto-dropship';
    }
}
