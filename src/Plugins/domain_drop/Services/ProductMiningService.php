<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Services;

use DomainSystem\Plugins\domain_drop\Contracts\AiProductEnricherInterface;
use DomainSystem\Plugins\domain_drop\Contracts\AliExpressApiClientInterface;
use DomainSystem\Plugins\domain_drop\Contracts\CurrencyConverterInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipCatalogRepositoryInterface;
use DomainSystem\Plugins\domain_drop\Contracts\ProductLinkScraperInterface;
use DomainSystem\Plugins\domain_drop\Contracts\ProductMiningServiceInterface;
use DomainSystem\Plugins\domain_drop\DTO\DropshipProductDto;
use DomainSystem\Plugins\domain_drop\DTO\ScrapedProductDto;
use RuntimeException;
use Throwable;

/**
 * Orquestrador do Motor de Mineração e Enriquecimento Inteligente (SRP / DIP).
 */
class ProductMiningService implements ProductMiningServiceInterface
{
    public const OFFICIAL_CATEGORIES = [
        'Eletrônicos & Gadget',
        'Casa Inteligente & Organização',
        'Beleza, Cuidados & Estilo',
        'Ferramentas & Utilidades',
        'Pet Shop & Pets Virais',
        'Acessórios para Veículos',
    ];

    private ProductLinkScraperInterface $linkScraper;
    private ?string $configFilePath;

    public function __construct(
        private readonly AliExpressApiClientInterface $apiClient,
        private readonly CurrencyConverterInterface $currencyConverter,
        private readonly AiProductEnricherInterface $aiEnricher,
        private readonly DropshipCatalogRepositoryInterface $repository,
        ?ProductLinkScraperInterface $linkScraper = null,
        ?string $configFilePath = null
    ) {
        $this->linkScraper = $linkScraper ?? new AliExpressLinkScraperService();
        $this->configFilePath = $configFilePath;
    }

    /**
     * Normaliza categorias legadas e apelidos para uma das 6 categorias oficiais.
     */
    public static function normalizeCategory(string $category): string
    {
        $trimmed = trim($category);
        if ($trimmed === '') {
            return 'Eletrônicos & Gadget';
        }

        $map = [
            'Eletrônicos'                    => 'Eletrônicos & Gadget',
            'Eletrônicos & Gadget'           => 'Eletrônicos & Gadget',
            'Gadgets'                        => 'Eletrônicos & Gadget',
            'Casa Inteligente'               => 'Casa Inteligente & Organização',
            'Casa Inteligente & Organização' => 'Casa Inteligente & Organização',
            'Casa & Organização'             => 'Casa Inteligente & Organização',
            'Beleza & Estilo'                => 'Beleza, Cuidados & Estilo',
            'Beleza, Cuidados & Estilo'      => 'Beleza, Cuidados & Estilo',
            'Beleza'                         => 'Beleza, Cuidados & Estilo',
            'Ferramentas'                    => 'Ferramentas & Utilidades',
            'Ferramentas & Utilidades'       => 'Ferramentas & Utilidades',
            'Utilidades'                     => 'Ferramentas & Utilidades',
            'Pet Shop'                       => 'Pet Shop & Pets Virais',
            'Pet Shop & Pets Virais'         => 'Pet Shop & Pets Virais',
            'Pets'                           => 'Pet Shop & Pets Virais',
            'Acessórios Auto'                => 'Acessórios para Veículos',
            'Acessórios para Veículos'       => 'Acessórios para Veículos',
            'Automotivo'                     => 'Acessórios para Veículos',
            'Veículos'                       => 'Acessórios para Veículos',
            'Geral'                          => 'Ferramentas & Utilidades',
        ];

        return $map[$trimmed] ?? $trimmed;
    }

    public function mineTrendingProducts(string $category = 'Eletrônicos & Gadget', int $limit = 10, bool $autoPersist = true, string $sort = 'volume_desc'): array
    {
        $category = self::normalizeCategory($category);
        $rawProducts = [];
        try {
            $rawProducts = $this->apiClient->searchProducts([
                'keywords'  => $category,
                'page_size' => (string)min($limit, 50),
                'sort'      => $sort,
            ]);
        } catch (Throwable $e) {
            $rawProducts = [];
        }

        // 1. Se a API remota oficial não estiver conectada, executa mineração em tempo real direto da busca pública do AliExpress
        if (empty($rawProducts)) {
            $liveMined = $this->mineLiveFromAliExpressSearch($category, $limit, $autoPersist);
            if (!empty($liveMined)) {
                return $liveMined;
            }
        }

        // 2. Se a busca ao vivo não responder, utiliza catálogo curado seguro
        if (empty($rawProducts)) {
            $rawProducts = $this->getCuratedTrendingFallback($category, $limit, $sort);
        }

        $mined = [];
        foreach ($rawProducts as $item) {
            try {
                $itemId = (string)($item['product_id'] ?? ($item['item_id'] ?? uniqid('ae_')));
                $dto = $this->processWithTransientBuffer(
                    $itemId,
                    $item,
                    function (array $rawItem) use ($category, $autoPersist) {
                        $transformed = $this->transformRawItemToDto($rawItem, $category);
                        if ($autoPersist) {
                            $savedId = $this->repository->save($transformed);
                            return $this->repository->findById($savedId) ?? $transformed;
                        }
                        return $transformed;
                    }
                );
                $mined[] = $dto;
            } catch (Throwable $e) {
                // Isolamento de falha individual de produto malformado
            }
        }

        return $mined;
    }

    /**
     * Minera produtos reais e ativos em tempo real através da página pública de tendências do AliExpress.
     * Garante que 100% dos produtos minerados possuam links reais, títulos oficiais e fotos autênticas.
     *
     * @return array<int, DropshipProductDto>
     */
    private function mineLiveFromAliExpressSearch(string $category, int $limit, bool $autoPersist): array
    {
        if (!$this->linkScraper->canHandle('https://pt.aliexpress.com')) {
            return [];
        }

        $category = self::normalizeCategory($category);

        $searchTerms = [
            'Eletrônicos & Gadget'           => ['smartwatch brasil', 'fone sem fio bluetooth brasil', 'projetor portatil 4k', 'mini drone 4k camera', 'power bank inducao', 'caixa som bluetooth potente', 'camera seguranca wifi 360'],
            'Casa Inteligente & Organização' => ['lampada led inteligente wifi rgb', 'tomada inteligente wifi tuya', 'sensor presenca inteligente', 'dispensador sabonete automatico sensor', 'fita led rgb inteligente wifi', 'aspirador po robo inteligente', 'fechadura digital biometrica'],
            'Beleza, Cuidados & Estilo'      => ['modelador cabelo profissional automatico', 'escova secadora modeladora eletrica', 'depilador laser luz pulsada ipl', 'massageador facial eletrico gua sha', 'maquina corte cabelo vintage profissional', 'espelho led maquiagem recarregavel'],
            'Ferramentas & Utilidades'       => ['alicate desencapador fios multifuncional', 'trena laser digital fita metrica', 'parafusadeira eletrica recarregavel sem fio', 'canivete tatico multifuncional', 'chave universal precisao multifuncoes', 'lanterna tatica led potente militar'],
            'Pet Shop & Pets Virais'         => ['coleira led luminosa cachorro recarregavel', 'bebedouro fonte automatica pet gato', 'escova a vapor pet removedora pelos', 'brinquedo interativo inteligente pet gato', 'cama nuvem calmante cachorro', 'cortador unhas pet com led'],
            'Acessórios para Veículos'       => ['suporte celular veicular carregador inducao', 'aspirador portatil automotivo sem fio', 'transmissor bluetooth veicular fm', 'compressor ar portatil eletrico pneus carro', 'led interno veicular sensor som', 'camera dvr veicular gravador transito'],
        ];

        $termList = $searchTerms[$category] ?? ['smartwatch brasil', 'fone bluetooth'];
        $searchTerm = $termList[array_rand($termList)];
        // Empurra para a vitrine e estoques do Brasil (Remessa Conforme e Envio Local)
        $searchUrl = "https://pt.aliexpress.com/w/wholesale-" . urlencode($searchTerm) . ".html?country=BR&locale=pt_BR&currency=BRL&isrefine=y";

        $ch = curl_init($searchUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cookie: aep_usuc_f=region=BR&site=bra&b_locale=pt_BR&c_tp=BRL; xman_us_f=x_locale=pt_BR; intl_locale=pt_BR;',
        ]);
        $html = curl_exec($ch);
        curl_close($ch);

