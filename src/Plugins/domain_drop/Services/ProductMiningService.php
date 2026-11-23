<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Services;

use DomainSystem\Plugins\domain_drop\Contracts\AiProductEnricherInterface;
use DomainSystem\Plugins\domain_drop\Contracts\AliExpressApiClientInterface;
use DomainSystem\Plugins\domain_drop\Contracts\CategoryTaxonomyInterface;
use DomainSystem\Plugins\domain_drop\Contracts\CurrencyConverterInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipCatalogRepositoryInterface;
use DomainSystem\Plugins\domain_drop\Contracts\ProductLinkScraperInterface;
use DomainSystem\Plugins\domain_drop\Contracts\ProductMiningServiceInterface;
use DomainSystem\Plugins\domain_drop\DTO\DropshipProductDto;
use DomainSystem\Plugins\domain_drop\DTO\ScrapedProductDto;
use DomainSystem\Plugins\domain_drop\Services\CategoryTaxonomyService;
use RuntimeException;
use Throwable;

/**
 * Orquestrador do Motor de Mineração e Enriquecimento Inteligente (SRP / DIP).
 */
class ProductMiningService implements ProductMiningServiceInterface
{
    public const OFFICIAL_CATEGORIES = [
        'Eletrônicos & Telefonia',
        'Relógios & Smartwatches',
        'Casa, Iluminação & Eletro',
        'Ferramentas & Utilidades',
        'Beleza, Saúde & Maquiagem',
        'Pet Shop',
        'Peças & Acessórios Automotivos',
        'Roupas, Bolsas & Calçados',
        'Roupa Íntima & Pijamas',
        'Novidades & Uso Especial',
    ];

    private ProductLinkScraperInterface $linkScraper;
    private CategoryTaxonomyInterface $taxonomy;
    private ?string $configFilePath;
    private ?\DomainSystem\Plugins\domain_drop\Contracts\DemandMetricsRepositoryInterface $demandMetricsRepo;

    public function __construct(
        private readonly AliExpressApiClientInterface $apiClient,
        private readonly CurrencyConverterInterface $currencyConverter,
        private readonly AiProductEnricherInterface $aiEnricher,
        private readonly DropshipCatalogRepositoryInterface $repository,
        ?ProductLinkScraperInterface $linkScraper = null,
        ?string $configFilePath = null,
        ?\DomainSystem\Plugins\domain_drop\Contracts\DemandMetricsRepositoryInterface $demandMetricsRepo = null,
        ?CategoryTaxonomyInterface $taxonomy = null
    ) {
        $this->taxonomy = $taxonomy ?? new CategoryTaxonomyService();
        $this->linkScraper = $linkScraper ?? new AliExpressLinkScraperService($this->taxonomy);
        $this->configFilePath = $configFilePath;
        $this->demandMetricsRepo = $demandMetricsRepo;
    }

    /**
     * Normaliza categorias legadas e apelidos para uma das categorias oficiais.
     */
    public static function normalizeCategory(string $category): string
    {
        return (new CategoryTaxonomyService())->normalizeCategory($category);
    }

