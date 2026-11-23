<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Services;

use DomainSystem\Plugins\domain_drop\Contracts\AiProductEnricherInterface;
use DomainSystem\Plugins\domain_drop\Contracts\AliExpressApiClientInterface;
use DomainSystem\Plugins\domain_drop\Contracts\CurrencyConverterInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipCatalogRepositoryInterface;
use DomainSystem\Plugins\domain_drop\Contracts\ProductLinkScraperInterface;
use DomainSystem\Plugins\domain_drop\Contracts\ProductMediaSyncInterface;
use DomainSystem\Plugins\domain_drop\DTO\DropshipProductDto;
use DomainSystem\Plugins\domain_drop\DTO\ScrapedProductDto;
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
        private readonly CurrencyConverterInterface $currencyConverter,
        private readonly ?AliExpressApiClientInterface $apiClient = null
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

        $scraped = null;
        $availability = 'unknown';

        // 1. Prioriza o SDK oficial do AliExpress (sem scraping e sem captcha)
        if ($this->apiClient !== null) {
            try {
                $remote = $this->apiClient->getProductDetails($sourceId);
                $availability = ProductMiningService::classifyApiDetailsAvailability($remote);
                if ($availability === 'not_found') {
                    throw new RuntimeException("PAGINA_NAO_ENCONTRADA: O SDK oficial do AliExpress informou que o produto ({$sourceId}) não foi encontrado ou está offline.");
                }
                if ($availability === 'alive' && is_array($remote)) {
                    $scraped = $this->buildScrapedDtoFromSdkPayload($sourceId, $remote, $existing);
                }
            } catch (RuntimeException $e) {
                if (str_starts_with($e->getMessage(), 'PAGINA_NAO_ENCONTRADA:')) {
                    throw $e;
                }
                $errAvail = ProductMiningService::classifyApiExceptionAvailability($e);
                if ($errAvail === 'not_found') {
                    throw new RuntimeException("PAGINA_NAO_ENCONTRADA: O SDK oficial do AliExpress informou que o produto ({$sourceId}) não foi encontrado ou está offline.");
                }
                // Se o app ainda está em status Test (sem access_token OAuth), permite fallback para o scraper
                $availability = 'unknown';
            } catch (Throwable $e) {
                $errAvail = ProductMiningService::classifyApiExceptionAvailability($e);
                if ($errAvail === 'not_found') {
                    throw new RuntimeException("PAGINA_NAO_ENCONTRADA: O SDK oficial do AliExpress informou que o produto ({$sourceId}) não foi encontrado ou está offline.");
                }
                $availability = 'unknown';
            }
        }

        // 2. Fallback para scraper apenas quando o SDK não retornou dados (ex: sem credenciais)
        if ($scraped === null) {
            if ($availability === 'unknown' && method_exists($this->scraper, 'checkAvailability')) {
                $availability = $this->scraper->checkAvailability($sourceId);
                if ($availability === 'not_found') {
                    throw new RuntimeException("PAGINA_NAO_ENCONTRADA: A página do fornecedor ({$sourceId}) retornou Página Não Encontrada.");
                }
            }

            $scraped = $this->scraper->scrape($sourceId);
            if ($scraped === null) {
                if ($availability === 'unknown') {
                    throw new RuntimeException("BLOQUEIO_TEMPORARIO_FORNECEDOR: O AliExpress solicitou verificação anti-robô temporária (captcha) ou houve instabilidade de rede para o produto {$sourceId}.");
                }
                throw new RuntimeException("Não foi possível acessar a página oficial do produto {$sourceId} para sincronização.");
            }
        }

        // Título polido a partir do título oficial raspado do link
        $polishedTitle = $this->aiEnricher->polishTitle($scraped->rawTitle, $scraped->category);
        $slug = $existing->slug ?: $this->slugify($polishedTitle . '-' . substr($sourceId, -4));

        // Conversão e atualização de preço com base na oferta ativa do fornecedor (Black Friday / Promoção do Dia)
        // Regra Oficial do Lojista: 2x o produto (Custo + Custo / 100% markup sem embutir frete)
        $costBrl = (float)($scraped->attributes['cost_price_brl'] ?? 0.0);
        $costUsd = $scraped->costPriceUsd > 0 ? $scraped->costPriceUsd : $existing->costPriceUsd;

        if ($costBrl <= 0.0 && $costUsd > 0.0) {
            $rate = $this->currencyConverter->getExchangeRate('USD', 'BRL');
            $costBrl = $costUsd * ($rate > 0 ? $rate : 5.75);
        }
        if ($costBrl <= 0.0) {
            $costBrl = (float)($existing->attributes['cost_price_brl'] ?? 0.0);
        }

        $volatility = method_exists($this->currencyConverter, 'getDefaultVolatilityPercent')
            ? $this->currencyConverter->getDefaultVolatilityPercent()
            : 12.0;

        if ($costBrl > 0.0 && method_exists($this->currencyConverter, 'calculateSalePriceFromBrl')) {
            $saleBrl = $this->currencyConverter->calculateSalePriceFromBrl($costBrl, null, $volatility);
        } else {
            $saleBrl = $this->currencyConverter->calculateSalePrice($costUsd, null, null, $volatility);
        }

        // Galeria com todas as imagens reais raspadas
        $allImages = $scraped->getAllImages();
        if (empty($allImages)) {
            $allImages = $existing->images;
        } else {
            // Preserva fotos de variantes existentes se houver
            if (!empty($existing->attributes['variants']) && is_array($existing->attributes['variants'])) {
                foreach ($existing->attributes['variants'] as $v) {
                    if (is_array($v) && !empty($v['image_url']) && !in_array($v['image_url'], $allImages, true)) {
                        $allImages[] = $v['image_url'];
                    }
                }
            }
        }
        $mainImage = $scraped->mainImageUrl ?: ($allImages[0] ?? $existing->mainImageUrl);

        // Preserva atributos existentes (como variantes e avaliações)
        $mergedAttributes = array_merge($existing->attributes, $scraped->attributes);

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
            attributes: $mergedAttributes,
            status: $existing->status,
            id: $existing->id
        );

        $this->repository->save($updatedDto);

        return $this->repository->findById($productId) ?? $updatedDto;
    }

    public function syncAllCatalogMedia(bool $skipIfAlreadyMultiple = true): array
    {
        $products = $this->repository->list([], 200, 0);
        $total = count($products);
        $checked = 0;
        $skippedAlreadyMultiple = 0;
        $synced = 0;
        $deletedNotFound = 0;
        $failed = 0;
        $errors = [];

        foreach ($products as $prod) {
            if ($prod->id === null) {
                continue;
            }

            $checked++;
            $images = !empty($prod->images) ? array_values(array_filter($prod->images)) : [];

            // Mesmo que já possua mais de 1 imagem indexada, verifica via SDK oficial (e fallback) se o anúncio morreu
            if (preg_match('/^\d{10,20}$/', $prod->aliexpressId)) {
                $avail = $this->apiClient !== null
                    ? ProductMiningService::checkAvailabilityViaApiClient($this->apiClient, $prod->aliexpressId)
                    : 'unknown';
                if ($avail === 'unknown' && method_exists($this->scraper, 'checkAvailability')) {
                    $avail = $this->scraper->checkAvailability($prod->aliexpressId);
                }
                if ($avail === 'not_found') {
                    $this->repository->delete((int)$prod->id);
                    $deletedNotFound++;
                    continue;
                }
            }

            // REGRA INTELIGENTE: Se o produto já possui mais de 1 imagem, NÃO precisa re-sincronizar!
            if ($skipIfAlreadyMultiple && count($images) > 1) {
                $skippedAlreadyMultiple++;
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
            'total'                    => $total,
            'checked'                  => $checked,
            'skipped_already_multiple' => $skippedAlreadyMultiple,
            'synced'                   => $synced,
            'deleted_not_found'        => $deletedNotFound,
            'failed'                   => $failed,
            'errors'                   => $errors,
        ];
    }

    public function syncMissingGalleries(int $limit = 50): array
    {
        $products = $this->repository->list([], 200, 0);
        $totalChecked = 0;
        $neededSync = 0;
        $synced = 0;
        $failed = 0;
        $errors = [];

        foreach ($products as $prod) {
            if ($prod->id === null) {
                continue;
            }

            $totalChecked++;
            $images = !empty($prod->images) ? array_values(array_filter($prod->images)) : [];

            // Pula se já tiver mais de 1 imagem (não gasta requisição à toa)
            if (count($images) > 1) {
                continue;
            }

            if (!preg_match('/^\d{10,20}$/', $prod->aliexpressId)) {
                continue;
            }

            $neededSync++;
            if ($synced >= $limit) {
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
            'total_checked' => $totalChecked,
            'needed_sync'   => $neededSync,
            'synced'        => $synced,
            'failed'        => $failed,
            'errors'        => $errors,
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

    /**
     * Constrói um ScrapedProductDto diretamente a partir do payload oficial do SDK (`aliexpress.ds.product.get`).
     *
     * @param array<string, mixed> $remote
     */
    private function buildScrapedDtoFromSdkPayload(string $sourceId, array $remote, DropshipProductDto $existing): ?ScrapedProductDto
    {
        $root = $remote['result'] ?? $remote;
        if (!is_array($root)) {
            return null;
        }

        $baseInfo = is_array($root['ae_item_base_info_dto'] ?? null) ? $root['ae_item_base_info_dto'] : [];
        $multiMedia = is_array($root['ae_multimedia_info_dto'] ?? null) ? $root['ae_multimedia_info_dto'] : [];

        $rawTitle = trim((string)($baseInfo['subject'] ?? $existing->title));
        if ($rawTitle === '') {
            $rawTitle = $existing->title;
        }

        $images = [];
        if (!empty($multiMedia['image_urls'])) {
            $rawList = is_array($multiMedia['image_urls'])
                ? $multiMedia['image_urls']
                : explode(';', (string)$multiMedia['image_urls']);
            foreach ($rawList as $imgUrl) {
                $clean = trim((string)$imgUrl);
                if ($clean !== '' && !in_array($clean, $images, true)) {
                    $images[] = $clean;
                }
            }
        }

        $skuDtos = $root['ae_item_sku_info_dtos']['ae_item_sku_info_d_t_o'] ?? [];
        if (is_array($skuDtos)) {
            $skuList = isset($skuDtos['sku_id']) || isset($skuDtos['id']) ? [$skuDtos] : $skuDtos;
            foreach ($skuList as $sku) {
                if (!is_array($sku)) {
                    continue;
                }
                $skuImg = trim((string)($sku['sku_image_url'] ?? ''));
                if ($skuImg !== '' && !in_array($skuImg, $images, true)) {
                    $images[] = $skuImg;
                }
            }
        }

        $mainImage = $images[0] ?? $existing->mainImageUrl;
        $costUsd = (float)($baseInfo['target_sale_price'] ?? ($baseInfo['offer_sale_price'] ?? $existing->costPriceUsd));
        if ($costUsd <= 0.0 && is_array($skuDtos)) {
            $firstSku = isset($skuDtos['sku_id']) ? $skuDtos : ($skuDtos[0] ?? []);
            if (is_array($firstSku)) {
                $costUsd = (float)($firstSku['sku_price'] ?? ($firstSku['offer_sale_price'] ?? 0.0));
            }
        }

        return new ScrapedProductDto(
            sourceId: $sourceId,
            sourceUrl: "https://pt.aliexpress.com/item/{$sourceId}.html",
            rawTitle: $rawTitle,
            mainImageUrl: $mainImage,
            galleryImages: $images,
            costPriceUsd: $costUsd > 0.0 ? $costUsd : $existing->costPriceUsd,
            category: $existing->category,
            rating: (float)($baseInfo['avg_evaluation_rating'] ?? $existing->rating),
            ordersCount: (int)($baseInfo['sales_count'] ?? $existing->ordersCount),
            attributes: ['sdk_synced_at' => time()]
        );
    }
}