        if (!$html || !is_string($html)) {
            return [];
        }

        preg_match_all('/(?:pt|www)\.aliexpress\.com\/item\/(\d{10,20})\.html/i', $html, $matches);
        $ids = array_values(array_unique($matches[1] ?? []));

        if (empty($ids)) {
            return [];
        }

        $mined = [];
        $collected = 0;

        foreach ($ids as $realId) {
            if ($collected >= $limit) {
                break;
            }

            try {
                // Filtro de Link Vivo: se for 404, indisponível ou sem imagem, descarta e busca o próximo
                $scraped = $this->linkScraper->scrape($realId);
                if ($scraped !== null && !empty($scraped->mainImageUrl)) {
                    $dto = $this->createAndSaveDtoFromScraped($scraped, $category);
                    $mined[] = $dto;
                    $collected++;
                }
            } catch (Throwable $e) {
                // Descarta silenciosamente e tenta o próximo item da lista de busca
            }
        }

        return $mined;
    }

    public function importSingleProduct(string|int $productIdOrUrl): DropshipProductDto
    {
        $inputStr = trim((string)$productIdOrUrl);

        // 1. Se for uma URL completa, tenta extrair em tempo real diretamente da página web oficial
        if (str_starts_with($inputStr, 'http://') || str_starts_with($inputStr, 'https://')) {
            $scraped = $this->linkScraper->scrape($inputStr);
            if ($scraped !== null) {
                return $this->createAndSaveDtoFromScraped($scraped);
            }
        }

        // 2. Extrai ID numérico
        $numericId = $inputStr;
        if (preg_match('/\/item\/(\d+)\.html/', $numericId, $matches)) {
            $numericId = $matches[1];
        } elseif (preg_match('/(\d{10,20})/', $numericId, $matches)) {
            $numericId = $matches[1];
        }

        // 3. Tenta via API Oficial (IOP SDK)
        $productData = null;
        try {
            $raw = $this->apiClient->getProductDetails($numericId);
            $productData = $raw['result'] 
                ?? ($raw['aliexpress_ds_product_get_response']['result'] ?? null);
        } catch (Throwable $e) {
            $productData = null;
        }

        if (!empty($productData)) {
            $dto = $this->transformRawItemToDto($productData);
            $savedId = $this->repository->save($dto);
            return $this->repository->findById($savedId) ?? $dto;
        }

        // 4. Se a API não retornou dados, tenta scraper pelo ID numérico
        $scrapedById = $this->linkScraper->scrape($numericId);
        if ($scrapedById !== null) {
            return $this->createAndSaveDtoFromScraped($scrapedById);
        }

        // 5. Se for uma URL pública e nem o scraper nem a API encontraram o produto, o anúncio não existe (404)
        if (str_starts_with($inputStr, 'http://') || str_starts_with($inputStr, 'https://')) {
            throw new RuntimeException("O link informado não foi encontrado ou está desativado no AliExpress (Erro 404). Por favor, forneça o link de um anúncio ativo.");
        }

        // 6. Fallback estruturado para testes com IDs sintéticos
        $productData = $this->createFallbackItemFromIdOrUrl($numericId, (string)$productIdOrUrl);
        $dto = $this->transformRawItemToDto($productData);
        $savedId = $this->repository->save($dto);

        return $this->repository->findById($savedId) ?? $dto;
    }

    public function importFromBrowserSource(string $rawSource, ?string $category = null): DropshipProductDto
    {
        $rawSource = trim($rawSource);
        if ($rawSource === '') {
            throw new \InvalidArgumentException("O código-fonte ou texto do produto fornecido está vazio.");
        }

        // 1. Extração estruturada via Scraper Service
        $scraped = $this->linkScraper->parseRawHtml($rawSource);
        if ($scraped === null) {
            throw new \RuntimeException("Não foi possível extrair os dados do produto do código colado. Verifique se copiou o código completo (Ctrl+U ou F12) de uma página de anúncio ativa do AliExpress.");
        }

        // 2. Processa com Buffer Transitório por Item com descarte imediato
        return $this->processWithTransientBuffer(
            $scraped->sourceId,
            [
                'source_id'  => $scraped->sourceId,
                'raw_title'  => $scraped->rawTitle,
                'main_image' => $scraped->mainImageUrl,
                'gallery'    => $scraped->galleryImages,
                'cost_usd'   => $scraped->costPriceUsd,
                'category'   => $category ?? $scraped->category,
                'attributes' => $scraped->attributes,
            ],
            function () use ($scraped, $category) {
                return $this->createAndSaveDtoFromScraped($scraped, $category);
            }
        );
    }

    /**
     * Gerencia um buffer transitório em arquivo por produto, com garantia de descarte imediato
     * e liberação de memória pelo coletor do PHP (gc_collect_cycles).
     * 
     * @param string $itemIdentifier ID do produto
     * @param array<string, mixed>|string $payload Dados brutos para armazenar temporariamente
     * @param callable $processor Função de transformação e persistência
     */
    private function processWithTransientBuffer(string $itemIdentifier, array|string $payload, callable $processor): DropshipProductDto
    {
        $tempDir = dirname(__DIR__, 4) . '/storage/cache/domain_drop_transient';
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0755, true);
        }

        $sanitizedId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $itemIdentifier) ?: uniqid('item_');
        $tempFile = $tempDir . "/temp_item_{$sanitizedId}_" . hrtime(true) . ".json";

        try {
            $content = is_array($payload) ? json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : $payload;
            file_put_contents($tempFile, $content, LOCK_EX);

            /** @var DropshipProductDto $dto */
            $dto = $processor($payload);

            return $dto;
        } finally {
            // Garante que o buffer transitório seja SEMPRE apagado imediatamente passo a passo
            if (file_exists($tempFile)) {
                @unlink($tempFile);
            }
            // Força a liberação de ciclos de memória do motor PHP
            gc_collect_cycles();
        }
    }

    private function createAndSaveDtoFromScraped(ScrapedProductDto $scraped, ?string $overrideCategory = null): DropshipProductDto
    {
        $category = $overrideCategory !== null ? self::normalizeCategory($overrideCategory) : self::normalizeCategory($scraped->category);
        $polishedTitle = $this->aiEnricher->polishTitle($scraped->rawTitle, $category);
        $slug = $this->slugify($polishedTitle . '-' . substr($scraped->sourceId, -4));
        $salePriceBrl = $this->currencyConverter->calculateSalePrice($scraped->costPriceUsd);
        $salesCopy = $this->aiEnricher->generateSalesCopy(
            "Produto importado oficial de alta durabilidade com compatibilidade e acabamento premium.",
            $scraped->attributes
        );

        $dto = new DropshipProductDto(
            aliexpressId: $scraped->sourceId,
            title: $polishedTitle,
            slug: $slug,
            costPriceUsd: $scraped->costPriceUsd,
            salePriceBrl: $salePriceBrl,
            mainImageUrl: $scraped->mainImageUrl,
            images: $scraped->getAllImages(),
            category: $category,
            description: $salesCopy,
            rating: $scraped->rating,
            ordersCount: $scraped->ordersCount,
            attributes: $scraped->attributes,
            status: 'published'
        );

        $savedId = $this->repository->save($dto);

        return $this->repository->findById($savedId) ?? $dto;
    }

    /**
     * Transforma o payload bruto do AliExpress em um DTO limpo e precificado.
     * Suporta tanto o payload oficial do IOP SDK (ae_item_base_info_dto, ae_multimedia_info_dto, ae_item_sku_info_dtos)
     * quanto feeds diretos, web scraping (imagePathList) e listas planas.
     * 
     * @param array<string, mixed> $item
     */
    private function transformRawItemToDto(array $item, string $defaultCategory = 'Eletrônicos & Gadget'): DropshipProductDto
    {
        $baseInfo = $item['ae_item_base_info_dto'] ?? [];
        $multiMedia = $item['ae_multimedia_info_dto'] ?? [];
        $skuDtos = $item['ae_item_sku_info_dtos']['ae_item_sku_info_d_t_o'] ?? ($item['ae_item_sku_info_dtos'] ?? []);

        $aeId = (string)($item['product_id'] ?? ($baseInfo['product_id'] ?? ($item['item_id'] ?? uniqid('ae_'))));
        $rawTitle = (string)($item['product_title'] ?? ($baseInfo['subject'] ?? ($item['title'] ?? 'Produto Internacional')));
        $rawCat = (string)($item['category_name'] ?? $defaultCategory);
        $category = self::normalizeCategory($rawCat);

        // 1. Higienização por I.A.
        $polishedTitle = $this->aiEnricher->polishTitle($rawTitle, $category);
        $slug = $this->slugify($polishedTitle . '-' . substr($aeId, -4));

        // 2. Extração de Imagens e Galeria Completa (Multi-Image Carousel)
        $rawMainImage = (string)($item['product_main_image_url'] ?? ($item['image_url'] ?? ''));
        $collectedImages = [];

        // 2.1 Da galeria oficial do IOP SDK (image_urls delimitado por ponto-e-vírgula ';')
        if (!empty($multiMedia['image_urls'])) {
            $splitUrls = is_array($multiMedia['image_urls']) ? $multiMedia['image_urls'] : explode(';', (string)$multiMedia['image_urls']);
            foreach ($splitUrls as $u) {
                $collectedImages[] = (string)$u;
            }
        }

        // 2.2 Da lista web de alta resolução (imagePathList)
        if (!empty($item['imagePathList']) && is_array($item['imagePathList'])) {
            foreach ($item['imagePathList'] as $u) {
                $collectedImages[] = (string)$u;
            }
        }

        // 2.3 Da chave direta images
        if (!empty($item['images']) && is_array($item['images'])) {
            foreach ($item['images'] as $u) {
                $collectedImages[] = (string)$u;
            }
        }

        // 2.4 De thumbnails oficiais da API de afiliados (product_small_image_urls)
        if (!empty($item['product_small_image_urls']['string'])) {
            $smallUrls = (array)$item['product_small_image_urls']['string'];
            foreach ($smallUrls as $u) {
                $collectedImages[] = (string)$u;
            }
        }

        // 2.5 De variantes/SKUs oficiais do AliExpress
        if (is_array($skuDtos)) {
            foreach ($skuDtos as $sku) {
                if (!empty($sku['sku_image_url'])) {
                    $collectedImages[] = (string)$sku['sku_image_url'];
                }
            }
        }

        // 2.6 Higienização, desduplicação e normalização para Full HD (remove sufixos de thumbnails _80x80.jpg)
        $images = [];
        if (!empty($rawMainImage)) {
            $cleanedMain = $this->cleanImageUrl($rawMainImage);
            $images[] = $cleanedMain;
            $mainImage = $cleanedMain;
        } else {
            $mainImage = '';
        }

        foreach ($collectedImages as $img) {
            $clean = $this->cleanImageUrl($img);
            if (!empty($clean) && !in_array($clean, $images, true)) {
                $images[] = $clean;
            }
        }

        if (empty($mainImage) && !empty($images)) {
            $mainImage = $images[0];
        }

        // 3. Precificação em Dólar e Conversão em Reais
        $costPriceUsd = (float)($item['target_sale_price'] ?? ($item['sale_price'] ?? ($item['original_price'] ?? 10.0)));
        $salePriceBrl = $this->currencyConverter->calculateSalePrice($costPriceUsd);

        // 4. Métricas e Atributos
        $rating = (float)($item['evaluate_rate'] ?? ($baseInfo['avg_evaluation_rating'] ?? 4.8));
        $ordersCount = (int)($item['volume'] ?? ($item['orders_count'] ?? 0));
        $rawDesc = (string)($item['product_description'] ?? ($baseInfo['detail'] ?? ($item['description'] ?? '')));
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

    /**
     * Limpa e padroniza a URL de imagem do AliExpress para máxima resolução.
     */
    private function cleanImageUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (str_starts_with($url, '//')) {
            $url = 'https:' . $url;
        }
        // Remove sufixos de thumbnails como _80x80.jpg, _50x50.jpg, etc.
        $url = (string)preg_replace('/_[0-9]+x[0-9]+\.(?:jpg|png|webp|jpeg)$/i', '', $url);

        return $url;
    }

    private function slugify(string $text): string
    {
        $str = mb_strtolower(trim($text), 'UTF-8');
        // Normaliza caracteres acentuados para URLs limpas (sem 'port-til' ou 'tit-nio')
        $str = preg_replace(
            ['/[áàãâä]/u', '/[éèêë]/u', '/[íìîï]/u', '/[óòõôö]/u', '/[úùûü]/u', '/[ç]/u', '/[ñ]/u'],
            ['a', 'e', 'i', 'o', 'u', 'c', 'n'],
            $str
        ) ?? $str;
        $str = (string)preg_replace('/[^a-z0-9]+/i', '-', $str);

        return trim($str, '-') ?: 'produto-dropship';
    }

    /**
     * Fallback curado de produtos virais de alta conversão para mineração inicial / testes.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getCuratedTrendingFallback(string $category, int $limit, string $sort = 'volume_desc'): array
    {
        $catalog = [
            'Eletrônicos & Gadget' => [
                [
                    'product_id' => '1005012009938049',
                    'product_title' => 'Smartwatch Ultra 2 AMOLED Pro Display 49mm GPS Caixa Titânio',
                    'category_name' => 'Eletrônicos & Gadget',
                    'target_sale_price' => '18.50',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/A6528ac8364bb4be08857064c2e00cf33v.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/A6528ac8364bb4be08857064c2e00cf33v.jpg',
                        'https://ae-pic-a1.aliexpress-media.com/kf/S7401a0230b174d39906c9426dedb874eS.jpg',
                        'https://ae-pic-a1.aliexpress-media.com/kf/S0017270e193e4e7594e0468f9960dc696.jpg',
                    ],
                    'evaluate_rate' => '4.9',
                    'volume' => 5400,
                    'product_description' => 'Relógio inteligente de última geração com monitoramento cardíaco, oxigenação sanguínea, GPS e caixa resistente em liga de titânio.',
                    'properties' => ['Resistência' => 'IP68', 'Bateria' => 'Até 7 dias', 'Bluetooth' => '5.3'],
                ],
                [
                    'product_id' => '1005005967732299',
                    'product_title' => 'Fone de Ouvido Lenovo TWS Pro Cancelamento de Ruído ANC Microfone HD',
                    'category_name' => 'Eletrônicos & Gadget',
                    'target_sale_price' => '12.80',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S19d1c7dace654de7918987ecffe76d2fh.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/S19d1c7dace654de7918987ecffe76d2fh.jpg',
                        'https://ae-pic-a1.aliexpress-media.com/kf/Saf56a139d0034b62b7e6ecac7f3f90fao.jpg',
                    ],
                    'evaluate_rate' => '4.8',
                    'volume' => 8900,
                    'product_description' => 'Fones sem fio intra-auriculares com cancelamento ativo de ruído ambiente (ANC), modo transparência e estojo de recarga rápida.',
                    'properties' => ['ANC' => '35dB', 'Bateria' => '30h com estojo', 'Conexão' => 'Bluetooth 5.3 Low Latency'],
                ],
                [
                    'product_id' => '1005005892552853',
                    'product_title' => 'Mini Projetor Smart 4K Wi-Fi 6 Android 11 Keystone Automático Portátil',
                    'category_name' => 'Eletrônicos & Gadget',
                    'target_sale_price' => '48.00',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S60c6fb8ba48d46449f6d4ec5876dced6J.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/S60c6fb8ba48d46449f6d4ec5876dced6J.jpg',
                        'https://ae-pic-a1.aliexpress-media.com/kf/Sf4f0fa896b5547c6928d62872b0a327c4.jpg',
                    ],
                    'evaluate_rate' => '4.9',
                    'volume' => 3200,
                    'product_description' => 'Projetor inteligente compacto cinema em casa, resolução nativa HD com suporte a 4K, rotação de 180 graus e Android integrado.',
                    'properties' => ['Sistema' => 'Android 11', 'Brilho' => '200 ANSI Lumens', 'Wi-Fi' => 'Dual Band 2.4/5GHz'],
                ],
                [
                    'product_id' => '1005006354452187',
                    'product_title' => 'Power Bank Magnético 10000mAh Carregamento Sem Fio 15W MagSafe Fast Charge',
                    'category_name' => 'Eletrônicos & Gadget',
                    'target_sale_price' => '14.50',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/Sf6380de1f6bf4459bdd1a5d9dc480a91T.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/Sf6380de1f6bf4459bdd1a5d9dc480a91T.jpg',
                    ],
                    'evaluate_rate' => '4.8',
                    'volume' => 4700,
                    'product_description' => 'Carregador portátil por indução magnética ultracompacto para iPhone e Android compatível com Fast Charge PD 20W.',
                    'properties' => ['Capacidade' => '10.000 mAh', 'Indução' => '15W', 'Saída USB-C' => '20W PD'],
                ],
                [
                    'product_id' => '1005013507767839',
                    'product_title' => 'Relógio Inteligente Esportivo Militar AMOLED Pro Display GPS IP68',
                    'category_name' => 'Eletrônicos & Gadget',
                    'target_sale_price' => '22.00',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S7401a0230b174d39906c9426dedb874eS.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/S7401a0230b174d39906c9426dedb874eS.jpg',
                    ],
                    'evaluate_rate' => '4.9',
                    'volume' => 6100,
                    'product_description' => 'Smartwatch esportivo ultra resistente com GPS de alta precisão, monitoramento avançado de saúde e tela AMOLED.',
                    'properties' => ['Tela' => 'AMOLED 1.43"', 'Bateria' => '450mAh até 14 dias', 'Resistência' => '5 ATM + IP68'],
                ],
                [
                    'product_id' => '1005006093867086',
                    'product_title' => 'Drone Dobrável 4K Dual Câmera Estabilização GPS Retorno Automático',
                    'category_name' => 'Eletrônicos & Gadget',
                    'target_sale_price' => '39.00',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/Sf81b4a7a3ae246c886c77f6f74fc939dn.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/Sf81b4a7a3ae246c886c77f6f74fc939dn.jpg',
                    ],
                    'evaluate_rate' => '4.8',
                    'volume' => 2800,
                    'product_description' => 'Drone de alta estabilidade com transmissão de vídeo 5G em tempo real, sensores anti-colisão e modos de voo inteligentes.',
                    'properties' => ['Câmera' => '4K Ultra HD', 'Alcance' => '1200 metros', 'Bateria' => '28 min por carga'],
                ],
                [
                    'product_id' => '1005005471465922',
                    'product_title' => 'Microfone Lapela Sem Fio Duplo USB-C Lightning Redução Ativa de Ruído',
                    'category_name' => 'Eletrônicos & Gadget',
                    'target_sale_price' => '9.80',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S387b9d2b15a542a496db933f64972442v.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/S387b9d2b15a542a496db933f64972442v.jpg',
                    ],
                    'evaluate_rate' => '4.9',
                    'volume' => 12400,
                    'product_description' => 'Kit com 2 transmissores e 1 receptor plug and play para entrevistas, podcasts e gravações com cancelamento de ruído DSP.',
                    'properties' => ['Alcance' => '20 metros', 'Bateria' => '6h de gravação contínua', 'Conector' => 'Universal Tipo C / iOS'],
                ],
                [
                    'product_id' => '1005005328221841',
                    'product_title' => 'Console Portátil Retrô R36S 10000 Jogos Clássicos Tela IPS HD 64GB',
                    'category_name' => 'Eletrônicos & Gadget',
                    'target_sale_price' => '27.00',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S307bfbf273094fb3b31950d4911aabcdZ.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/S307bfbf273094fb3b31950d4911aabcdZ.jpg',
                    ],
                    'evaluate_rate' => '4.9',
                    'volume' => 7800,
                    'product_description' => 'Videogame portátil nostálgico com emuladores de arcade, PS1, Super Nintendo e Mega Drive com saída HDMI para TV.',
                    'properties' => ['Tela' => '3.5 IPS OCA', 'Armazenamento' => '64GB Cartão TF', 'Jogos' => '+10.000 inclusos'],
                ],
                [
                    'product_id' => '1005006126601445',
                    'product_title' => 'Suporte Articulado de Mesa Alumínio CNC Carregamento Rápido Sem Fio Qi',
                    'category_name' => 'Eletrônicos & Gadget',
                    'target_sale_price' => '13.50',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/Sbfb68a625ec14695b84bdf1700855711Z.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/Sbfb68a625ec14695b84bdf1700855711Z.jpg',
                    ],
                    'evaluate_rate' => '4.8',
                    'volume' => 4500,
                    'product_description' => 'Suporte metálico em alumínio aeroespacial com inclinação e rotação 360 graus e base de carregamento wireless Qi.',
                    'properties' => ['Material' => 'Alumínio CNC', 'Potência Wireless' => '15W Fast Qi', 'Compatibilidade' => 'Universal'],
                ],
                [
                    'product_id' => '1005006245648839',
                    'product_title' => 'Fone de Ouvido TWS Pro Cancelamento Ativo de Ruído ANC Graves Profundos',
                    'category_name' => 'Eletrônicos & Gadget',
                    'target_sale_price' => '11.00',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/Saf56a139d0034b62b7e6ecac7f3f90fao.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/Saf56a139d0034b62b7e6ecac7f3f90fao.jpg',
                    ],
                    'evaluate_rate' => '4.8',
                    'volume' => 9300,
                    'product_description' => 'Fones estéreo com som espacial imersivo, estojo digital com indicador LED e pareamento automático instantâneo.',
                    'properties' => ['Autonomia' => '24h totais', 'Driver' => '13mm Titânio', 'Proteção' => 'IPX5'],
                ],
            ],
            'Casa Inteligente & Organização' => [
                [
                    'product_id' => '1005011740614978',
                    'product_title' => 'Fita de LED COB Tuya Wi-Fi RGBIC Inteligente Sincronização Musical Alexa',
                    'category_name' => 'Casa Inteligente & Organização',
                    'target_sale_price' => '14.50',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S33b97dc904274cb18329b6fc528ef26eM.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/S33b97dc904274cb18329b6fc528ef26eM.jpg',
                    ],
                    'evaluate_rate' => '4.9',
                    'volume' => 8200,
                    'product_description' => 'Fita de iluminação contínua sem pontos visíveis, compatível com Alexa e Google Home com modos de iluminação dinâmicos.',
                    'properties' => ['Tecnologia' => 'COB RGBIC', 'App' => 'Tuya / Smart Life', 'Comprimento' => '5 metros'],
                ],
                [
                    'product_id' => '1005012801107696',
                    'product_title' => 'Barra de Luz RGBIC para Monitor Gamer Luzes LED Sensor Anti-Reflexo',
                    'category_name' => 'Casa Inteligente & Organização',
                    'target_sale_price' => '19.80',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/Sbfb68a625ec14695b84bdf1700855711Z.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/Sbfb68a625ec14695b84bdf1700855711Z.jpg',
                    ],
                    'evaluate_rate' => '4.9',
                    'volume' => 6400,
                    'product_description' => 'Barra de iluminação assimétrica para topo de monitores eliminando reflexos na tela e fadiga visual com backlight RGBIC gamer.',
                    'properties' => ['Fixação' => 'Contrapeso Magnético Universal', 'Controle' => 'Touch e Wireless', 'Iluminação' => 'Dupla Face Frontal + RGB'],
                ],
                [
                    'product_id' => '1005012941154280',
                    'product_title' => 'Luminária Inteligente RGBIC Barra de Luz Ambiente Wi-Fi + Bluetooth',
                    'category_name' => 'Casa Inteligente & Organização',
                    'target_sale_price' => '16.00',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/Sf4f0fa896b5547c6928d62872b0a327c4.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/Sf4f0fa896b5547c6928d62872b0a327c4.jpg',
                    ],
                    'evaluate_rate' => '4.8',
                    'volume' => 5100,
                    'product_description' => 'Torres de iluminação ambiente decorativa para mesa de setup, home theater e quarto com mais de 16 milhões de tonalidades.',
                    'properties' => ['Sincronização' => 'Microfone Embutido Reativo', 'Conexão' => 'Wi-Fi 2.4GHz + BLE', 'Efeitos' => '+40 Modos Pré-programados'],
                ],
                [
                    'product_id' => '1005013169928626',
                    'product_title' => 'Fita LED COB Endereçável Ultra Brilhante Alta Densidade Sem Pontos',
                    'category_name' => 'Casa Inteligente & Organização',
                    'target_sale_price' => '11.20',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S60c6fb8ba48d46449f6d4ec5876dced6J.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/S60c6fb8ba48d46449f6d4ec5876dced6J.jpg',
                    ],
                    'evaluate_rate' => '4.8',
                    'volume' => 9700,
                    'product_description' => 'Fita flexível de iluminação linear uniforme para sancas, móveis planejados e nichos com alto índice de reprodução de cor (CRI > 90).',
                    'properties' => ['Densidade' => '480 LEDs/metro', 'Alimentação' => '12V/24V DC', 'CRI' => '> 90 Ra'],
                ],
                [
                    'product_id' => '1005013373014575',
                    'product_title' => 'Fita LED RGB Inteligente 5M Bluetooth Controle Remoto e Aplicativo',
                    'category_name' => 'Casa Inteligente & Organização',
                    'target_sale_price' => '8.90',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S1a9c692010184b1ebed83b52c480c1c3H.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/S1a9c692010184b1ebed83b52c480c1c3H.jpg',
                    ],
                    'evaluate_rate' => '4.8',
                    'volume' => 14300,
                    'product_description' => 'Kit completo pronto para ligar com fita autoadesiva 3M, controlador Bluetooth e controle remoto infravermelho.',
                    'properties' => ['Comprimento' => '5 metros', 'Controle' => 'App iOS/Android + Controle IR', 'Alimentação' => 'USB 5V'],
                ],
                [
                    'product_id' => '1005007987820323',
                    'product_title' => 'Tuya Bluetooth Inteligente Lâmpada de Teto Plafon Led Dimerizável',
                    'category_name' => 'Casa Inteligente & Organização',
                    'target_sale_price' => '21.00',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/Sb19d7bfdcc5942e89453520e9b7f14290.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/Sb19d7bfdcc5942e89453520e9b7f14290.jpg',
                    ],
                    'evaluate_rate' => '4.9',
                    'volume' => 4300,
                    'product_description' => 'Plafon inteligente ultrafino com ajuste de temperatura de luz de 3000K a 6500K e dimmer suave pelo celular.',
                    'properties' => ['Potência' => '24W (2200 Lumens)', 'Temperatura' => 'Branco Quente ao Frio', 'Design' => 'Ultrafino 2cm'],
                ],
                [
                    'product_id' => '1005007225440926',
                    'product_title' => 'Luzes LED Slim para Armário e Guarda-Roupa Sensor Movimento Noturno',
                    'category_name' => 'Casa Inteligente & Organização',
                    'target_sale_price' => '7.80',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S6cfc0b4d7ced4ac28db51a9bdd1924cdZ.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/S6cfc0b4d7ced4ac28db51a9bdd1924cdZ.jpg',
                    ],
                    'evaluate_rate' => '4.9',
                    'volume' => 11800,
                    'product_description' => 'Régua de luz magnética recarregável com sensor de presença PIR que acende automaticamente no escuro.',
                    'properties' => ['Fixação' => 'Magnética Sem Furar', 'Bateria' => 'Recarregável USB-C', 'Sensor' => 'Infravermelho 3 metros'],
                ],
                [
                    'product_id' => '1005006845770643',
                    'product_title' => 'Lâmpada LED RGBIC USB com App e Controle de Voz Automação Smart',
                    'category_name' => 'Casa Inteligente & Organização',
                    'target_sale_price' => '9.50',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S9a4fee8edc064a339de1a7fe4251795cE.png',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/S9a4fee8edc064a339de1a7fe4251795cE.png',
                    ],
                    'evaluate_rate' => '4.8',
                    'volume' => 7600,
                    'product_description' => 'Lâmpada inteligente USB compacta com sensor rítmico para cabeceiras, estantes e automação residencial acessível.',
                    'properties' => ['Alimentação' => 'USB 5V', 'Controle' => 'App Smart', 'Cores' => 'RGBIC Multicolorido'],
                ],
            ],
            'Beleza, Cuidados & Estilo' => [
                [
                    'product_id' => '1005013338758151',
                    'product_title' => 'Sencicimen X9 Modelador de Cabelo Multifuncional Secador e Escova Oval',
                    'category_name' => 'Beleza, Cuidados & Estilo',
                    'target_sale_price' => '24.90',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/Saf56a139d0034b62b7e6ecac7f3f90fao.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/Saf56a139d0034b62b7e6ecac7f3f90fao.jpg',
                    ],
                    'evaluate_rate' => '4.9',
                    'volume' => 7800,
                    'product_description' => 'Escova modeladora com revestimento cerâmico e gerador de íons negativos para alisamento e modelagem rápida sem queimar os fios.',
                    'properties' => ['Potência' => '1200W', 'Revestimento' => 'Cerâmica + Turmalina', 'Voltagem' => 'Bivolt'],
                ],
                [
                    'product_id' => '1005013151107606',
                    'product_title' => 'Escova de Cabelo Profissional 6 em 1 Cerâmica Íons Anti-Frizz',
                    'category_name' => 'Beleza, Cuidados & Estilo',
                    'target_sale_price' => '21.50',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S5eb27bac07644281ae067878486aa4aco.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/S5eb27bac07644281ae067878486aa4aco.jpg',
                    ],
                    'evaluate_rate' => '4.8',
                    'volume' => 9200,
                    'product_description' => 'Kit completo de modelagem e secagem com cabeçotes intercambiáveis para cachos, ondas, volume e alisamento.',
                    'properties' => ['Acessórios' => '6 pontas magnéticas', 'Velocidades' => '3 Ajustes de Fluxo', 'Cabo' => 'Giratório 360°'],
                ],
                [
                    'product_id' => '1005006117385892',
                    'product_title' => 'Secador de Cabelo 5 em 1 Escova de Ar Quente Cachos Automáticos',
                    'category_name' => 'Beleza, Cuidados & Estilo',
                    'target_sale_price' => '19.80',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/Sb6bfd21fab234736823f31f3971f17bfd.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/Sb6bfd21fab234736823f31f3971f17bfd.jpg',
                    ],
                    'evaluate_rate' => '4.8',
                    'volume' => 11400,
                    'product_description' => 'Sistema de ar quente com efeito aerodinâmico que envolve e modela os cachos automaticamente sem danificar a estrutura capilar.',
                    'properties' => ['Efeito' => 'Coanda Air Flow', 'Proteção Térmica' => 'Controle inteligente de temperatura', 'Motor' => '110.000 RPM'],
                ],
                [
                    'product_id' => '1005011825737716',
                    'product_title' => 'Escova Alisadora 3 em 1 Elétrica Aquecimento Rápido Cerâmica Tourmaline',
                    'category_name' => 'Beleza, Cuidados & Estilo',
                    'target_sale_price' => '13.50',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/Scd99cec3930c45a2b17e1bf4d8146611J.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/Scd99cec3930c45a2b17e1bf4d8146611J.jpg',
                    ],
                    'evaluate_rate' => '4.9',
                    'volume' => 16300,
                    'product_description' => 'Pente alisador térmico com dentes cerâmicos anti-escaldamento que alisa mechas volumosas em uma única passada.',
                    'properties' => ['Aquecimento' => 'PTC Ultra Rápido 30s', 'Temperatura' => '130°C - 200°C', 'Segurança' => 'Desligamento automático'],
                ],
                [
                    'product_id' => '1005007216933299',
                    'product_title' => 'Secador de Cabelo Profissional Motor Brushless Vento Forte Ultra Silencioso',
                    'category_name' => 'Beleza, Cuidados & Estilo',
                    'target_sale_price' => '32.00',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S642e887865c347f0bc7dab19c9ec4ac58.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/S642e887865c347f0bc7dab19c9ec4ac58.jpg',
                    ],
                    'evaluate_rate' => '4.9',
                    'volume' => 5800,
                    'product_description' => 'Secador supersônico de alta potência com emissão de 200 milhões de íons negativos, secagem 3x mais rápida e bico magnético.',
                    'properties' => ['Motor' => 'Brushless 110.000 RPM', 'Ruído' => '< 59dB Silencioso', 'Íons Negativos' => '200M/cm³'],
                ],
                [
                    'product_id' => '1005007864147814',
                    'product_title' => 'Modelador de Cabelo Automático Auto Curling Sem Fio Display LED',
                    'category_name' => 'Beleza, Cuidados & Estilo',
                    'target_sale_price' => '17.20',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S19d1c7dace654de7918987ecffe76d2fh.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/S19d1c7dace654de7918987ecffe76d2fh.jpg',
                    ],
                    'evaluate_rate' => '4.8',
                    'volume' => 8400,
                    'product_description' => 'Modelador giratório recarregável com câmara isolada anti-queimadura, rotação bidirecional e timer acústico.',
                    'properties' => ['Autonomia' => '60 min bateria lítio', 'Direção' => 'Esquerda / Direita', 'Display' => 'LCD Digital'],
                ],
                [
                    'product_id' => '1005013540535377',
                    'product_title' => 'Escova de Ar 6 em 1 Secador Iônico Modelador e Volumizador Salão',
                    'category_name' => 'Beleza, Cuidados & Estilo',
                    'target_sale_price' => '22.80',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S6ec6c3e1b66e4751a5f526f5f9a58381Y.png',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/S6ec6c3e1b66e4751a5f526f5f9a58381Y.png',
                    ],
                    'evaluate_rate' => '4.8',
                    'volume' => 6700,
                    'product_description' => 'Escova multifuncional ergonômica com corpo leve e 6 pontas intercambiáveis para acabamento brilhante sedoso.',
                    'properties' => ['Potência' => '1000W', 'Tecnologia' => 'Íon Negativo Anti-Frizz', 'Peso' => 'Leve 480g'],
                ],
                [
                    'product_id' => '1005013436211622',
                    'product_title' => 'Secador e Modelador Multifuncional Bivolt com 6 Acessórios Troca Rápida',
                    'category_name' => 'Beleza, Cuidados & Estilo',
                    'target_sale_price' => '20.50',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S6fe7898bd4b64faeb75b0840f5ee2e9d4.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/S6fe7898bd4b64faeb75b0840f5ee2e9d4.jpg',
                    ],
                    'evaluate_rate' => '4.8',
                    'volume' => 7100,
                    'product_description' => 'Styler completo de beleza para penteados práticos no dia a dia com difusor, concentrador e rolos modeladores.',
                    'properties' => ['Níveis de Calor' => 'Frio / Morno / Quente', 'Voltagem' => 'Bivolt Automático', 'Filtro' => 'Traseiro Removível'],
                ],
            ],
            'Ferramentas & Utilidades' => [
                [
                    'product_id' => '1005006678170353',
                    'product_title' => 'Alicate Multifuncional Para Desencapar Fios Cortador e Crimpador Alta Dureza',
                    'category_name' => 'Ferramentas & Utilidades',
                    'target_sale_price' => '8.37',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S387b9d2b15a542a496db933f64972442v.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/S387b9d2b15a542a496db933f64972442v.jpg',
                    ],
                    'evaluate_rate' => '4.9',
                    'volume' => 19300,
                    'product_description' => 'Alicate profissional de precisão para eletricistas com decapagem rápida de fios AWG 24-10 e corpo reforçado em aço forjado.',
                    'properties' => ['Capacidade' => 'AWG 24-10 (0.2 - 6.0mm²)', 'Aço' => 'Aço Carbono Forjado', 'Empunhadura' => 'Ergonômica Antiderrapante'],
                ],
                [
                    'product_id' => '1005006727505916',
                    'product_title' => 'Kit Chaves de Fenda Magnética Precisão Cruz Flat Reparo Celular e Óculos',
                    'category_name' => 'Ferramentas & Utilidades',
                    'target_sale_price' => '7.50',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S2199483548c0452db85058b24c31462aC.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/S2199483548c0452db85058b24c31462aC.jpg',
                    ],
                    'evaluate_rate' => '4.8',
                    'volume' => 14200,
                    'product_description' => 'Estojo compacto de bits magnéticos em liga S2 endurecida para manutenção de smartphones, relógios, notebooks e drones.',
                    'properties' => ['Material' => 'Aço S2 60 HRC', 'Estojo' => 'Slide Pop-Up Magnético', 'Ponteiras' => '24 Bits de Precisão'],
                ],
                [
                    'product_id' => '1005009229236285',
                    'product_title' => 'Conjunto de Pinças de Aço Inoxidável Antiestática ESD Alta Precisão',
                    'category_name' => 'Ferramentas & Utilidades',
                    'target_sale_price' => '5.20',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S6e6b18ae6d494b3889634c90e45efb18Q.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/S6e6b18ae6d494b3889634c90e45efb18Q.jpg',
                    ],
                    'evaluate_rate' => '4.9',
                    'volume' => 16500,
                    'product_description' => 'Pinças profissionais de precisão cirúrgica com tratamento antiestático ESD para soldagem de componentes SMD e eletrônica fina.',
                    'properties' => ['Material' => 'Aço Inox Não Magnético', 'Revestimento' => 'ESD Condutivo', 'Modelos' => 'Pontas Reta, Curva e Chata'],
                ],
                [
                    'product_id' => '1005013286445276',
                    'product_title' => 'Conjunto de Chaves de Fenda com Catraca Reversível Bits Aço Cromo Vanádio',
                    'category_name' => 'Ferramentas & Utilidades',
                    'target_sale_price' => '12.00',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S307bfbf273094fb3b31950d4911aabcdZ.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/S307bfbf273094fb3b31950d4911aabcdZ.jpg',
                    ],
                    'evaluate_rate' => '4.9',
                    'volume' => 8800,
                    'product_description' => 'Chave catracada com ajuste de 3 posições (aperto, soltura e trava) com cabo emborrachado e extensor flexível.',
                    'properties' => ['Catraca' => 'Mecanismo 72 Dentes', 'Aço' => 'Cr-V Cromo Vanádio', 'Extensor' => 'Eixo Flexível Incluso'],
                ],
                [
                    'product_id' => '1005012487522034',
                    'product_title' => 'Chave de Fenda Elétrica Portátil Magnética Recarregável USB-C Torque Ajustável',
                    'category_name' => 'Ferramentas & Utilidades',
                    'target_sale_price' => '17.50',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S018988f1a8624e768e8d4feb5d4278a6J.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/S018988f1a8624e768e8d4feb5d4278a6J.jpg',
                    ],
                    'evaluate_rate' => '4.9',
                    'volume' => 11200,
                    'product_description' => 'Mini parafusadeira elétrica com luz LED frontal de 360°, bateria recarregável USB-C e torque de precisão para eletrônicos.',
                    'properties' => ['Torque' => '0.25 - 0.35 N.m', 'Bateria' => 'Lítio 350mAh', 'Velocidade' => '200 RPM'],
                ],
                [
                    'product_id' => '1005012900019466',
                    'product_title' => 'Kit de Chaves de Precisão Profissional para Manutenção Eletrônica e Notebook',
                    'category_name' => 'Ferramentas & Utilidades',
                    'target_sale_price' => '9.80',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S6efbecc998aa4d4b85b6b8891d2e136a2.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/S6efbecc998aa4d4b85b6b8891d2e136a2.jpg',
                    ],
                    'evaluate_rate' => '4.8',
                    'volume' => 7600,
                    'product_description' => 'Estojo com pontas Torx, Philips, Fenda, Tri-Wing e Pentalobe com magnetizador e haste extensora.',
                    'properties' => ['Quantidade' => '32 em 1', 'Magnetizador' => 'Integrado', 'Aplicação' => 'PC, Console e Gadgets'],
                ],
                [
                    'product_id' => '1005012658095432',
                    'product_title' => 'Jogo de Ferramentas Manuais Multifuncional com Maleta Rígida Reforçada',
                    'category_name' => 'Ferramentas & Utilidades',
                    'target_sale_price' => '24.00',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S72e9f4a8e01d492386903384324497f1T.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/S72e9f4a8e01d492386903384324497f1T.jpg',
                    ],
                    'evaluate_rate' => '4.9',
                    'volume' => 5300,
                    'product_description' => 'Maleta essencial para reparos domésticos rápidos contendo martelo, alicate, trena métrica, estilete e chaves.',
                    'properties' => ['Maleta' => 'Polímero Rígido Antichoque', 'Itens' => 'Peças Forjadas Anticorrosão', 'Garantia' => 'Uso Profissional'],
                ],
            ],
            'Pet Shop & Pets Virais' => [
                [
                    'product_id' => '1005006743928190',
                    'product_title' => 'Coleira LED Luminosa Noturna Recarregável para Cães À Prova D Água',
                    'category_name' => 'Pet Shop & Pets Virais',
                    'target_sale_price' => '6.50',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S19d1c7dace654de7918987ecffe76d2fh.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/S19d1c7dace654de7918987ecffe76d2fh.jpg',
                    ],
                    'evaluate_rate' => '4.9',
                    'volume' => 22000,
                    'product_description' => 'Coleira refletiva com fibra óptica ultrabrilhante para passeios noturnos seguros com 3 modos de pisca e recarga rápida USB.',
                    'properties' => ['Visibilidade' => 'Até 500 metros', 'Autonomia' => '8 horas', 'Ajuste' => 'Tamanhos P/M/G'],
                ],
                [
                    'product_id' => '1005006201948301',
                    'product_title' => 'Fonte Bebedouro Automática Silenciosa com Filtro de Carvão Gatos e Cães',
                    'category_name' => 'Pet Shop & Pets Virais',
                    'target_sale_price' => '12.50',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/Sb19d7bfdcc5942e89453520e9b7f14290.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/Sb19d7bfdcc5942e89453520e9b7f14290.jpg',
                    ],
                    'evaluate_rate' => '4.9',
                    'volume' => 16400,
                    'product_description' => 'Água corrente rica em oxigênio com filtragem tripla que incentiva os pets a beberem mais água prevenindo problemas renais.',
                    'properties' => ['Capacidade' => '2.5 Litros', 'Bomba' => 'Silenciosa < 20dB', 'Filtro' => 'Carvão Ativado Quádruplo'],
                ],
                [
                    'product_id' => '1005005839201948',
                    'product_title' => 'Escova a Vapor Removedora de Pelos Soltos Pet com Spray Hidratante Suave',
                    'category_name' => 'Pet Shop & Pets Virais',
                    'target_sale_price' => '7.80',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/Saf56a139d0034b62b7e6ecac7f3f90fao.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/Saf56a139d0034b62b7e6ecac7f3f90fao.jpg',
                    ],
                    'evaluate_rate' => '4.8',
                    'volume' => 28500,
                    'product_description' => 'Escova viral 3 em 1 de massagem que remove até 95% dos pelos soltos sem estresse com micro-névoa quente aromática.',
                    'properties' => ['Cerdas' => 'Silicone Macio Arredondado', 'Função' => 'Vapor Fino + Desembaraço', 'Bateria' => 'Recarregável'],
                ],
                [
                    'product_id' => '1005006392019483',
                    'product_title' => 'Bolinha Inteligente Interativa Rolante Automática para Gatos e Cães',
                    'category_name' => 'Pet Shop & Pets Virais',
                    'target_sale_price' => '6.80',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S018988f1a8624e768e8d4feb5d4278a6J.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/S018988f1a8624e768e8d4feb5d4278a6J.jpg',
                    ],
                    'evaluate_rate' => '4.8',
                    'volume' => 19700,
                    'product_description' => 'Brinquedo motorizado inteligente com sensor de desvio de obstáculos e luz suave que mantém o pet ativo e entretido.',
                    'properties' => ['Material' => 'Silicone Resistente a Mordidas', 'Modos' => 'Normal e Inteligente', 'Recarga' => 'USB-C Rápida'],
                ],
            ],
            'Acessórios para Veículos' => [
                [
                    'product_id' => '1005006392019284',
                    'product_title' => 'Suporte Celular Veicular Carregador por Indução 15W Sensor Automático 360°',
                    'category_name' => 'Acessórios para Veículos',
                    'target_sale_price' => '10.50',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/Sbfb68a625ec14695b84bdf1700855711Z.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/Sbfb68a625ec14695b84bdf1700855711Z.jpg',
                    ],
                    'evaluate_rate' => '4.9',
                    'volume' => 24100,
                    'product_description' => 'Braços inteligentes automáticos com sensor infravermelho de toque e trava eletromagnética segura contra buracos e curvas.',
                    'properties' => ['Potência' => '15W Fast Wireless Qi', 'Fixação' => 'Grade de Ar e Painel 360°', 'Sensor' => 'Touch Capacitivo'],
                ],
                [
                    'product_id' => '1005006839201948',
                    'product_title' => 'Aspirador Automotivo Portátil Sem Fio 120W Alta Potência de Sucção Bateria',
                    'category_name' => 'Acessórios para Veículos',
                    'target_sale_price' => '12.80',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S387b9d2b15a542a496db933f64972442v.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/S387b9d2b15a542a496db933f64972442v.jpg',
                    ],
                    'evaluate_rate' => '4.8',
                    'volume' => 13400,
                    'product_description' => 'Aspirador compacto e potente para limpeza de carpetes, frestas de bancos e porta-malas com bicos especiais e filtro lavável.',
                    'properties' => ['Potência' => '120W Real', 'Sucção' => '8.000 Pa', 'Acessórios' => '3 Bicos Diferentes'],
                ],
                [
                    'product_id' => '1005006192849301',
                    'product_title' => 'Compressor de Ar Portátil Digital para Pneus 150 PSI Parada Automática',
                    'category_name' => 'Acessórios para Veículos',
                    'target_sale_price' => '18.90',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S018988f1a8624e768e8d4feb5d4278a6J.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/S018988f1a8624e768e8d4feb5d4278a6J.jpg',
                    ],
                    'evaluate_rate' => '4.9',
                    'volume' => 17800,
                    'product_description' => 'Calibrador elétrico inteligente com parada automática na pressão pré-definida, tela digital LCD e lanterna LED integrada.',
                    'properties' => ['Pressão Máxima' => '150 PSI', 'Bateria' => '4000mAh Recarregável', 'Bicos' => 'Carro, Moto, Bicicleta e Bolas'],
                ],
                [
                    'product_id' => '1005006592840192',
                    'product_title' => 'Transmissor Veicular Bluetooth FM QC 3.0 Carga Rápida Dupla e Viva-Voz',
                    'category_name' => 'Acessórios para Veículos',
                    'target_sale_price' => '5.90',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/Sf6380de1f6bf4459bdd1a5d9dc480a91T.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/Sf6380de1f6bf4459bdd1a5d9dc480a91T.jpg',
                    ],
                    'evaluate_rate' => '4.8',
                    'volume' => 31000,
                    'product_description' => 'Conecta o celular ao som do carro instantaneamente com graves reforçados BASS, luz LED RGB ambiente e carregamento rápido.',
                    'properties' => ['Portas' => 'USB QC 3.0 + Type-C PD 20W', 'Bluetooth' => '5.0 Sem Ruído', 'Microfone' => 'Cancelamento de Eco'],
                ],
            ],
        ];

        // Mapeia apelidos para a categoria oficial do pool
        $normalizedCat = self::normalizeCategory($category);
        $pool = $catalog[$normalizedCat] ?? ($catalog[$category] ?? $catalog['Eletrônicos & Gadget'] ?? $catalog['Eletrônicos']);

        // Se o limite solicitado for maior que o pool da categoria, completa com itens de outras categorias
        if (count($pool) < $limit) {
            foreach ($catalog as $catName => $items) {
                if ($catName !== $category) {
                    foreach ($items as $extraItem) {
                        $pool[] = $extraItem;
                        if (count($pool) >= $limit) {
                            break 2;
                        }
                    }
                }
            }
        }

        // Ordena pelo critério de reputação solicitado: maior volume de vendas ou maior avaliação por estrelas
        if ($sort === 'evaluate_rate_desc') {
            usort($pool, fn($a, $b) => ((float)($b['evaluate_rate'] ?? 0)) <=> ((float)($a['evaluate_rate'] ?? 0)));
        } else {
            usort($pool, fn($a, $b) => ((int)($b['volume'] ?? 0)) <=> ((int)($a['volume'] ?? 0)));
        }

        $slice = array_slice($pool, 0, $limit);
        foreach ($slice as &$p) {
            $p['category_name'] = $normalizedCat;
        }
        unset($p);

        return $slice;
    }

    /**
     * Gera estrutura de dados de fallback inteligente para um ID ou URL informada.
     * Tenta primeiro extrair dados reais (título, galeria HD imagePathList e preço) diretamente da página web.
     *
     * @return array<string, mixed>
     */
    private function createFallbackItemFromIdOrUrl(string $productId, string $url): array
    {
        $liveWebData = $this->fetchProductDataFromWeb($productId, $url);
        if ($liveWebData !== null) {
            return $liveWebData;
        }

        $title = 'Produto Importado AliExpress ' . $productId;
        $category = 'Geral';

        // Tenta inferir o título a partir da URL
        if (preg_match('/item\/\d+-([^.]+)\.html/i', $url, $m)) {
            $words = explode('-', $m[1]);
            $title = ucwords(implode(' ', array_filter($words)));
            if (stripos($url, 'watch') !== false || stripos($url, 'relogio') !== false) {
                $category = 'Eletrônicos';
            } elseif (stripos($url, 'fone') !== false || stripos($url, 'earphone') !== false) {
                $category = 'Eletrônicos';
            } elseif (stripos($url, 'lamp') !== false || stripos($url, 'luz') !== false) {
                $category = 'Casa Inteligente';
            }
        }

        return [
            'product_id' => $productId,
            'product_title' => $title,
            'category_name' => $category,
            'target_sale_price' => '14.00',
            'product_main_image_url' => 'https://images.unsplash.com/photo-1526738549149-8e07eca6c147?w=600&auto=format&fit=crop&q=80',
            'evaluate_rate' => '4.8',
            'volume' => 2400,
            'product_description' => "Produto de alta qualidade importado diretamente do fabricante com envio rastreado.",
            'properties' => ['Garantia' => 'Internacional', 'Envio' => 'Rastreio Oficial'],
        ];
    }

    /**
     * Extrai dados em tempo real diretamente do HTML da página pública do AliExpress.
     * Captura título limpo, imagem de capa, galeria Full HD (imagePathList) e precificação via pdp_npi.
     *
     * @return array<string, mixed>|null
     */
    private function fetchProductDataFromWeb(string $productId, string $url): ?array
    {
        $targetUrl = str_starts_with($url, 'http') ? $url : "https://pt.aliexpress.com/item/{$productId}.html";

        $ch = curl_init($targetUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $html = curl_exec($ch);
        curl_close($ch);

        if (!$html || !is_string($html)) {
            return null;
        }

        // Descarta se for página de desafio anti-bot ou link quebrado
        if (stripos($html, 'Arraste o controle deslizante') !== false
            || stripos($html, 'sec.aliexpress.com') !== false
            || stripos($html, 'punish') !== false
            || stripos($html, 'baxia') !== false
            || stripos($html, 'Página não encontrada') !== false
        ) {
            return null;
        }

        $title = '';
        if (preg_match('/<meta property="og:title" content="([^"]+)"/i', $html, $m)) {
            $title = html_entity_decode(preg_replace('/\s*-\s*AliExpress.*$/i', '', $m[1]));
        }

        $mainImage = '';
        if (preg_match('/<meta property="og:image" content="([^"]+)"/i', $html, $m)) {
            $mainImage = $m[1];
        }

        $images = [];
        if (preg_match('/"imagePathList":(\[[^\]]+\])/', $html, $m)) {
            $decoded = json_decode($m[1], true);
            if (is_array($decoded)) {
                $images = $decoded;
            }
        }

        if (empty($title) && empty($mainImage) && empty($images)) {
            return null;
        }

        // Tenta inferir preço da query string (pdp_npi) ou HTML
        $costPriceUsd = 12.00;
        $decodedUrl = urldecode($targetUrl . ' ' . $html);
        if (preg_match('/dis!([A-Z]{3})![0-9.]+!([0-9.]+)/i', $decodedUrl, $pm)) {
            $currency = strtoupper($pm[1]);
            $promoVal = (float)$pm[2];
            if ($currency === 'BRL') {
                $costPriceUsd = round($promoVal / 5.25, 2);
            } elseif ($currency === 'USD') {
                $costPriceUsd = $promoVal;
            }
        }

        // Categoria inteligente
        $category = 'Eletrônicos';
        if (stripos($title, 'pulseira') !== false || stripos($title, 'watch') !== false || stripos($title, 'relogio') !== false) {
            $category = 'Eletrônicos';
        } elseif (stripos($title, 'beleza') !== false || stripos($title, 'cabelo') !== false || stripos($title, 'pele') !== false) {
            $category = 'Beleza & Estilo';
        } elseif (stripos($title, 'luminaria') !== false || stripos($title, 'casa') !== false || stripos($title, 'quarto') !== false) {
            $category = 'Casa Inteligente';
        }

        return [
            'product_id' => $productId,
            'product_title' => $title ?: ('Produto AliExpress ' . $productId),
            'category_name' => $category,
            'target_sale_price' => (string)$costPriceUsd,
            'product_main_image_url' => $mainImage,
            'images' => $images,
            'imagePathList' => $images,
            'evaluate_rate' => '4.8',
            'volume' => 1350,
            'product_description' => "Produto importado oficial de alta durabilidade com compatibilidade e acabamento premium.",
            'properties' => [
                'Origem' => 'Importação Oficial',
                'Garantia' => 'Garantia do Fabricante',
                'Envio' => 'Rastreamento Internacional Expresso',
            ],
        ];
    }

    /**
     * Executa o ciclo de rotação diária automatizada de 24 horas.
     * Minera produtos virais aleatórios para todas as 6 categorias oficiais,
     * atualiza o catálogo, registra a data/hora de conclusão e retorna o resumo estatístico.
     *
     * @param int $itemsPerCategory Quantidade de novos produtos minerados por categoria
     * @return array<string, mixed> Resumo da rodada executada
     */
    public function runDailyAutomatedRotation(int $itemsPerCategory = 4): array
    {
        $results = [];
        $totalProducts = 0;

        foreach (self::OFFICIAL_CATEGORIES as $cat) {
            try {
                $mined = $this->mineTrendingProducts(category: $cat, limit: $itemsPerCategory, autoPersist: true);
                $count = count($mined);
                $results[$cat] = $count;
                $totalProducts += $count;
            } catch (Throwable $e) {
                $results[$cat] = 0;
            }
        }

        // Registra a conclusão do ciclo de 24 horas
        $now = time();
        $this->updateConfigField('last_daily_cycle_at', $now);

        return [
            'success'         => true,
            'executed_at'     => $now,
            'executed_date'   => date('d/m/Y H:i:s', $now),
            'total_mined'     => $totalProducts,
            'category_counts' => $results,
        ];
    }

    /**
     * Retorna os metadados do ciclo diário de 24 horas:
     * timestamp da última execução, próxima execução, segundos restantes, tempo formatado (HH:MM:SS),
     * status se expirado (is_due) e estatísticas de produtos por categoria.
     *
     * @return array<string, mixed>
     */
    public function getDailyCycleInfo(): array
    {
        $config = $this->loadConfig();
        $intervalSeconds = (int)($config['cycle_interval_seconds'] ?? 86400); // 24 horas padrão
        if ($intervalSeconds <= 0) {
            $intervalSeconds = 86400;
        }

        $lastCycleAt = isset($config['last_daily_cycle_at']) && is_numeric($config['last_daily_cycle_at'])
            ? (int)$config['last_daily_cycle_at']
            : 0;

        $now = time();
        $autoSyncEnabled = !isset($config['auto_sync_enabled']) || !empty($config['auto_sync_enabled']);

        if ($lastCycleAt <= 0) {
            // Se nunca executou, marca como vencido para disparar a primeira rodada
            $nextCycleAt = $now;
            $secondsRemaining = 0;
            $isDue = true;
        } else {
            $nextCycleAt = $lastCycleAt + $intervalSeconds;
            $secondsRemaining = max(0, $nextCycleAt - $now);
            $isDue = ($secondsRemaining === 0);
        }

        $hours = (int)floor($secondsRemaining / 3600);
        $minutes = (int)floor(($secondsRemaining % 3600) / 60);
        $seconds = (int)($secondsRemaining % 60);
        $formatted = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

        return [
            'last_cycle_at'       => $lastCycleAt,
            'last_cycle_date'     => $lastCycleAt > 0 ? date('d/m/Y H:i:s', $lastCycleAt) : 'Pendente de primeira execução',
            'next_cycle_at'       => $nextCycleAt,
            'next_cycle_date'     => date('d/m/Y H:i:s', $nextCycleAt),
            'interval_seconds'    => $intervalSeconds,
            'seconds_remaining'   => $secondsRemaining,
            'formatted_remaining' => $formatted,
            'is_due'              => $isDue,
            'auto_sync_enabled'   => $autoSyncEnabled,
            'official_categories' => self::OFFICIAL_CATEGORIES,
        ];
    }

    private function getConfigFilePath(): string
    {
        if ($this->configFilePath !== null) {
            return $this->configFilePath;
        }
        $root = defined('DOMAIN_SYSTEM_ROOT') ? DOMAIN_SYSTEM_ROOT : dirname(__DIR__, 4);
        return $root . '/config/plugins/domain_drop.json';
    }

    private function loadConfig(): array
    {
        $file = $this->getConfigFilePath();
        if (file_exists($file)) {
            $content = @file_get_contents($file);
            if ($content !== false) {
                $data = json_decode($content, true);
                if (is_array($data)) {
                    return $data;
                }
            }
        }
        return [
            'enabled'                => true,
            'auto_sync_enabled'      => true,
            'cycle_interval_seconds' => 86400,
        ];
    }

    private function updateConfigField(string $key, mixed $value): void
    {
        $file = $this->getConfigFilePath();
        $config = $this->loadConfig();
        $config[$key] = $value;
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($file, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