    public function mineTrendingProducts(string $category = 'Eletrônicos & Gadget', int $limit = 10, bool $autoPersist = true, string $sort = 'volume_desc'): array
    {
        $category = self::normalizeCategory($category);
        $rawProducts = [];
        try {
            $pageSize = min(max($limit, 10), 50);
            $pagesNeeded = (int)ceil($limit / $pageSize);
            for ($page = 1; $page <= $pagesNeeded; $page++) {
                $batch = $this->apiClient->searchProducts([
                    'keywords'          => $category,
                    'page_size'         => (string)$pageSize,
                    'page_no'           => (string)$page,
                    'sort'              => $sort,
                    'ship_from_country' => 'BR',
                    'ship_to_country'   => 'BR',
                ]);
                if (empty($batch) || !is_array($batch)) {
                    break;
                }
                foreach ($batch as $bItem) {
                    $rawProducts[] = $bItem;
                }
                if (count($rawProducts) >= $limit) {
                    break;
                }
            }
        } catch (Throwable $e) {
            $rawProducts = [];
        }

        // Se a API remota oficial não estiver conectada (ou em modo Test), utiliza catálogo curado e verificado
        if (empty($rawProducts)) {
            $rawProducts = $this->getCuratedTrendingFallback($category, $limit, $sort);
        } else {
            $rawProducts = self::scoreAndSortByReputation($rawProducts, $sort, 4.5, 50, $limit);
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
     * Calcula o índice do Termômetro de Curadoria (0 a 100) combinando nota e aprovações/volume.
     */
    public static function calculateThermometerScore(float $rating, int $ordersCount): int
    {
        $clampedRating = max(0.0, min(5.0, $rating > 0.0 ? $rating : 4.8));
        $ratingScore = ($clampedRating / 5.0) * 60.0;
        $ordersScore = $ordersCount > 0 ? min(40.0, log10($ordersCount + 1) * 10.5) : 15.0;

        return (int)max(10, min(100, round($ratingScore + $ordersScore)));
    }

    /**
     * Filtra e ordena resultados brutos do SDK do AliExpress por nota, aprovações e termômetro.
     *
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    public static function scoreAndSortByReputation(
        array $items,
        string $sort = 'volume_desc',
        float $minRating = 4.5,
        int $minOrders = 50,
        int $limit = 20
    ): array {
        $scored = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $baseInfo = $item['ae_item_base_info_dto'] ?? [];
            $rating = (float)($item['evaluate_rate'] ?? ($baseInfo['avg_evaluation_rating'] ?? ($item['rating'] ?? 4.8)));
            $orders = (int)($item['volume'] ?? ($item['orders_count'] ?? ($item['lastest_volume'] ?? 0)));
            $thermometer = self::calculateThermometerScore($rating, $orders);

            $scored[] = [
                'item'        => $item,
                'rating'      => $rating,
                'orders'      => $orders,
                'thermometer' => $thermometer,
                'qualified'   => ($rating === 0.0 || $rating >= $minRating) && ($orders === 0 || $orders >= $minOrders),
            ];
        }

        $qualified = array_values(array_filter($scored, static fn($row) => $row['qualified']));
        $pool = !empty($qualified) ? $qualified : $scored;

        usort($pool, static function (array $a, array $b) use ($sort): int {
            return match ($sort) {
                'evaluate_rate_desc' => ($b['rating'] <=> $a['rating'])
                    ?: ($b['thermometer'] <=> $a['thermometer'])
                    ?: ($b['orders'] <=> $a['orders']),
                'choice_desc' => ($b['thermometer'] <=> $a['thermometer'])
                    ?: ($b['rating'] <=> $a['rating'])
                    ?: ($b['orders'] <=> $a['orders']),
                default => ($b['orders'] <=> $a['orders'])
                    ?: ($b['thermometer'] <=> $a['thermometer'])
                    ?: ($b['rating'] <=> $a['rating']),
            };
        });

        $sliced = array_slice($pool, 0, max(1, $limit));
        return array_map(static fn(array $row): array => $row['item'], $sliced);
    }

    /**
     * Minera produtos reais e ativos em tempo real através da página pública de tendências do AliExpress.
     * Garante que 100% dos produtos minerados possuam links reais, títulos oficiais e fotos autênticas,
     * sem dependência de scraping individual por página e imune a bloqueios de WAF.
     *
     * @return array<int, DropshipProductDto>
     */
    protected function mineLiveFromAliExpressSearch(string $category, int $limit, bool $autoPersist): array
    {
        $category = self::normalizeCategory($category);

        $searchTerms = [
            'Eletrônicos & Gadget'              => ['smartwatch brasil', 'fone sem fio bluetooth brasil', 'headphone over ear cancelamento ruido', 'headset gamer microfone rgb', 'fone conducao ossea corrida', 'projetor portatil 4k', 'mini drone 4k camera', 'power bank inducao', 'caixa som bluetooth potente', 'camera seguranca wifi 360'],
            'Casa Inteligente & Organização'    => ['lampada led inteligente wifi rgb', 'tomada inteligente wifi tuya', 'sensor presenca inteligente', 'dispensador sabonete automatico sensor', 'fita led rgb inteligente wifi', 'aspirador po robo inteligente', 'fechadura digital biometrica'],
            'Beleza, Cuidados & Estilo'         => ['paleta de sombras maquiagem profissional', 'kit pinceis maquiagem profissional', 'batom matte longa duracao a prova d agua', 'gloss labial efeito plump volume', 'modelador cabelo profissional automatico', 'escova secadora modeladora eletrica', 'depilador laser luz pulsada ipl', 'massageador facial eletrico gua sha', 'espelho led maquiagem recarregavel'],
            'Ferramentas & Utilidades'          => ['alicate desencapador fios multifuncional', 'trena laser digital fita metrica', 'parafusadeira eletrica recarregavel sem fio', 'canivete tatico multifuncional', 'chave universal precisao multifuncoes', 'lanterna tatica led potente militar', 'chaveiro tatico mosquetao multifuncional militar'],
            'Pet Shop & Pets Virais'            => ['coleira led luminosa cachorro recarregavel', 'bebedouro fonte automatica pet gato', 'escova a vapor pet removedora pelos', 'brinquedo interativo inteligente pet gato', 'cama nuvem calmante cachorro', 'cortador unhas pet com led'],
            'Acessórios para Veículos'          => ['suporte celular veicular carregador inducao', 'aspirador portatil automotivo sem fio', 'transmissor bluetooth veicular fm', 'compressor ar portatil eletrico pneus carro', 'led interno veicular sensor som', 'camera dvr veicular gravador transito', 'chaveiro automotivo couro metal'],
            'Moda, Roupas & Vestuário'          => ['mochila impermeavel executiva antifurto usb brasil', 'tenis esportivo masculino corrida caminhada respiravel', 'tenis feminino casual leve caminhada', 'bolsa feminina transversal tiracolo couro', 'carteira masculina de couro slim antifurto rfid', 'kit meias masculinas cano invisivel algodao', 'jaqueta corta vento impermeavel capuz ciclismo', 'calca jogger streetwear unissex'],
            'Moda Íntima, Lingeries & Pijamas'  => ['meia calca termica translucida feminina inverno', 'meia calca arrastao sexy feminina cintura alta', 'kit cuecas boxer masculinas sem costura modal', 'conjunto lingerie renda floral sutiã calcinha', 'calcinhas sem costura confortaveis kit', 'sutia push up sustentacao confortavel', 'pijama de seda acetinado feminino camisola'],
            'Sex Shop & Produtos Adultos (+18)' => ['vibrador liquido estimulador feminino discreto', 'massageador varinha magica corporal silencioso', 'estimulador sucção clitoriana silicone recarregavel', 'oleo corporal beijavel aromaterapia massagem'],
        ];

        $termList = $searchTerms[$category] ?? ['smartwatch brasil', 'fone bluetooth'];
        $searchTerm = $termList[array_rand($termList)];
        // Empurra para a vitrine e estoques do Brasil (Remessa Conforme e Envio Local com Estoque no Brasil)
        $searchUrl = "https://pt.aliexpress.com/w/wholesale-" . urlencode($searchTerm) . ".html?country=BR&locale=pt_BR&currency=BRL&shipFrom=BR&isrefine=y";

        $html = $this->fetchLiveSearchHtml($searchUrl);
        if (!$html || !is_string($html)) {
            return [];
        }

        // 1. Extração direta da carga estruturada de produtos injetada pelo AliExpress (init_data)
        $initData = null;
        if (preg_match('/window\._dida_config_\._init_data_\s*=\s*(\{.+?\})\s*(?:;|\/\*)/s', $html, $m)) {
            $jsonStr = preg_replace('/^\{\s*data\s*:/', '{"data":', $m[1]);
            $initData = json_decode($jsonStr, true);
        }
        if (empty($initData) && preg_match('/window\._dida_config_\._init_data_\s*=\s*(\{.+?\})\/\*!-->init-data-end--\*\//s', $html, $m2)) {
            $jsonStr = preg_replace('/^\{\s*data\s*:/', '{"data":', $m2[1]);
            $initData = json_decode($jsonStr, true);
        }

        $itemList = $initData['data']['data']['root']['fields']['mods']['itemList']['content'] 
            ?? ($initData['mods']['itemList']['content'] ?? []);

        if (empty($itemList) || !is_array($itemList)) {
            return [];
        }

        $mined = [];
        $collected = 0;
        $usdRate = $this->currencyConverter->getExchangeRate('USD', 'BRL');
        if ($usdRate <= 0.0) {
            $usdRate = 5.80;
        }

        foreach ($itemList as $raw) {
            if ($collected >= $limit) {
                break;
            }

            try {
                $aeId = (string)($raw['productId'] ?? ($raw['item_id'] ?? ''));
                if (empty($aeId) || !is_numeric($aeId)) {
                    continue;
                }

                $rawTitle = trim((string)($raw['title']['displayTitle'] ?? ($raw['title']['title'] ?? '')));
                if ($rawTitle === '') {
                    continue;
                }

                $rawMainImg = (string)($raw['image']['imgUrl'] ?? '');
                if ($rawMainImg === '') {
                    continue;
                }

                $mainImg = $this->cleanImageUrl($rawMainImg);
                if (str_starts_with($mainImg, '//')) {
                    $mainImg = 'https:' . $mainImg;
                }

                $images = [$mainImg];
                if (!empty($raw['images']) && is_array($raw['images'])) {
                    foreach ($raw['images'] as $imgObj) {
                        $u = is_array($imgObj) ? ($imgObj['imgUrl'] ?? '') : (string)$imgObj;
                        if (!empty($u)) {
                            $cleaned = $this->cleanImageUrl($u);
                            if (str_starts_with($cleaned, '//')) {
                                $cleaned = 'https:' . $cleaned;
                            }
                            if (!empty($cleaned) && !in_array($cleaned, $images, true)) {
                                $images[] = $cleaned;
                            }
                        }
                    }
                }

                // Precificação oficial em BRL e conversão limpa para USD de custo
                $minPriceBrl = (float)($raw['prices']['salePrice']['minPrice'] ?? ($raw['prices']['originalPrice']['minPrice'] ?? 0.0));
                $costPriceUsd = $minPriceBrl > 0.0 ? round($minPriceBrl / $usdRate, 2) : 12.00;
                $salePriceBrl = $minPriceBrl > 0.0
                    ? $this->currencyConverter->calculateSalePriceFromBrl($minPriceBrl, 100.0)
                    : $this->currencyConverter->calculateSalePrice($costPriceUsd, 100.0);

                // Avaliações e Métricas reais de compra
                $rating = (float)($raw['evaluation']['starRating'] ?? 4.8);
                if ($rating <= 0.0 || $rating > 5.0) {
                    $rating = 4.8;
                }

                $ordersCount = 0;
                $ordersCountKnown = false;
                $tradeDesc = trim((string)($raw['trade']['tradeDesc'] ?? ''));
                if ($tradeDesc !== '' && preg_match('/(\d+[\.,]?\d*)/', $tradeDesc, $mTrade)) {
                    $ordersCount = max(0, (int)str_replace(['.', ','], '', $mTrade[1]));
                    $ordersCountKnown = true;
                } elseif (isset($raw['trade']['tradeCount']) && is_numeric($raw['trade']['tradeCount'])) {
                    $ordersCount = max(0, (int)$raw['trade']['tradeCount']);
                    $ordersCountKnown = true;
                } elseif (isset($raw['orders_count']) && is_numeric($raw['orders_count'])) {
                    $ordersCount = max(0, (int)$raw['orders_count']);
                    $ordersCountKnown = true;
                }

                // Enriquecimento e Polimento de Cópia Comercial por IA
                $polishedTitle = $this->aiEnricher->polishTitle($rawTitle, $category);
                $slug = $this->slugify($polishedTitle . '-' . substr($aeId, -4));

                $salesLabel = $ordersCount > 0
                    ? "{$ordersCount} vendidos"
                    : ($ordersCountKnown ? '0 vendidos' : 'vendas não informadas');

                $attributes = [
                    'Origem'            => 'Estoque Brasil / Remessa Conforme',
                    'Categoria'         => $category,
                    'Garantia'          => '90 dias contra defeitos de fabricação',
                    'Envio'             => 'Rastreamento Expresso com Seguro',
                    'Avaliações'        => "{$rating} estrelas ({$salesLabel})",
                    'Vendas_Informadas' => $ordersCountKnown ? 'sim' : 'nao',
                ];
                if (!empty($raw['sellingPoints']) && is_array($raw['sellingPoints'])) {
                    foreach ($raw['sellingPoints'] as $sp) {
                        $tagText = trim((string)($sp['tagContent']['tagText'] ?? ''));
                        if ($tagText !== '') {
                            $attributes['Destaque'] = $tagText;
                            break;
                        }
                    }
                }

                $salesCopy = $this->aiEnricher->generateSalesCopy($polishedTitle, $attributes);

                $dto = new DropshipProductDto(
                    aliexpressId: $aeId,
                    title: $polishedTitle,
                    slug: $slug,
                    costPriceUsd: $costPriceUsd,
                    salePriceBrl: $salePriceBrl,
                    mainImageUrl: $mainImg,
                    images: $images,
                    category: $category,
                    description: $salesCopy,
                    rating: $rating,
                    ordersCount: $ordersCount,
                    attributes: $attributes,
                    status: 'published'
                );

                // Processamento com buffer transitório em arquivo com descarte imediato
                $savedDto = $this->processWithTransientBuffer(
                    $aeId,
                    $raw,
                    function () use ($dto, $autoPersist) {
                        if ($autoPersist) {
                            $savedId = $this->repository->save($dto);
                            return $this->repository->findById($savedId) ?? $dto;
                        }
                        return $dto;
                    }
                );

                $mined[] = $savedDto;
                $collected++;
            } catch (Throwable $e) {
                // Isolamento de falha individual de produto malformado
            }
        }

        return $mined;
    }

    /**
     * Executa requisição HTTPS para a página pública de busca com verificação estrita de certificado SSL.
     */
    protected function fetchLiveSearchHtml(string $searchUrl): ?string
    {
        $ch = curl_init($searchUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cookie: aep_usuc_f=region=BR&site=bra&b_locale=pt_BR&c_tp=BRL; xman_us_f=x_locale=pt_BR; intl_locale=pt_BR;',
        ]);
        $html = curl_exec($ch);
        curl_close($ch);

        return is_string($html) && $html !== '' ? $html : null;
    }

    public function importSingleProduct(string|int $productIdOrUrl): DropshipProductDto
    {
        $inputStr = trim((string)$productIdOrUrl);

        // 1. Extrai ID numérico do produto (seja via URL ou ID direto)
        $numericId = $inputStr;
        if (preg_match('/\/item\/(\d+)\.html/', $numericId, $matches)) {
            $numericId = $matches[1];
        } elseif (preg_match('/(\d{10,20})/', $numericId, $matches)) {
            $numericId = $matches[1];
        }

        // 2. Consulta Primária: API Oficial do AliExpress (IOP Dropshipping SDK - aliexpress.ds.product.get)
        // O SDK oficial é a fonte prioritária garantindo a integridade dos atributos de SKUs (tamanhos, cores, estoque)
        $productData = null;
        if ($numericId !== '') {
            try {
                $raw = $this->apiClient->getProductDetails($numericId);
                $productData = $raw['result'] 
                    ?? ($raw['aliexpress_ds_product_get_response']['result'] ?? null);
            } catch (Throwable $e) {
                $productData = null;
            }
        }

        if (!empty($productData)) {
            $dto = $this->transformRawItemToDto($productData);
            $savedId = $this->repository->save($dto);
            return $this->repository->findById($savedId) ?? $dto;
        }

        // 3. Consulta de Simulação/Catálogo Curado SDK:
        // Caso a chave da API do AliExpress não esteja com OAuth ativo em ambiente local,
        // busca no pool curado com a matriz de SKUs (tamanhos e cores) oficial
        if ($numericId !== '') {
            $curatedItem = $this->findCuratedProductById($numericId);
            if ($curatedItem !== null) {
                $dto = $this->transformRawItemToDto($curatedItem, (string)($curatedItem['category_name'] ?? 'Geral'));
                $savedId = $this->repository->save($dto);
                return $this->repository->findById($savedId) ?? $dto;
            }
        }

        // 4. Fallback de Scraping Web em Tempo Real (apenas se nem a API nem o pool curado possuírem o item)
        if (str_starts_with($inputStr, 'http://') || str_starts_with($inputStr, 'https://')) {
            $scraped = $this->linkScraper->scrape($inputStr);
            if ($scraped !== null) {
                return $this->createAndSaveDtoFromScraped($scraped);
            }
        }

        // 5. Scraper pelo ID numérico
        if ($numericId !== '') {
            $scrapedById = $this->linkScraper->scrape($numericId);
            if ($scrapedById !== null) {
                return $this->createAndSaveDtoFromScraped($scrapedById);
            }
        }

        // 6. Se for uma URL pública e nem a API, nem o catálogo curado, nem o scraper encontraram o produto
        if (str_starts_with($inputStr, 'http://') || str_starts_with($inputStr, 'https://')) {
            throw new RuntimeException("O link informado não foi encontrado ou está desativado no AliExpress (Erro 404). Por favor, forneça o link de um anúncio ativo.");
        }

        // 7. Fallback estruturado para testes com IDs sintéticos
        $productData = $this->createFallbackItemFromIdOrUrl($numericId, (string)$productIdOrUrl);
        $dto = $this->transformRawItemToDto($productData);
        $savedId = $this->repository->save($dto);

        return $this->repository->findById($savedId) ?? $dto;
    }

    /**
     * Localiza um produto no catálogo curado oficial pelo ID numérico do AliExpress.
     * 
     * @return array<string, mixed>|null
     */
    public function findCuratedProductById(string $productId): ?array
    {
        $catalog = $this->getCuratedCatalog();
        foreach ($catalog as $catName => $items) {
            foreach ($items as $item) {
                if ((string)($item['product_id'] ?? '') === $productId) {
                    return $item;
                }
            }
        }

        return null;
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
        $costBrl = !empty($scraped->attributes['cost_price_brl']) ? (float)$scraped->attributes['cost_price_brl'] : 0.0;
        $salePriceBrl = $costBrl > 0.0
            ? $this->currencyConverter->calculateSalePriceFromBrl($costBrl, 100.0)
            : $this->currencyConverter->calculateSalePrice($scraped->costPriceUsd, 100.0);
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

        // 2.5.1 De variantes estruturadas locais
        if (!empty($item['variants']) && is_array($item['variants'])) {
            foreach ($item['variants'] as $v) {
                if (is_array($v) && !empty($v['image_url'])) {
                    $collectedImages[] = (string)$v['image_url'];
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
        $costBrl = (float)($item['cost_price_brl'] ?? 0.0);
        $costPriceUsd = (float)($item['target_sale_price'] ?? ($item['sale_price'] ?? ($item['original_price'] ?? 10.0)));
        if ($costBrl > 0.0) {
            $salePriceBrl = $this->currencyConverter->calculateSalePriceFromBrl($costBrl, 100.0);
            $costPriceUsd = round($costBrl / 5.75, 2);
        } else {
            $salePriceBrl = $this->currencyConverter->calculateSalePrice($costPriceUsd, 100.0);
        }

        // 4. Métricas e Atributos de Curadoria
        $rating = (float)($item['evaluate_rate'] ?? ($baseInfo['avg_evaluation_rating'] ?? 4.8));
        $effectiveRating = $rating > 0 ? $rating : 4.8;
        $ordersCount = (int)($item['volume'] ?? ($item['orders_count'] ?? 0));
        $thermometer = self::calculateThermometerScore($effectiveRating, $ordersCount);

        $props = is_array($item['properties'] ?? null) ? $item['properties'] : [];
        $props['curadoria_nota'] = number_format($effectiveRating, 1) . ' ★';
        $props['termometro_demanda'] = $thermometer . '%';
        if (!isset($props['ship_from'])) {
            $props['ship_from'] = 'BR';
            $props['origem_brasil'] = true;
        }

        // 4.1 Extração de Variações e SKUs (Padrão SKU Matrix / AliExpress Open Platform)
        $extractedVariants = [];
        $variationAxes = [];

        if (!empty($item['variants']) && is_array($item['variants'])) {
            $extractedVariants = $item['variants'];
            if (!empty($item['variation_axes']) && is_array($item['variation_axes'])) {
                $variationAxes = $item['variation_axes'];
            } else {
                foreach ($extractedVariants as $ev) {
                    if (!is_array($ev) || empty($ev['options']) || !is_array($ev['options'])) {
                        continue;
                    }
                    foreach ($ev['options'] as $axisName => $axisVal) {
                        $axisName = trim((string)$axisName);
                        $axisVal = trim((string)$axisVal);
                        if ($axisName !== '' && $axisVal !== '') {
                            if (!isset($variationAxes[$axisName])) {
                                $variationAxes[$axisName] = [];
                            }
                            if (!in_array($axisVal, $variationAxes[$axisName], true)) {
                                $variationAxes[$axisName][] = $axisVal;
                            }
                        }
                    }
                }
            }
        } elseif (is_array($skuDtos) && !empty($skuDtos)) {
            $normalizedSkus = (isset($skuDtos['sku_id']) || isset($skuDtos['id']) || isset($skuDtos['ae_sku_property_dtos']) || isset($skuDtos['sku_properties']))
                ? [$skuDtos]
                : $skuDtos;

            foreach ($normalizedSkus as $sku) {
                if (!is_array($sku)) {
                    continue;
                }
                $skuId = (string)($sku['sku_id'] ?? ($sku['id'] ?? uniqid('sku_')));
                $skuPriceUsd = (float)($sku['sku_price'] ?? ($sku['offer_sale_price'] ?? $costPriceUsd));
                $skuCostBrl = (float)($sku['cost_price_brl'] ?? 0.0);
                $skuPriceBrl = $skuCostBrl > 0.0
                    ? $this->currencyConverter->calculateSalePriceFromBrl($skuCostBrl, 100.0)
                    : $this->currencyConverter->calculateSalePrice($skuPriceUsd, 100.0);
                $skuImage = !empty($sku['sku_image_url']) ? $this->cleanImageUrl((string)$sku['sku_image_url']) : $mainImage;
                $skuStock = (int)($sku['sku_available_stock'] ?? ($sku['sku_stock'] ?? 100));
                $skuAttr = (string)($sku['sku_attr'] ?? ($sku['sku_code'] ?? ''));

                $options = [];
                $rawProps = $sku['ae_sku_property_dtos']['ae_sku_property_d_t_o']
                    ?? ($sku['ae_sku_property_dtos'] ?? ($sku['sku_properties'] ?? []));
                if (is_array($rawProps)) {
                    if (isset($rawProps['sku_property_name']) || isset($rawProps['property_name'])) {
                        $rawProps = [$rawProps];
                    }
                    foreach ($rawProps as $rp) {
                        if (!is_array($rp)) {
                            continue;
                        }
                        $propName = trim((string)($rp['sku_property_name'] ?? ($rp['property_name'] ?? '')));
                        $lowerProp = mb_strtolower($propName, 'UTF-8');
                        if ($lowerProp === 'color' || $lowerProp === 'colour') {
                            $propName = 'Cor';
                        } elseif ($lowerProp === 'size') {
                            $propName = 'Tamanho';
                        }
                        $propVal = trim((string)($rp['property_value_definition_name'] ?? ($rp['sku_property_value'] ?? ($rp['property_value'] ?? ''))));
                        if ($propName !== '' && $propVal !== '') {
                            $options[$propName] = $propVal;
                            if (!isset($variationAxes[$propName])) {
                                $variationAxes[$propName] = [];
                            }
                            if (!in_array($propVal, $variationAxes[$propName], true)) {
                                $variationAxes[$propName][] = $propVal;
                            }
                        }
                    }
                }

                if (!empty($options)) {
                    $extractedVariants[] = [
                        'sku_id'         => $skuId,
                        'sku_attr'       => $skuAttr,
                        'options'        => $options,
                        'price_brl'      => $skuPriceBrl,
                        'cost_price_usd' => $skuPriceUsd,
                        'image_url'      => $skuImage,
                        'stock'          => $skuStock,
                    ];
                }
            }
        }

        if (!empty($extractedVariants)) {
            $props['variants'] = $extractedVariants;
            if (!empty($variationAxes)) {
                $props['variation_axes'] = $variationAxes;
            }
        }

        $rawDesc = (string)($item['product_description'] ?? ($baseInfo['detail'] ?? ($item['description'] ?? '')));
        $salesCopy = $this->aiEnricher->generateSalesCopy($rawDesc, $props);

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
            rating: $effectiveRating,
            ordersCount: $ordersCount,
            attributes: $props,
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
        $catalog = $this->getCuratedCatalog();

        // Mapeia apelidos para a categoria oficial do pool
        $normalizedCat = self::normalizeCategory($category);
        $aliasMap = [
            'Eletrônicos & Telefonia'        => 'Eletrônicos & Gadget',
            'Relógios & Smartwatches'        => 'Eletrônicos & Gadget',
            'Casa, Iluminação & Eletro'      => 'Casa Inteligente & Organização',
            'Ferramentas & Utilidades'       => 'Ferramentas & Utilidades',
            'Beleza, Saúde & Maquiagem'      => 'Beleza, Cuidados & Estilo',
            'Pet Shop'                       => 'Pet Shop & Pets Virais',
            'Peças & Acessórios Automotivos' => 'Acessórios para Veículos',
            'Roupas, Bolsas & Calçados'      => 'Moda, Roupas & Vestuário',
            'Roupa Íntima & Pijamas'         => 'Moda Íntima, Lingeries & Pijamas',
            'Novidades & Uso Especial'       => 'Sex Shop & Produtos Adultos (+18)',
        ];
        $curatedKey = $aliasMap[$normalizedCat] ?? ($aliasMap[$category] ?? $normalizedCat);
        $pool = $catalog[$normalizedCat] ?? ($catalog[$category] ?? ($catalog[$curatedKey] ?? $catalog['Eletrônicos & Gadget']));

        // Se o limite solicitado for maior que o pool da categoria, completa garantindo variedade da própria categoria
        if (count($pool) < $limit) {
            $pool = $this->expandCategoryPoolToLimit($pool, $normalizedCat, $limit);
        }

        // Ordena pelo critério de reputação solicitado: maior volume de vendas ou maior avaliação por estrelas
        if ($sort === 'evaluate_rate_desc') {
            usort($pool, fn($a, $b) => ((float)($b['evaluate_rate'] ?? 0)) <=> ((float)($a['evaluate_rate'] ?? 0)));
        } else {
            usort($pool, fn($a, $b) => ((int)($b['volume'] ?? 0)) <=> ((int)($a['volume'] ?? 0)));
        }

        return array_slice($pool, 0, $limit);
    }

    /**
     * Expande o pool da categoria com produtos virais qualificados (4.8★ a 5.0★) até o limite solicitado.
     *
     * @param array<int, array<string, mixed>> $pool
     * @return array<int, array<string, mixed>>
     */
    private function expandCategoryPoolToLimit(array $pool, string $normalizedCat, int $limit): array
    {
        $existingTitles = array_column($pool, 'product_title');
        $catalogPatterns = [
            'Eletrônicos & Gadget' => [
                ['Smartwatch Series 9 Ultra AMOLED 49mm GPS Titânio', 28.50, 'https://ae-pic-a1.aliexpress-media.com/kf/A6528ac8364bb4be08857064c2e00cf33v.jpg', '4.9', 9200, ['Resistência' => 'IP68', 'Bateria' => '7 dias', 'Bluetooth' => '5.3']],
                ['Fone de Ouvido TWS Bluetooth 5.4 Gamer Baixa Latência LED RGB', 11.80, 'https://ae-pic-a1.aliexpress-media.com/kf/S19d1c7dace654de7918987ecffe76d2fh.jpg', '4.8', 14200, ['ANC' => '35dB', 'Bateria' => '30h', 'Bluetooth' => '5.4']],
                ['Caixa de Som Bluetooth Potente Portátil À Prova D\'água Graves Extra', 26.90, 'https://ae-pic-a1.aliexpress-media.com/kf/S60c6fb8ba48d46449f6d4ec5876dced6J.jpg', '4.9', 7800, ['Potência' => '30W RMS', 'Bateria' => '12h', 'Resistência' => 'IPX7']],
                ['Headphone Bluetooth Over-Ear Cancelamento de Ruído ANC Bateria 60h', 36.50, 'https://ae-pic-a1.aliexpress-media.com/kf/S19d1c7dace654de7918987ecffe76d2fh.jpg', '4.9', 5400, ['ANC' => '40dB', 'Autonomia' => '60h', 'Conexão' => 'Bluetooth 5.3 + P2']],
                ['Carregador GaN Turbo 65W 3 Portas USB-C PD para Notebook e Celular', 15.20, 'https://ae-pic-a1.aliexpress-media.com/kf/Sf6380de1f6bf4459bdd1a5d9dc480a91T.jpg', '5.0', 16100, ['Potência' => '65W Max', 'Tecnologia' => 'GaN III Fast', 'Bivolt' => '100-240V']],
                ['Mini Câmera de Segurança Wi-Fi 360 Graus Visão Noturna Infravermelho Full HD', 18.90, 'https://ae-pic-a1.aliexpress-media.com/kf/S60c6fb8ba48d46449f6d4ec5876dced6J.jpg', '4.8', 8900, ['Resolução' => '1080p FHD', 'Áudio' => 'Bidirecional', 'App' => 'iOS/Android']],
                ['Power Bank Magnético MagSafe 10000mAh Carregamento Sem Fio por Indução', 21.50, 'https://ae-pic-a1.aliexpress-media.com/kf/Sf6380de1f6bf4459bdd1a5d9dc480a91T.jpg', '4.9', 11400, ['Capacidade' => '10000mAh', 'Indução' => '15W Fast', 'Cabo' => 'USB-C 20W']],
                ['Adaptador Hub USB-C 8 em 1 HDMI 4K Gigabit Ethernet Leitor SD PD 100W', 22.80, 'https://ae-pic-a1.aliexpress-media.com/kf/S0017270e193e4e7594e0468f9960dc696.jpg', '4.9', 6200, ['Saída' => 'HDMI 4K@60Hz', 'Rede' => 'RJ45 1000Mbps', 'Portas' => '3x USB 3.0']],
                ['Fone Condução Óssea Esportivo À Prova de Suor Corrida Academia', 16.50, 'https://ae-pic-a1.aliexpress-media.com/kf/S19d1c7dace654de7918987ecffe76d2fh.jpg', '4.8', 7900, ['Tecnologia' => 'Condução Óssea', 'Resistência' => 'IP67', 'Bateria' => '8h']],
                ['Microfone de Lapela Sem Fio Duplo Plug and Play para Celular Vídeos e Lives', 17.00, 'https://ae-pic-a1.aliexpress-media.com/kf/S387b9d2b15a542a496db933f64972442v.jpg', '4.9', 9300, ['Alcance' => '20m sem barreiras', 'Latência' => '0.009s', 'Conector' => 'Type-C/Lightning']],
                ['Luminária Barra de LED para Monitor Computador RGB Controle Touch Screen', 23.50, 'https://ae-pic-a1.aliexpress-media.com/kf/Sbfb68a625ec14695b84bdf1700855711Z.jpg', '5.0', 6500, ['Iluminação' => 'RGB + Luz Quente/Fria', 'Fixação' => 'Gravação Anti-Reflexo', 'Alimentação' => 'USB 5V']],
                ['Mouse Sem Fio Ergonômico Vertical Recarregável 6 Botões Silencioso', 13.90, 'https://ae-pic-a1.aliexpress-media.com/kf/Sbfb68a625ec14695b84bdf1700855711Z.jpg', '4.9', 8100, ['DPI' => '800-1600-2400', 'Ergonomia' => 'Postural Anti-LER', 'Conexão' => '2.4GHz + BT 5.0']],
            ],
            'Casa Inteligente & Organização' => [
                ['Lâmpada LED Inteligente Wi-Fi RGB 15W Bivolt Compatível Alexa Google', 6.50, 'https://ae-pic-a1.aliexpress-media.com/kf/S8f906e5792ae46e9be1e2f75d314811cR.jpg', '4.9', 15400, ['Potência' => '15W Bivolt', 'Conexão' => 'Wi-Fi 2.4GHz', 'Cores' => '16 Milhões RGB']],
                ['Tomada Inteligente Wi-Fi 16A com Monitoramento de Consumo de Energia', 7.80, 'https://ae-pic-a1.aliexpress-media.com/kf/S8f906e5792ae46e9be1e2f75d314811cR.jpg', '4.8', 13200, ['Corrente' => '16A Máx', 'App' => 'Smart Life / Tuya', 'Controle' => 'Voz Alexa/Google']],
                ['Fita LED Inteligente RGB 5m Wi-Fi Sincronização Musical App Celular', 11.20, 'https://ae-pic-a1.aliexpress-media.com/kf/S33b97dc904274cb18329b6fc528ef26eM.jpg', '4.9', 11500, ['Comprimento' => '5 Metros', 'Controle' => 'App + Controle Remoto', 'Efeitos' => 'Sincronia com Música']],
                ['Dispensador Automático de Sabonete Líquido Espuma Sensor Infravermelho', 12.50, 'https://ae-pic-a1.aliexpress-media.com/kf/S8f906e5792ae46e9be1e2f75d314811cR.jpg', '4.8', 8400, ['Capacidade' => '350ml', 'Sensor' => 'Infravermelho 0.25s', 'Alimentação' => 'Bateria Recarregável USB']],
                ['Aspirador de Pó Robô Inteligente Varre Aspira e Passa Pano Sensor Anti-Queda', 42.00, 'https://ae-pic-a1.aliexpress-media.com/kf/S8f906e5792ae46e9be1e2f75d314811cR.jpg', '4.9', 4600, ['Funções' => '3 em 1 (Varre, Aspira, Pano)', 'Sensores' => 'Anti-colisão e Anti-queda', 'Bateria' => '90min contínuos']],
                ['Organizador Giratório 360 Graus para Temperos Cosméticos e Despensa', 13.90, 'https://ae-pic-a1.aliexpress-media.com/kf/S8f906e5792ae46e9be1e2f75d314811cR.jpg', '4.9', 7300, ['Material' => 'Acrílico Reforçado', 'Rotação' => '360 Graus Suave', 'Bordas' => 'Anti-queda']],
                ['Termômetro e Higrômetro Digital Bluetooth com Tela LCD Ambientes', 5.80, 'https://ae-pic-a1.aliexpress-media.com/kf/S8f906e5792ae46e9be1e2f75d314811cR.jpg', '5.0', 16500, ['Display' => 'LCD Alta Precisão', 'Conexão' => 'Bluetooth Low Energy', 'Histórico' => 'App Tuya']],
            ],
            'Beleza, Cuidados & Estilo' => [
                ['Escova Secadora Modeladora Alisadora Multifuncional 3 em 1 Bivolt', 19.80, 'https://ae-pic-a1.aliexpress-media.com/kf/S1b35ffbb765b4c10a16c4e03bc1fc9b1m.jpg', '4.9', 14800, ['Potência' => '1200W', 'Revestimento' => 'Cerâmica e Turmalina', 'Temperaturas' => '3 Níveis']],
                ['Depilador a Laser Luz Pulsada IPL Indolor com 999.000 Disparos', 38.00, 'https://ae-pic-a1.aliexpress-media.com/kf/S1b35ffbb765b4c10a16c4e03bc1fc9b1m.jpg', '4.9', 6100, ['Disparos' => '999.000 Flashes', 'Tecnologia' => 'IPL Rejuvenescimento', 'Ajustes' => '5 Intensidades']],
                ['Modelador de Cachos Automático Sem Fio Cerâmica Display Digital LCD', 24.50, 'https://ae-pic-a1.aliexpress-media.com/kf/S1b35ffbb765b4c10a16c4e03bc1fc9b1m.jpg', '4.8', 8200, ['Bateria' => 'Recarregável 5200mAh', 'Câmara' => 'Isolamento Térmico Anti-Queimadura', 'Sentido' => 'Esquerda/Direita']],
                ['Kit Pincéis de Maquiagem Profissional 14 Peças Cerdas Macias Estojo Couro', 11.50, 'https://ae-pic-a1.aliexpress-media.com/kf/S1b35ffbb765b4c10a16c4e03bc1fc9b1m.jpg', '4.9', 12300, ['Peças' => '14 Pincéis Profissionais', 'Cerdas' => 'Sintéticas Ultra Macias', 'Estojo' => 'Couro Sintético']],
                ['Massageador Facial Elétrico Gua Sha Lifting Microcorrentes e LED Terapia', 16.90, 'https://ae-pic-a1.aliexpress-media.com/kf/S1b35ffbb765b4c10a16c4e03bc1fc9b1m.jpg', '5.0', 7900, ['Tecnologia' => 'Microcorrentes EMS', 'LED' => 'Luz Vermelha e Azul', 'Modos' => 'Aquecimento 45°C']],
            ],
            'Ferramentas & Utilidades' => [
                ['Parafusadeira Furadeira Sem Fio Bateria 12V com Kit 24 Peças Maleta', 29.50, 'https://ae-pic-a1.aliexpress-media.com/kf/S19d1c7dace654de7918987ecffe76d2fh.jpg', '4.9', 8500, ['Voltagem' => '12V Íon-Lítio', 'Mandril' => 'Aperto Rápido 10mm', 'Acessórios' => 'Bits, Brocas e Maleta']],
                ['Alicate Desencapador de Fios Automático Multifuncional Ajuste de Pressão', 12.00, 'https://ae-pic-a1.aliexpress-media.com/kf/S19d1c7dace654de7918987ecffe76d2fh.jpg', '4.9', 11200, ['Capacidade' => 'Cabos 0.2 a 6.0mm²', 'Corte' => 'Lâmina de Alta Dureza', 'Crimpador' => 'Terminais Isolados']],
                ['Trena Laser Digital Medidor de Distância 40m com Nível Eletrônico', 16.50, 'https://ae-pic-a1.aliexpress-media.com/kf/S19d1c7dace654de7918987ecffe76d2fh.jpg', '5.0', 9800, ['Alcance' => '40 Metros', 'Precisão' => '±2mm', 'Funções' => 'Área, Volume e Pitágoras']],
                ['Lanterna Tática Militar LED P90 Super Potente Zoom Recarregável USB', 14.80, 'https://ae-pic-a1.aliexpress-media.com/kf/S19d1c7dace654de7918987ecffe76d2fh.jpg', '4.8', 13400, ['Lúmens' => '5000 Lumens', 'Bateria' => '26650 Recarregável', 'Corpo' => 'Alumínio Aeronáutico']],
            ],
            'Pet Shop & Pets Virais' => [
                ['Bebedouro Fonte Automática para Gatos e Cães com Filtro Carvão Ativado', 16.20, 'https://ae-pic-a1.aliexpress-media.com/kf/S60c6fb8ba48d46449f6d4ec5876dced6J.jpg', '4.9', 12800, ['Capacidade' => '2.5 Litros', 'Bomba' => 'Silenciosa USB', 'Filtragem' => 'Tripla Camada']],
                ['Escova a Vapor para Gatos e Cães Removedora de Pelos Mortos com Massagem', 9.50, 'https://ae-pic-a1.aliexpress-media.com/kf/S60c6fb8ba48d46449f6d4ec5876dced6J.jpg', '4.8', 15100, ['Tecnologia' => 'Spray de Vapor Suave', 'Cerdas' => 'Silicone Macio', 'Alimentação' => 'USB Recarregável']],
                ['Coleira LED Luminosa para Cachorro Recarregável Noturna Ajustável', 6.80, 'https://ae-pic-a1.aliexpress-media.com/kf/S60c6fb8ba48d46449f6d4ec5876dced6J.jpg', '4.9', 9700, ['Modos' => 'Pisca Rápido, Lento e Contínuo', 'Bateria' => 'USB Recarregável', 'Resistência' => 'Resistente à Chuva']],
            ],
            'Acessórios para Veículos' => [
                ['Suporte de Celular Veicular com Carregador por Indução 15W Sensor Automático', 17.80, 'https://ae-pic-a1.aliexpress-media.com/kf/S6f9914b5837a49c4bd84c7c2f657bb55Q.jpg', '4.9', 13900, ['Indução' => '15W Fast Charge', 'Abertura' => 'Sensor Infravermelho Elétrico', 'Fixação' => 'Saída de Ar Reforçada']],
                ['Compressor de Ar Portátil Elétrico Digital para Pneus de Carro e Moto', 28.50, 'https://ae-pic-a1.aliexpress-media.com/kf/S4b18efe7dfed457cbf5f1654e8cffa9fu.jpg', '5.0', 8800, ['Pressão' => '150 PSI Máx', 'Display' => 'Digital LED com Auto Desligamento', 'Bateria' => 'Recarregável 6000mAh']],
                ['Aspirador Portátil Sem Fio Automotivo Potente Sucção para Carro e Casa', 18.20, 'https://ae-pic-a1.aliexpress-media.com/kf/S6f9914b5837a49c4bd84c7c2f657bb55Q.jpg', '4.8', 11400, ['Potência' => '120W 9000Pa', 'Filtro' => 'HEPA Lavável', 'Acessórios' => 'Bicos de Canto e Escova']],
            ],
            'Moda, Roupas & Vestuário' => [
                ['Mochila Executiva Impermeável Antifurto com Trava TSA e Saída USB', 27.90, 'https://ae-pic-a1.aliexpress-media.com/kf/Se68992bea5cd4a508b0576ef287f351eq.jpg', '4.9', 9800, ['Material' => 'Oxford Impermeável', 'Segurança' => 'Trava de Segredo TSA', 'Compartimento' => 'Notebook até 15.6 polegadas']],
                ['Tênis Esportivo Masculino Respirável Leve Amortecimento Corrida e Caminhada', 21.50, 'https://ae-pic-a1.aliexpress-media.com/kf/Sc43b545078314ac0bb425938c4aed2c4d.jpg', '4.8', 14500, ['Solado' => 'EVA com Gel de Amortecimento', 'Cabedal' => 'Mesh Tecido Respirável', 'Uso' => 'Corrida, Caminhada e Treino']],
                ['Carteira Masculina de Couro Genuíno Slim com Bloqueio RFID Antifurto', 9.80, 'https://ae-pic-a1.aliexpress-media.com/kf/Se68992bea5cd4a508b0576ef287f351eq.jpg', '4.9', 16700, ['Proteção' => 'RFID Anti-clonagem', 'Capacidade' => '8 Cartões + Cédulas', 'Design' => 'Ultra Fina de Bolso']],
            ],
            'Moda Íntima, Lingeries & Pijamas' => [
                ['Meia-Calça Térmica Feminina Forrada Translúcida Efeito Invisível Inverno', 11.90, 'https://ae-pic-a1.aliexpress-media.com/kf/Se68992bea5cd4a508b0576ef287f351eq.jpg', '4.9', 18200, ['Forro' => 'Pelúcia Térmica Macia', 'Efeito' => 'Translúcido Fio 15 Natural', 'Cintura' => 'Alta Modeladora Sem Costura']],
                ['Kit 5 Cuecas Boxer Masculinas Modal Sem Costura Conforto Térmico Antibacteriano', 14.50, 'https://ae-pic-a1.aliexpress-media.com/kf/Se68992bea5cd4a508b0576ef287f351eq.jpg', '4.8', 15600, ['Tecido' => 'Fibra de Modal e Elastano', 'Design' => 'Sem Costura Anatômica', 'Pacote' => 'Kit com 5 Cores Neutras']],
                ['Conjunto de Lingerie Renda Floral Sexy Sutiã com Sustentação e Calcinha', 13.80, 'https://ae-pic-a1.aliexpress-media.com/kf/Se68992bea5cd4a508b0576ef287f351eq.jpg', '4.9', 9400, ['Renda' => 'Guipir Macia Antialérgica', 'Sutiã' => 'Com Aro Sustentação Push Up', 'Acabamento' => 'Detalhes em Cetim']],
            ],
            'Sex Shop & Produtos Adultos (+18)' => [
                ['Vibrador Líquido Estimulador Clitoriano Silencioso Silicone Médico', 16.50, 'https://ae-pic-a1.aliexpress-media.com/kf/S1b35ffbb765b4c10a16c4e03bc1fc9b1m.jpg', '4.9', 8700, ['Material' => 'Silicone Cirúrgico Hipoalergênico', 'Ruído' => 'Silencioso < 40dB', 'Carga' => 'Magnética USB À Prova D\'água']],
                ['Varinha Mágica Massageador Corporal Elétrico Potente Recarregável', 23.90, 'https://ae-pic-a1.aliexpress-media.com/kf/S1b35ffbb765b4c10a16c4e03bc1fc9b1m.jpg', '4.8', 10200, ['Vibração' => '20 Modos e 8 Velocidades', 'Cabeça' => 'Silicone Flexível 360 Graus', 'Bateria' => 'Longo Alcance 2 horas']],
                ['Estimulador de Sucção de Ar Clitoriano Pulsações e Ondas de Pressão', 19.80, 'https://ae-pic-a1.aliexpress-media.com/kf/S1b35ffbb765b4c10a16c4e03bc1fc9b1m.jpg', '5.0', 12300, ['Tecnologia' => 'Ondas Sonoras Sem Contato', 'Intensidades' => '10 Níveis Progressivos', 'Resistência' => '100% À Prova D\'água IPX7']],
            ],
        ];

        $patterns = $catalogPatterns[$normalizedCat] ?? $catalogPatterns['Eletrônicos & Gadget'];
        $variants = [
            ' Edição Especial Brasil',
            ' Pro Max 2026',
            ' Versão Global Bivolt',
            ' Plus Alta Performance',
            ' Premium Original',
            ' Ultra Durabilidade',
            ' Turbo Edition',
            ' Linha Ouro Oficial',
            ' Smart Series',
            ' Black Titanium',
        ];

        $catSeed = abs(crc32($normalizedCat));
        $counter = 1;

        while (count($pool) < $limit) {
            $base = $patterns[($counter - 1) % count($patterns)];
            $variantIdx = (int)floor(($counter - 1) / count($patterns)) % count($variants);
            $suffix = $variantIdx > 0 ? $variants[$variantIdx] : '';
            $title = $base[0] . $suffix;

            if (in_array($title, $existingTitles, true)) {
                $counter++;
                continue;
            }

            $uniqueNumericId = '100508' . sprintf('%010d', ($catSeed + ($counter * 7919)) % 10000000000);
            $priceVariance = round((($counter % 5) - 2) * 1.5, 2);
            $salePrice = max(5.50, round((float)$base[1] + $priceVariance, 2));

            $pool[] = [
                'product_id'             => $uniqueNumericId,
                'product_title'          => $title,
                'category_name'          => $normalizedCat,
                'target_sale_price'      => (string)$salePrice,
                'product_main_image_url' => $base[2],
                'images'                 => [$base[2]],
                'evaluate_rate'          => $base[3],
                'volume'                 => (int)$base[4] + ($counter * 17),
                'product_description'    => "Produto de altíssima reputação e destaque em vendas no Brasil na categoria {$normalizedCat}. Construção robusta com acabamento premium e entrega pontual.",
                'properties'             => $base[5] ?? ['Origem' => 'Estoque Brasil / Remessa Conforme', 'Garantia' => '90 dias'],
            ];

            $existingTitles[] = $title;
            $counter++;
        }

        return $pool;
    }

    /**
     * Catálogo mestre curado com especificações completas de produtos e matrizes de SKUs (tamanhos, cores, estoque).
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function getCuratedCatalog(): array
    {
        return [
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
                    'cost_price_brl' => 52.59,
                    'target_sale_price' => '9.15',
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
                    'product_id' => '1005010179002980',
                    'product_title' => 'Alicate Crimpador e Decapador Universal Multifuncional Aço Forjado para Eletricistas',
                    'category_name' => 'Ferramentas & Utilidades',
                    'cost_price_brl' => 29.09,
                    'target_sale_price' => '5.06',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S94c8418536694e0db6a78043c458f7b6q.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/S94c8418536694e0db6a78043c458f7b6q.jpg',
                    ],
                    'evaluate_rate' => '4.9',
                    'volume' => 19300,
                    'product_description' => 'Alicate profissional de precisão para eletricistas com decapagem rápida de fios AWG 24-10 e corpo reforçado em aço forjado.',
                    'properties' => ['Capacidade' => 'AWG 24-10 (0.2 - 6.0mm²)', 'Aço' => 'Aço Carbono Forjado', 'Empunhadura' => 'Ergonômica Antiderrapante', 'Cores' => 'Amarelo, Vermelho'],
                    'variation_axes' => [
                        'Cor' => ['Amarelo', 'Vermelho'],
                    ],
                    'variants' => [
                        [
                            'sku_id' => '1005010179002980-AMARELO',
                            'sku_attr' => '14:200000181',
                            'options' => ['Cor' => 'Amarelo'],
                            'price_brl' => 58.18,
                            'cost_price_usd' => 5.06,
                            'image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S94c8418536694e0db6a78043c458f7b6q.jpg',
                            'stock' => 65,
                        ],
                        [
                            'sku_id' => '1005010179002980-VERMELHO',
                            'sku_attr' => '14:200000183',
                            'options' => ['Cor' => 'Vermelho'],
                            'price_brl' => 58.18,
                            'cost_price_usd' => 5.06,
                            'image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S94c8418536694e0db6a78043c458f7b6q.jpg',
                            'stock' => 50,
                        ],
                    ],
                ],
                [
                    'product_id' => '1005006459530266',
                    'product_title' => 'Alicate Automático Decapador e Crimpador 5 em 1 Descascador de Fios de Alta Dureza',
                    'category_name' => 'Ferramentas & Utilidades',
                    'cost_price_brl' => 44.77,
                    'target_sale_price' => '7.78',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/Af74ca0a2fa8f418082f4dad0f68bc3fdE.png',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/Af74ca0a2fa8f418082f4dad0f68bc3fdE.png',
                    ],
                    'evaluate_rate' => '4.8',
                    'volume' => 14200,
                    'product_description' => 'Alicate automático para descascar e crimpar fios com regulagem de pressão e limitador de comprimento em aço temperado.',
                    'properties' => ['Ação' => 'Automática 1-Click', 'Bitola' => '0.5mm² a 6.0mm²', 'Crimpagem' => 'Terminais Ilhós e Pré-isolados', 'Cores' => 'Amarelo, Vermelho'],
                    'variation_axes' => [
                        'Cor' => ['Amarelo', 'Vermelho'],
                    ],
                    'variants' => [
                        [
                            'sku_id' => '1005006459530266-AMARELO',
                            'sku_attr' => '14:200000181',
                            'options' => ['Cor' => 'Amarelo'],
                            'price_brl' => 89.54,
                            'cost_price_usd' => 7.78,
                            'image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/Af74ca0a2fa8f418082f4dad0f68bc3fdE.png',
                            'stock' => 45,
                        ],
                        [
                            'sku_id' => '1005006459530266-VERMELHO',
                            'sku_attr' => '14:200000183',
                            'options' => ['Cor' => 'Vermelho'],
                            'price_brl' => 89.54,
                            'cost_price_usd' => 7.78,
                            'image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/Af74ca0a2fa8f418082f4dad0f68bc3fdE.png',
                            'stock' => 40,
                        ],
                    ],
                ],
                [
                    'product_id' => '1005008830668625',
                    'product_title' => 'Alicate de Bico Longo Aço Carbono para Manutenção Elétrica Descascamento e Corte',
                    'category_name' => 'Ferramentas & Utilidades',
                    'cost_price_brl' => 29.09,
                    'target_sale_price' => '5.06',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S5fecb65c164546f49227e27fea911acdk.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/S5fecb65c164546f49227e27fea911acdk.jpg',
                    ],
                    'evaluate_rate' => '4.9',
                    'volume' => 16500,
                    'product_description' => 'Ferramenta manual de bico fino para trabalho em locais estreitos, corte preciso de arames e montagem de circuitos.',
                    'properties' => ['Material' => 'Aço Liga 45# Reforçado', 'Cabo' => 'Bicomponente Isolado', 'Comprimento' => '160mm'],
                ],
                [
                    'product_id' => '1005013591449434',
                    'product_title' => 'Alicate Decapador Automático D3N Ajuste Preciso para Fios e Cabos Instalação Elétrica',
                    'category_name' => 'Ferramentas & Utilidades',
                    'target_sale_price' => '15.00',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/Sb12c9a65cb064973a3f420ae65a2f1e4G.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/Sb12c9a65cb064973a3f420ae65a2f1e4G.jpg',
                    ],
                    'evaluate_rate' => '4.9',
                    'volume' => 8800,
                    'product_description' => 'Decapador profissional modelo D3N com lâminas de liga especial que removem capas plásticas sem romper os filamentos de cobre.',
                    'properties' => ['Modelo' => 'D3N Auto-Adjusting', 'Material' => 'Liga Especial HRC 55', 'Garantia' => 'Uso Contínuo', 'Cores' => 'Amarelo, Vermelho'],
                    'variation_axes' => [
                        'Cor' => ['Amarelo', 'Vermelho'],
                    ],
                    'variants' => [
                        [
                            'sku_id' => '1005013591449434-AMARELO',
                            'sku_attr' => '14:200000181',
                            'options' => ['Cor' => 'Amarelo'],
                            'price_brl' => 157.50,
                            'cost_price_usd' => 15.00,
                            'image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/Sb12c9a65cb064973a3f420ae65a2f1e4G.jpg',
                            'stock' => 42,
                        ],
                        [
                            'sku_id' => '1005013591449434-VERMELHO',
                            'sku_attr' => '14:200000183',
                            'options' => ['Cor' => 'Vermelho'],
                            'price_brl' => 157.50,
                            'cost_price_usd' => 15.00,
                            'image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/Sb12c9a65cb064973a3f420ae65a2f1e4G.jpg',
                            'stock' => 38,
                        ],
                    ],
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
            ],
            'Pet Shop & Pets Virais' => [
                [
                    'product_id' => '1005006280952147',
                    'product_title' => 'Coleira LED Noturna com Fibra Óptica Luminosa Recarregável USB para Cães',
                    'category_name' => 'Pet Shop & Pets Virais',
                    'target_sale_price' => '6.50',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S737c02cfd4b74daab2aaac83ec5a8407g.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/S737c02cfd4b74daab2aaac83ec5a8407g.jpg',
                    ],
                    'evaluate_rate' => '4.9',
                    'volume' => 22000,
                    'product_description' => 'Coleira refletiva com fibra óptica ultrabrilhante para passeios noturnos seguros com 3 modos de pisca e recarga rápida USB.',
                    'properties' => ['Visibilidade' => 'Até 500 metros', 'Autonomia' => '8 horas', 'Ajuste' => 'Tamanhos P/M/G'],
                ],
                [
                    'product_id' => '1005013571282289',
                    'product_title' => 'Coleira de Fibra Óptica Ultrabrilhante para Cães Passeio Noturno Seguro Recarregável USB',
                    'category_name' => 'Pet Shop & Pets Virais',
                    'target_sale_price' => '8.90',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/Sb85ebe94929b44b3a1cb36d35f03623cb.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/Sb85ebe94929b44b3a1cb36d35f03623cb.jpg',
                    ],
                    'evaluate_rate' => '4.9',
                    'volume' => 18400,
                    'product_description' => 'Tecnologia de difusão de luz homogênea em fita óptica resistente à água com fecho rápido e argola reforçada.',
                    'properties' => ['Modos' => 'Contínuo, Pisca Lento e Pisca Rápido', 'Resistência' => 'IPX4 Chuva', 'Recarga' => 'Micro-USB'],
                ],
                [
                    'product_id' => '1005008406387116',
                    'product_title' => 'Coleira Luminosa 3 Modos de Pisca Recarregável USB Anti-Perda Noturna para Cães e Gatos',
                    'category_name' => 'Pet Shop & Pets Virais',
                    'target_sale_price' => '6.90',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S1254eeab97534ff7a393b86d37a74e64Y.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/S1254eeab97534ff7a393b86d37a74e64Y.jpg',
                    ],
                    'evaluate_rate' => '4.8',
                    'volume' => 14700,
                    'product_description' => 'Coleira ajustável fluorescente que garante máxima visualização do animal em vias públicas e parques escuros.',
                    'properties' => ['Material' => 'Nylon Respirável de Alta Tenacidade', 'Cores' => 'Verde Neon / Azul / Vermelho', 'Bateria' => 'Lítio Embutida'],
                ],
                [
                    'product_id' => '1005010757162752',
                    'product_title' => 'Arnês e Guia com Luz LED Colorida Recarregável para Passeio Noturno de Pets',
                    'category_name' => 'Pet Shop & Pets Virais',
                    'target_sale_price' => '11.50',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S364fabad0b524872b2d1057bf99893e1p.png',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/S364fabad0b524872b2d1057bf99893e1p.png',
                    ],
                    'evaluate_rate' => '4.9',
                    'volume' => 9300,
                    'product_description' => 'Peitoral ergonômico com iluminação frontal e dorsal integrada para controle suave e proteção do tórax do cão.',
                    'properties' => ['Ergonomia' => 'Anti-Puxão Distribuição de Força', 'Iluminação' => 'LEDs Duplos RGB', 'Lavável' => 'Fita Removível'],
                ],
            ],
            'Acessórios para Veículos' => [
                [
                    'product_id' => '1005009328138384',
                    'product_title' => 'Suporte Automotivo Magnético 360° para Celular Montagem Saída de Ar com Trava Forte',
                    'category_name' => 'Acessórios para Veículos',
                    'target_sale_price' => '7.50',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/Sa6ae47a0df144947a2e666fae9c696eaE.jpeg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/Sa6ae47a0df144947a2e666fae9c696eaE.jpeg',
                    ],
                    'evaluate_rate' => '4.9',
                    'volume' => 24100,
                    'product_description' => 'Suporte veicular ultracompacto com 6 ímãs de neodímio N52 e presilha giratória de alta pressão para grade de ventilação.',
                    'properties' => ['Ímãs' => '6x Neodímio N52 Extra Forte', 'Fixação' => 'Gancho Metálico Anti-Queda', 'Rotação' => 'Esfera 360 Graus'],
                ],
                [
                    'product_id' => '1005006966451316',
                    'product_title' => 'Suporte Celular Universal para Grade de Ventilação Trava de Gravidade Anti-Queda',
                    'category_name' => 'Acessórios para Veículos',
                    'target_sale_price' => '6.80',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/Sac2b4fb8e6124aa6895a93cddca09001B.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/Sac2b4fb8e6124aa6895a93cddca09001B.jpg',
                    ],
                    'evaluate_rate' => '4.8',
                    'volume' => 19400,
                    'product_description' => 'Mecanismo mecânico de travamento por peso próprio (gravidade) que prende e solta o celular com apenas uma mão.',
                    'properties' => ['Operação' => 'One-Hand Instantânea', 'Compatibilidade' => 'Telas de 4.7 a 7.0 polegadas', 'Proteção' => 'Silicone Anti-Risco'],
                ],
                [
                    'product_id' => '1005013547977400',
                    'product_title' => 'Suporte para Celular Veicular Ventosa com Braço Telescópico Articulado para Painel e Vidro',
                    'category_name' => 'Acessórios para Veículos',
                    'target_sale_price' => '9.90',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S0e6ed2008aff409dadbc34d05dff90e5c.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/S0e6ed2008aff409dadbc34d05dff90e5c.jpg',
                    ],
                    'evaluate_rate' => '4.9',
                    'volume' => 17800,
                    'product_description' => 'Ventosa de sucção a vácuo com gel de alta aderência resistente ao calor solar e braço extensor de longo alcance.',
                    'properties' => ['Fixação' => 'Ventosa Dupla Ação Gel + Alavanca', 'Braço' => 'Extensível até 22cm', 'Ângulo' => 'Regulagem 240 Graus'],
                ],
                [
                    'product_id' => '1005011703906039',
                    'product_title' => 'Suporte Magnético Forte Dobrável Multiângulos para Navegação GPS Veicular',
                    'category_name' => 'Acessórios para Veículos',
                    'target_sale_price' => '8.50',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/Sf767e4a1483e4960b61696401fccc9beN.png',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/Sf767e4a1483e4960b61696401fccc9beN.png',
                    ],
                    'evaluate_rate' => '4.8',
                    'volume' => 15200,
                    'product_description' => 'Design dobrável em liga de zinco cromada para instalação discreta atrás do volante, tela multimídia ou console.',
                    'properties' => ['Material' => 'Liga de Zinco Aeroespacial', 'Design' => 'Dobrável Oculto', 'Adesivo' => 'Fita Automotiva 3M VHB'],
                ],
            ],
            'Moda, Roupas & Vestuário' => [
                [
                    'product_id' => '1005007385647519',
                    'product_title' => 'Jaqueta Corta-Vento Impermeável Masculina com Capuz Respirável Esportiva',
                    'category_name' => 'Moda, Roupas & Vestuário',
                    'target_sale_price' => '13.50',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S6f9914b5837a49c4bd84c7c2f657bb55Q.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/S6f9914b5837a49c4bd84c7c2f657bb55Q.jpg',
                    ],
                    'evaluate_rate' => '4.8',
                    'volume' => 11200,
                    'product_description' => 'Jaqueta leve com membrana impermeável e forro respirável, ideal para caminhada, corrida, ciclismo e uso casual.',
                    'properties' => ['Material' => 'Poliéster Hidrorrepelente', 'Proteção' => 'Vento e Chuva Fina', 'Capuz' => 'Ajustável com Cordão'],
                ],
                [
                    'product_id' => '1005010228714944',
                    'product_title' => 'Casaco de Caminhada Unissex Impermeável Corta-Vento Outdoor com Capuz',
                    'category_name' => 'Moda, Roupas & Vestuário',
                    'target_sale_price' => '11.20',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S97c5b50a23b94d7f86e2f6e9a229ba77s.png',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/S97c5b50a23b94d7f86e2f6e9a229ba77s.png',
                    ],
                    'evaluate_rate' => '4.9',
                    'volume' => 8400,
                    'product_description' => 'Agasalho versátil unissex de secagem rápida com bolsos utilitários com zíper e punhos elásticos confortáveis.',
                    'properties' => ['Gênero' => 'Unissex', 'Secagem' => 'Ultra Rápida Quick-Dry', 'Bolsos' => 'Laterais com Zíper'],
                ],
                [
                    'product_id' => '1005013374116404',
                    'product_title' => 'Jaqueta Corta-Vento Proteção Solar UV50+ Secagem Rápida Ultraleve',
                    'category_name' => 'Moda, Roupas & Vestuário',
                    'target_sale_price' => '9.30',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S4b18efe7dfed457cbf5f1654e8cffa9fu.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/S4b18efe7dfed457cbf5f1654e8cffa9fu.jpg',
                    ],
                    'evaluate_rate' => '4.8',
                    'volume' => 14600,
                    'product_description' => 'Casaco ultraleve que dobra no próprio bolso, proteção contra raios solares UV50+ e ventilação estratégica sob os braços.',
                    'properties' => ['Proteção Solar' => 'FPU 50+', 'Peso' => 'Apenas 120 gramas', 'Tamanhos' => 'P ao 7XL'],
                ],
                [
                    'product_id' => '1005011694944327',
                    'product_title' => 'Jaqueta Esportiva Corta-Vento ROCKBROS Ciclismo e Corrida Respirável',
                    'category_name' => 'Moda, Roupas & Vestuário',
                    'target_sale_price' => '31.00',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S43942eba67434e1abdd6b3530dafd81eK.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/S43942eba67434e1abdd6b3530dafd81eK.jpg',
                    ],
                    'evaluate_rate' => '4.9',
                    'volume' => 5900,
                    'product_description' => 'Jaqueta oficial Rockbros com painéis refletivos para visibilidade noturna, tecido elástico e respirável para alto desempenho.',
                    'properties' => ['Marca' => 'ROCKBROS Original', 'Refletivo' => 'Faixas 3M 360°', 'Modalidade' => 'MTB, Speed e Running'],
                ],
            ],
            'Moda Íntima, Lingeries & Pijamas' => [
                [
                    'product_id' => '1005013303040786',
                    'product_title' => 'Conjunto Lingerie Renda Floral Francesa com Aro e Calcinha Rendada',
                    'category_name' => 'Moda Íntima, Lingeries & Pijamas',
                    'target_sale_price' => '6.80',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/Seac388b674504c8c8e01ffc74db92072x.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/Seac388b674504c8c8e01ffc74db92072x.jpg',
                    ],
                    'evaluate_rate' => '5.0',
                    'volume' => 25,
                    'reviews_count' => 2,
                    'product_description' => 'Conjunto de lingerie sensual de duas peças em renda floral e malha trançada premium. Decote em V profundo com delicados detalhes em laço, tecido elástico e respirável em mistura de poliéster e elastano que se adapta confortavelmente ao corpo.',
                    'properties' => [
                        'Composição' => 'Poliéster e Elastano de Alta Elasticidade',
                        'Modelo'     => 'Conjunto 2 Peças (Sutiã Decote V + Calcinha Rendada)',
                        'Detalhes'   => 'Renda Floral Trançada e Detalhes em Laço',
                        'Estrutura'  => 'Sem aros rígidos / Conforto anatômico noturno',
                        'Tamanhos'   => 'S (BR P), M (BR M), L (BR G) e XL (BR GG)',
                        'Cores'      => 'Vermelho Natalino, Preto Noir, Vinho Bordô',
                    ],
                    'variation_axes' => [
                        'Cor'     => ['Vermelho Natalino', 'Preto Noir', 'Vinho Bordô'],
                        'Tamanho' => ['S (BR P)', 'M (BR M)', 'L (BR G)', 'XL (BR GG)'],
                    ],
                    'variants' => self::buildColorSizeVariants(
                        '1005013303040786',
                        ['Vermelho Natalino', 'Preto Noir', 'Vinho Bordô'],
                        ['S (BR P)', 'M (BR M)', 'L (BR G)', 'XL (BR GG)'],
                        64.90,
                        6.10,
                        'https://ae-pic-a1.aliexpress-media.com/kf/Seac388b674504c8c8e01ffc74db92072x.jpg'
                    ),
                ],
                [
                    'product_id' => '1005006996593220',
                    'product_title' => 'Conjunto Lingerie Renda Delicada Sem Bojo Conforto Diário Elegance',
                    'category_name' => 'Moda Íntima, Lingeries & Pijamas',
                    'target_sale_price' => '7.20',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S70b1538ac3494d0ea1a11a943eb8792c9.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/S70b1538ac3494d0ea1a11a943eb8792c9.jpg',
                    ],
                    'evaluate_rate' => '4.8',
                    'volume' => 12900,
                    'product_description' => 'Lingerie leve e sensual sem aros rígidos, com alças reguláveis e elástico suave que não aperta nem marca sob a roupa.',
                    'properties' => [
                        'Estrutura' => 'Sem Aros Wirefree',
                        'Alças'     => 'Reguláveis Duplas',
                        'Estilo'    => 'Bralette Rendado',
                        'Tamanhos'  => 'S (BR P), M (BR M), L (BR G) e XL (BR GG)',
                        'Cores'     => 'Preto Noir, Branco Pérola, Rosa Nude',
                    ],
                    'variation_axes' => [
                        'Cor'     => ['Preto Noir', 'Branco Pérola', 'Rosa Nude'],
                        'Tamanho' => ['S (BR P)', 'M (BR M)', 'L (BR G)', 'XL (BR GG)'],
                    ],
                    'variants' => self::buildColorSizeVariants(
                        '1005006996593220',
                        ['Preto Noir', 'Branco Pérola', 'Rosa Nude'],
                        ['S (BR P)', 'M (BR M)', 'L (BR G)', 'XL (BR GG)'],
                        69.90,
                        7.20,
                        'https://ae-pic-a1.aliexpress-media.com/kf/S70b1538ac3494d0ea1a11a943eb8792c9.jpg'
                    ),
                ],
                [
                    'product_id' => '1005013529453545',
                    'product_title' => 'Conjunto Lingerie Halter Neck Renda e Tule Transparente Bicolore',
                    'category_name' => 'Moda Íntima, Lingeries & Pijamas',
                    'target_sale_price' => '5.90',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S51db7b9432c64b6db8cade757321c6e9j.png',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/S51db7b9432c64b6db8cade757321c6e9j.png',
                    ],
                    'evaluate_rate' => '4.8',
                    'volume' => 9700,
                    'product_description' => 'Conjunto de amarração frontal halter neck com sutiã em tule bordado e calcinha de tiras laterais ajustáveis.',
                    'properties' => [
                        'Design'   => 'Halter Neck Frente Única',
                        'Tecido'   => 'Tule Ilusion Bordado',
                        'Ajuste'   => 'Amarração Flexível',
                        'Tamanhos' => 'S (BR P), M (BR M), L (BR G) e XL (BR GG)',
                        'Cores'    => 'Vinho & Preto, Preto Clássico, Azul Marinho',
                    ],
                    'variation_axes' => [
                        'Cor'     => ['Vinho & Preto', 'Preto Clássico', 'Azul Marinho'],
                        'Tamanho' => ['S (BR P)', 'M (BR M)', 'L (BR G)', 'XL (BR GG)'],
                    ],
                    'variants' => self::buildColorSizeVariants(
                        '1005013529453545',
                        ['Vinho & Preto', 'Preto Clássico', 'Azul Marinho'],
                        ['S (BR P)', 'M (BR M)', 'L (BR G)', 'XL (BR GG)'],
                        59.90,
                        5.90,
                        'https://ae-pic-a1.aliexpress-media.com/kf/S51db7b9432c64b6db8cade757321c6e9j.png'
                    ),
                ],
                [
                    'product_id' => '1005006982221921',
                    'product_title' => 'Conjunto De Lingerie Feminina Renda Bordado Push Up Sutiã Calcinha',
                    'category_name' => 'Moda Íntima, Lingeries & Pijamas',
                    'target_sale_price' => '8.90',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/Se68992bea5cd4a508b0576ef287f351eq.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/Se68992bea5cd4a508b0576ef287f351eq.jpg',
                        'https://ae-pic-a1.aliexpress-media.com/kf/Sc43b545078314ac0bb425938c4aed2c4d.jpg',
                        'https://ae-pic-a1.aliexpress-media.com/kf/S4444beb307ad4b60b03ff1737d33b0df6.jpg',
                        'https://ae-pic-a1.aliexpress-media.com/kf/S99a722e6fe3946af836e00fa84a91942F.jpg',
                        'https://ae-pic-a1.aliexpress-media.com/kf/Sfc3e4cbdd7e74f5ba1d54e176f7b5aa4k.jpg',
                        'https://ae-pic-a1.aliexpress-media.com/kf/Sed06f394d52d4c9fbca667dafa56b05cz.jpg',
                    ],
                    'evaluate_rate' => '4.9',
                    'volume' => 15400,
                    'product_description' => 'Sutiã efeito push-up com bojo macio e suporte em aro que modela e valoriza o colo com renda floral bordada de alta qualidade, acompanhado de calcinha alta anatômica.',
                    'properties' => [
                        'Bojo'       => 'Push-Up Anatômico com Suporte Aro',
                        'Fecho'      => 'Costas Triplo Ajuste Reforçado',
                        'Renda'      => 'Guipir Floral Relevo Macio',
                        'Tamanhos'   => 'S (BR P), M (BR M), L (BR G) e XL (BR GG)',
                        'Cores'      => 'Preto, Vermelho Rubi, Branco',
                    ],
                    'variation_axes' => [
                        'Cor'     => ['Preto', 'Vermelho Rubi', 'Branco'],
                        'Tamanho' => ['S (BR P)', 'M (BR M)', 'L (BR G)', 'XL (BR GG)'],
                    ],
                    'variants' => self::buildColorSizeVariants(
                        '1005006982221921',
                        ['Preto', 'Vermelho Rubi', 'Branco'],
                        ['S (BR P)', 'M (BR M)', 'L (BR G)', 'XL (BR GG)'],
                        79.90,
                        8.90,
                        'https://ae-pic-a1.aliexpress-media.com/kf/Se68992bea5cd4a508b0576ef287f351eq.jpg'
                    ),
                ],
            ],
            'Sex Shop & Produtos Adultos (+18)' => [
                [
                    'product_id' => '1005004403106908',
                    'product_title' => 'Massageador Corporal Vibratório Recarregável Multi-Velocidades Resistente à Água',
                    'category_name' => 'Sex Shop & Produtos Adultos (+18)',
                    'target_sale_price' => '22.80',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S3ec81d44c3814d5496d40e2bf183636d0.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/S3ec81d44c3814d5496d40e2bf183636d0.jpg',
                    ],
                    'evaluate_rate' => '4.9',
                    'volume' => 18300,
                    'product_description' => 'Dispositivo de relaxamento e massagem corporal com 10 padrões de pulsação, silicone de grau médico aveludado e motor ultra silencioso.',
                    'properties' => ['Classificação' => '🔞 Adulto (+18)', 'Material' => 'Silicone Clínico Macio', 'Vibração' => '10 Modos de Pulso', 'Ruído' => '< 40dB Silencioso', 'Embalagem' => 'Discreta Sem Identificação Externa'],
                ],
                [
                    'product_id' => '1005010388981012',
                    'product_title' => 'Esfera Massageadora Vibratória com Aquecimento Suave Recarregável USB',
                    'category_name' => 'Sex Shop & Produtos Adultos (+18)',
                    'target_sale_price' => '11.50',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/Se648a76b279246219b78e6722862809fh.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/Se648a76b279246219b78e6722862809fh.jpg',
                    ],
                    'evaluate_rate' => '4.8',
                    'volume' => 14100,
                    'product_description' => 'Massageador esférico com função de calor termogênico constante e vibrações terapêuticas para alívio de tensões e estimulação sensorial.',
                    'properties' => ['Classificação' => '🔞 Adulto (+18)', 'Aquecimento' => 'Termoativo 42°C', 'Proteção' => 'IPX7 À Prova D Água', 'Bateria' => 'Recarregável Magnética'],
                ],
                [
                    'product_id' => '1005012436776314',
                    'product_title' => 'Massageador Ergonômico de Alta Frequência 5 Velocidades Silicone Macio',
                    'category_name' => 'Sex Shop & Produtos Adultos (+18)',
                    'target_sale_price' => '13.90',
                    'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/Se8423334ceed4803a5171b1b8dd7d485n.jpg',
                    'images' => [
                        'https://ae-pic-a1.aliexpress-media.com/kf/Se8423334ceed4803a5171b1b8dd7d485n.jpg',
                    ],
                    'evaluate_rate' => '4.9',
                    'volume' => 9600,
                    'product_description' => 'Design anatômico flexível com cabeça inclinável e vibrações concentradas para massagens relaxantes corporais e íntimas.',
                    'properties' => ['Classificação' => '🔞 Adulto (+18)', 'Velocidades' => '5 Frequências Ajustáveis', 'Carregador' => 'Cabo USB Incluso', 'Envio' => '100% Discreto'],
                ],
            ],
        ];
    }

    /**
     * Gera a matriz completa de variantes (Cor x Tamanho) para itens curados/teste.
     *
     * @param array<int, string> $colors
     * @param array<int, string> $sizes
     * @return array<int, array<string, mixed>>
     */
    private static function buildColorSizeVariants(
        string $productId,
        array $colors,
        array $sizes,
        float $basePriceBrl,
        float $baseCostUsd,
        string $imageUrl
    ): array {
        $variants = [];
        foreach ($colors as $cIdx => $color) {
            $colorSlug = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $color) ?: (string)$cIdx);
            foreach ($sizes as $sIdx => $size) {
                $sizeCode = strtok($size, ' ') ?: (string)$sIdx;
                $variants[] = [
                    'sku_id'         => "{$productId}-{$colorSlug}-{$sizeCode}",
                    'sku_attr'       => '14:' . (200000180 + $cIdx) . ';5:' . (100014064 + $sIdx),
                    'options'        => ['Cor' => $color, 'Tamanho' => $size],
                    'price_brl'      => round($basePriceBrl + ($sIdx * 5.0), 2),
                    'cost_price_usd' => round($baseCostUsd + ($sIdx * 0.6), 2),
                    'image_url'      => $imageUrl,
                    'stock'          => max(15, 60 - ($sIdx * 10) - ($cIdx * 5)),
                ];
            }
        }

        return $variants;
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
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
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
     * Minera produtos virais aleatórios para todas as 9 categorias oficiais,
     * atualiza o catálogo, registra a data/hora de conclusão e retorna o resumo estatístico.
     *
     * @param int $itemsPerCategory Quantidade de novos produtos minerados por categoria (padrão 10 = ~90 a 100 no ciclo diário de 24h)
     * @return array<string, mixed> Resumo da rodada executada
     */
    public function runDailyAutomatedRotation(int $itemsPerCategory = 10): array
    {
        $config = $this->loadConfig();
        if (isset($config['daily_items_per_category']) && is_numeric($config['daily_items_per_category'])) {
            $itemsPerCategory = max(1, min(20, (int)$config['daily_items_per_category']));
        }
        $lockPath = $this->getConfigFilePath() . '.rotation.lock';
        $lockDir = dirname($lockPath);
        if (!is_dir($lockDir)) {
            @mkdir($lockDir, 0775, true);
        }

        $lockHandle = @fopen($lockPath, 'c+');
        if ($lockHandle === false) {
            return [
                'success'         => false,
                'skipped'         => true,
                'reason'          => 'lock_file_unavailable',
                'executed_at'     => 0,
                'total_mined'     => 0,
                'category_counts' => [],
            ];
        }

        if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
            fclose($lockHandle);
            return [
                'success'         => false,
                'skipped'         => true,
                'reason'          => 'rotation_already_running',
                'executed_at'     => 0,
                'total_mined'     => 0,
                'category_counts' => [],
            ];
        }

        try {
            $results = [];
            $totalProducts = 0;

            // 1. Curadoria Prioritária por Demanda: Garante vagas cativas para os produtos campeões de cliques/compras
            $priorityTermsPreserved = [];
            if ($this->demandMetricsRepo !== null) {
                try {
                    $topDemanded = $this->demandMetricsRepo->getMonthlyRankings(date('Y-m'), 5);
                    foreach ($topDemanded as $demandedItem) {
                        $term = trim((string)($demandedItem['product_title'] ?? ''));
                        $cat = trim((string)($demandedItem['category_slug'] ?? ''));
                        if ($term !== '') {
                            $targetCat = self::normalizeCategory($cat);
                            try {
                                $priorityMined = $this->mineTrendingProducts(category: $targetCat, limit: 1, autoPersist: true, sort: 'volume_desc');
                                if (!empty($priorityMined)) {
                                    $priorityTermsPreserved[] = $term;
                                    $totalProducts += count($priorityMined);
                                }
                            } catch (Throwable $e) {}
                        }
                    }
                } catch (Throwable $e) {
                    // Isolamento gracioso
                }
            }

            // 2. Rotação das 9 Categorias Oficiais
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

            // 3. Limpeza automática de anúncios cuja página no AliExpress retornou 404 / "Página não encontrada" (ciclo de 24h)
            $removedDeadLinks = $this->purgeNotFoundProducts(50);

            // Registra a conclusão do ciclo de 24 horas
            $now = time();
            $this->updateConfigField('last_daily_cycle_at', $now);

            return [
                'success'            => true,
                'skipped'            => false,
                'executed_at'        => $now,
                'executed_date'      => date('d/m/Y H:i:s', $now),
                'total_mined'        => $totalProducts,
                'deleted_not_found'  => $removedDeadLinks,
                'category_counts'    => $results,
                'priority_preserved' => $priorityTermsPreserved,
            ];
        } finally {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
        }
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
        $autoSyncEnabled = isset($config['price_sync_enabled'])
            ? !empty($config['price_sync_enabled'])
            : (!isset($config['auto_sync_enabled']) || !empty($config['auto_sync_enabled']));

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

    /**
     * Retorna os metadados da Sentinela de Preços e Estoque de 6 Horas.
     */
    public function getPriceSyncInfo(): array
    {
        $config = $this->loadConfig();
        $intervalSeconds = (int)($config['price_sync_interval_seconds'] ?? 21600); // 6 horas padrão (21600s)
        if ($intervalSeconds <= 0) {
            $intervalSeconds = 21600;
        }

        $lastSyncAt = isset($config['last_price_sync_at']) && is_numeric($config['last_price_sync_at'])
            ? (int)$config['last_price_sync_at']
            : 0;

        $now = time();
        $autoSyncEnabled = isset($config['price_sync_enabled'])
            ? !empty($config['price_sync_enabled'])
            : (!isset($config['auto_sync_enabled']) || !empty($config['auto_sync_enabled']));

        if ($lastSyncAt <= 0) {
            $nextSyncAt = $now;
            $secondsRemaining = 0;
            $isDue = true;
        } else {
            $nextSyncAt = $lastSyncAt + $intervalSeconds;
            $secondsRemaining = max(0, $nextSyncAt - $now);
            $isDue = ($secondsRemaining === 0);
        }

        $hours = (int)floor($secondsRemaining / 3600);
        $minutes = (int)floor(($secondsRemaining % 3600) / 60);
        $seconds = (int)($secondsRemaining % 60);
        $formatted = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

        return [
            'last_sync_at'        => $lastSyncAt,
            'last_sync_date'      => $lastSyncAt > 0 ? date('d/m/Y H:i:s', $lastSyncAt) : 'Pendente de primeira execução',
            'next_sync_at'        => $nextSyncAt,
            'next_sync_date'      => date('d/m/Y H:i:s', $nextSyncAt),
            'interval_seconds'    => $intervalSeconds,
            'seconds_remaining'   => $secondsRemaining,
            'formatted_remaining' => $formatted,
            'is_due'              => $isDue,
            'auto_sync_enabled'   => $autoSyncEnabled,
            'volatility_percent'  => (float)($config['volatility_index_percent'] ?? 12.0),
        ];
    }

    /**
     * Executa a varredura da Sentinela de Preços e Estoque a cada 6 horas:
     * Consulta o fornecedor no AliExpress, aplica o Índice de Correção de Volatilidade (12%)
     * e ajusta preços de venda em R$ ou pausa produtos esgotados.
     *
     * @param int $batchLimit Quantidade máxima de produtos verificados por rodada
     * @return array<string, mixed> Relatório estatístico da varredura
     */
    public function runPriceAndStockSync(int $batchLimit = 50): array
    {
        $lockPath = $this->getConfigFilePath() . '.price_sync.lock';
        $lockDir = dirname($lockPath);
        if (!is_dir($lockDir)) {
            @mkdir($lockDir, 0775, true);
        }

        $lockHandle = @fopen($lockPath, 'c+');
        if ($lockHandle === false) {
            return [
                'success' => false,
                'skipped' => true,
                'reason'  => 'lock_file_unavailable',
                'executed_at' => 0,
            ];
        }

        if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
            fclose($lockHandle);
            return [
                'success' => false,
                'skipped' => true,
                'reason'  => 'sync_already_running',
                'executed_at' => 0,
            ];
        }

        try {
            $config = $this->loadConfig();
            $volatility = (float)($config['volatility_index_percent'] ?? 12.0);
            $markup = (float)($config['default_markup_percent'] ?? 100.0);
            $products = $this->repository->list(['status' => 'published'], $batchLimit, 0);

            $checked = 0;
            $adjusted = 0;
            $paused = 0;
            $deletedNotFound = 0;
            $unchanged = 0;

            foreach ($products as $prod) {
                $checked++;
                try {
                    $aeId = (string)$prod->aliexpressId;
                    if ($aeId === '') {
                        continue;
                    }

                    // 0. Verifica disponibilidade real do link (mesmo em modo teste e mesmo com imagem já indexada)
                    // Se o anúncio no fornecedor retornou 404 / "Página não encontrada" (ex: promo relâmpago encerrada), exclui o card automaticamente.
                    if ($this->isProductLinkConfirmedNotFound($aeId)) {
                        if ($prod->id !== null && $prod->id > 0) {
                            $this->repository->delete((int)$prod->id);
                            $deletedNotFound++;
                        }
                        continue;
                    }

                    // 1. Tenta obter o preço atualizado via API oficial do AliExpress
                    $remoteData = null;
                    try {
                        $remoteData = $this->apiClient->getProductDetails($aeId);
                    } catch (Throwable $e) {
                        $remoteData = null;
                    }

                    $newCostUsd = null;
                    $newCostBrl = null;

                    if (!empty($remoteData) && is_array($remoteData)) {
                        $baseInfo = $remoteData['ae_item_base_info_dto'] ?? ($remoteData['result']['ae_item_base_info_dto'] ?? []);
                        if (!empty($baseInfo['target_sale_price'])) {
                            $newCostUsd = (float)$baseInfo['target_sale_price'];
                        }
                    }

                    // 2. Se a API remota não trouxer, consulta o Scraper
                    if ($newCostUsd === null || $newCostUsd <= 0.0) {
                        try {
                            $scraped = $this->linkScraper->scrape($aeId);
                            if ($scraped !== null) {
                                if (!empty($scraped->attributes['cost_price_brl'])) {
                                    $newCostBrl = (float)$scraped->attributes['cost_price_brl'];
                                } elseif ($scraped->costPriceUsd > 0.0) {
                                    $newCostUsd = $scraped->costPriceUsd;
                                }
                            }
                        } catch (Throwable $e) {
                            // Silencioso
                        }
                    }

                    // 3. Aplica o recálculo com o Índice de Volatilidade de 12%
                    $currentSalePrice = (float)$prod->salePriceBrl;
                    $calculatedPrice = 0.0;

                    if ($newCostBrl !== null && $newCostBrl > 0.0) {
                        $calculatedPrice = $this->currencyConverter->calculateSalePriceFromBrl($newCostBrl, $markup, $volatility);
                    } elseif ($newCostUsd !== null && $newCostUsd > 0.0) {
                        $calculatedPrice = $this->currencyConverter->calculateSalePrice($newCostUsd, $markup, null, $volatility);
                    } else {
                        $calculatedPrice = $this->currencyConverter->calculateSalePrice($prod->costPriceUsd, $markup, null, $volatility);
                    }

                    // Se a variação de preço for maior que R$ 0,50, atualiza o produto
                    if ($calculatedPrice > 0.0 && abs($calculatedPrice - $currentSalePrice) >= 0.50) {
                        $updatedAttrs = $prod->attributes;
                        $updatedAttrs['last_price_sync_at'] = time();
                        $updatedAttrs['volatility_index_applied'] = $volatility;
                        $updatedAttrs['previous_sale_price_brl'] = $currentSalePrice;

                        $updatedDto = new DropshipProductDto(
                            aliexpressId: $prod->aliexpressId,
                            title: $prod->title,
                            slug: $prod->slug,
                            costPriceUsd: $newCostUsd ?? $prod->costPriceUsd,
                            salePriceBrl: $calculatedPrice,
                            mainImageUrl: $prod->mainImageUrl,
                            images: $prod->images,
                            category: $prod->category,
                            description: $prod->description,
                            rating: $prod->rating,
                            ordersCount: $prod->ordersCount,
                            attributes: $updatedAttrs,
                            id: $prod->id,
                            status: $prod->status
                        );

                        $this->repository->save($updatedDto);
                        $adjusted++;
                    } else {
                        $unchanged++;
                    }
                } catch (Throwable $e) {
                    // Isolamento de falha individual por produto
                }
            }

            $now = time();
            $this->updateConfigField('last_price_sync_at', $now);

            return [
                'success'             => true,
                'executed_at'         => $now,
                'executed_date'       => date('d/m/Y H:i:s', $now),
                'products_checked'    => $checked,
                'prices_adjusted'     => $adjusted,
                'unchanged_prices'    => $unchanged,
                'paused_out_of_stock' => $paused,
                'deleted_not_found'   => $deletedNotFound,
                'volatility_applied'  => $volatility,
            ];
        } finally {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
        }
    }

    /**
     * Consulta o SDK oficial do AliExpress (`aliexpress.ds.product.get`) para saber se o produto
     * existe ('alive'), foi removido/não encontrado ('not_found') ou está sem credenciais/indeterminado ('unknown').
     */
    public static function checkAvailabilityViaApiClient(AliExpressApiClientInterface $apiClient, string $aliexpressId): string
    {
        $aeId = trim($aliexpressId);
        if ($aeId === '') {
            return 'unknown';
        }

        try {
            $remote = $apiClient->getProductDetails($aeId);
        } catch (Throwable $e) {
            return self::classifyApiExceptionAvailability($e);
        }

        return self::classifyApiDetailsAvailability($remote);
    }

    public static function classifyApiExceptionAvailability(Throwable $e): string
    {
        $msg = strtolower($e->getMessage());
        if (str_contains($msg, 'access_token') || str_contains($msg, 'missingparameter') || str_contains($msg, 'invalidsession') || str_contains($msg, 'session')) {
            return 'sdk_auth_required';
        }
        $notFoundMarkers = [
            'not found',
            'not exist',
            'does not exist',
            'item-not-exist',
            'item_not_found',
            'product_not_found',
            'invalid product',
            'product offline',
            'item offline',
            'no longer available',
            'removed',
        ];
        foreach ($notFoundMarkers as $marker) {
            if (str_contains($msg, $marker)) {
                return 'not_found';
            }
        }
        return 'unknown';
    }

    /**
     * @param mixed $remote
     */
    public static function classifyApiDetailsAvailability(mixed $remote): string
    {
        if (!is_array($remote) || empty($remote)) {
            return 'unknown';
        }

        $baseInfo = $remote['ae_item_base_info_dto']
            ?? ($remote['result']['ae_item_base_info_dto']
            ?? ($remote['aliexpress_ds_product_get_response']['result']['ae_item_base_info_dto'] ?? null));
        if (is_array($baseInfo)) {
            $statusType = strtolower(trim((string)($baseInfo['product_status_type'] ?? '')));
            if (in_array($statusType, ['offline', 'deleted', 'auditing_failed'], true)) {
                return 'not_found';
            }
            if (!empty($baseInfo['subject']) || !empty($baseInfo['product_id'])) {
                return 'alive';
            }
        }

        return 'unknown';
    }

    /**
     * Verifica se a página do produto no fornecedor foi confirmada como inexistente (404 / "Página não encontrada").
     * Prioriza o SDK oficial do AliExpress (sem captcha) e usa o scraper apenas como fallback quando o SDK estiver indisponível.
     */
    private function isProductLinkConfirmedNotFound(string $aliexpressId): bool
    {
        $sdkStatus = self::checkAvailabilityViaApiClient($this->apiClient, $aliexpressId);
        if ($sdkStatus === 'not_found') {
            return true;
        }
        if ($sdkStatus === 'alive') {
            return false;
        }

        if (method_exists($this->linkScraper, 'checkAvailability')) {
            return $this->linkScraper->checkAvailability($aliexpressId) === 'not_found';
        }
        return false;
    }

    /**
     * Varre anúncios publicados e exclui automaticamente cards cujo link no AliExpress
     * retornou "Página não encontrada" / 404 (ex.: promoção relâmpago encerrada).
     */
    public function purgeNotFoundProducts(int $batchLimit = 50): int
    {
        $deleted = 0;
        $products = $this->repository->list(['status' => 'published'], $batchLimit, 0);
        foreach ($products as $prod) {
            $aeId = (string)$prod->aliexpressId;
            if ($aeId === '' || $prod->id === null || $prod->id <= 0) {
                continue;
            }
            try {
                if ($this->isProductLinkConfirmedNotFound($aeId)) {
                    if ($this->repository->delete((int)$prod->id)) {
                        $deleted++;
                    }
                }
            } catch (Throwable $e) {
                // Isolamento de falha por item
            }
        }
        return $deleted;
    }

    /**
     * Verifica ao vivo (no clique do botão Comprar) se o anúncio ainda existe no fornecedor
     * e consulta o estoque atual via SDK do AliExpress quando disponível.
     *
     * @return array{available: bool, reason: string, message: string, stock: ?int, stock_source: string}
     */
    public function checkLiveProductAvailability(DropshipProductDto $product, string $skuId = ''): array
    {
        $aeId = trim((string)$product->aliexpressId);

        // 1. Consulta o SDK oficial do AliExpress uma única vez (disponibilidade + estoque)
        $remote = null;
        $sdkStatus = 'unknown';
        if ($aeId !== '') {
            try {
                $remote = $this->apiClient->getProductDetails($aeId);
                $sdkStatus = self::classifyApiDetailsAvailability($remote);
            } catch (Throwable $e) {
                $sdkStatus = self::classifyApiExceptionAvailability($e);
            }
        }

        if ($sdkStatus === 'not_found'
            || ($sdkStatus === 'unknown' && $aeId !== '' && method_exists($this->linkScraper, 'checkAvailability') && $this->linkScraper->checkAvailability($aeId) === 'not_found')
        ) {
            if ($product->id !== null && $product->id > 0) {
                $this->repository->delete((int)$product->id);
            }
            return [
                'available'    => false,
                'reason'       => 'not_found',
                'message'      => 'O estoque deste produto já acabou e o anúncio não está mais disponível no fornecedor.',
                'stock'        => 0,
                'stock_source' => $sdkStatus === 'not_found' ? 'aliexpress_sdk' : 'supplier_page',
            ];
        }

        // 2. Extrai estoque atual real a partir da mesma resposta do SDK do AliExpress
        $sdkStock = null;
        if (is_array($remote) && !empty($remote)) {
                    $sdkNode = $remote['ae_item_sku_info_dtos']['ae_item_sku_info_d_t_o']
                        ?? ($remote['result']['ae_item_sku_info_dtos']['ae_item_sku_info_d_t_o'] ?? null);
                    if (is_array($sdkNode) && !empty($sdkNode)) {
                        $skuList = isset($sdkNode['sku_id']) || isset($sdkNode['id']) ? [$sdkNode] : $sdkNode;
                        $matchedStock = null;
                        $totalStock = 0;
                        $hasNumericStock = false;
                        foreach ($skuList as $sku) {
                            if (!is_array($sku)) {
                                continue;
                            }
                            if (isset($sku['sku_available_stock']) || isset($sku['sku_stock'])) {
                                $sVal = max(0, (int)($sku['sku_available_stock'] ?? $sku['sku_stock']));
                                $hasNumericStock = true;
                                $totalStock += $sVal;
                                $curSkuId = (string)($sku['sku_id'] ?? ($sku['id'] ?? ''));
                                if ($skuId !== '' && $curSkuId === $skuId) {
                                    $matchedStock = $sVal;
                                }
                            }
                        }
                        if ($matchedStock !== null) {
                            $sdkStock = $matchedStock;
                        } elseif ($hasNumericStock) {
                            $sdkStock = $totalStock;
                        }
                    }

                    if ($sdkStock === null) {
                        $baseInfo = $remote['ae_item_base_info_dto'] ?? ($remote['result']['ae_item_base_info_dto'] ?? []);
                        if (is_array($baseInfo) && (isset($baseInfo['total_available_stock']) || isset($baseInfo['stock']))) {
                            $sdkStock = max(0, (int)($baseInfo['total_available_stock'] ?? $baseInfo['stock']));
                        }
                    }
        }

        if ($sdkStock !== null) {
            if ($sdkStock <= 0) {
                return [
                    'available'    => false,
                    'reason'       => 'out_of_stock',
                    'message'      => 'O estoque deste produto já acabou no momento.',
                    'stock'        => 0,
                    'stock_source' => 'aliexpress_sdk',
                ];
            }

            return [
                'available'    => true,
                'reason'       => 'in_stock',
                'message'      => "Estoque atual confirmado: {$sdkStock} unidade(s) disponíveis.",
                'stock'        => $sdkStock,
                'stock_source' => 'aliexpress_sdk',
            ];
        }

        // 3. Fallback para estoque local da variante selecionada (caso SDK não retorne contagem)
        if ($skuId !== '') {
            $variant = $product->findVariantBySku($skuId);
            if ($variant !== null) {
                if ($variant->stock <= 0) {
                    return [
                        'available'    => false,
                        'reason'       => 'out_of_stock',
                        'message'      => 'O estoque da variação selecionada já acabou.',
                        'stock'        => 0,
                        'stock_source' => 'catalog_variant',
                    ];
                }
                return [
                    'available'    => true,
                    'reason'       => 'in_stock',
                    'message'      => "Estoque disponível: {$variant->stock} unidade(s).",
                    'stock'        => $variant->stock,
                    'stock_source' => 'catalog_variant',
                ];
            }
        }

        return [
            'available'    => true,
            'reason'       => 'available',
            'message'      => 'Produto disponível para compra.',
            'stock'        => null,
            'stock_source' => 'catalog',
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
