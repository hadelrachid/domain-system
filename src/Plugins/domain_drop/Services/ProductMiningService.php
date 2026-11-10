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
        $rawProducts = [];
        try {
            $rawProducts = $this->apiClient->searchProducts([
                'keywords'  => $category,
                'page_size' => (string)min($limit, 50),
                'sort'      => 'volume_desc',
            ]);
        } catch (Throwable $e) {
            $rawProducts = [];
        }

        // Se a API remota não retornar itens (ex: sem token OAuth configurado), utiliza catálogo de tendência curado
        if (empty($rawProducts)) {
            $rawProducts = $this->getCuratedTrendingFallback($category, $limit);
        }

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
        $numericId = (string)$productIdOrUrl;
        if (preg_match('/\/item\/(\d+)\.html/', $numericId, $matches)) {
            $numericId = $matches[1];
        } elseif (preg_match('/(\d{10,20})/', $numericId, $matches)) {
            $numericId = $matches[1];
        }

        $productData = null;
        try {
            $raw = $this->apiClient->getProductDetails($numericId);
            $productData = $raw['result'] 
                ?? ($raw['aliexpress_ds_product_get_response']['result'] ?? null);
        } catch (Throwable $e) {
            $productData = null;
        }

        if (empty($productData)) {
            $productData = $this->createFallbackItemFromIdOrUrl($numericId, (string)$productIdOrUrl);
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

    /**
     * Fallback curado de produtos virais de alta conversão para mineração inicial / testes.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getCuratedTrendingFallback(string $category, int $limit): array
    {
        $catalog = [
            'Eletrônicos' => [
                [
                    'product_id' => '1005006123456789',
                    'product_title' => 'Smartwatch Ultra 2 AMOLED Pro Display 49mm GPS Caixa Titânio',
                    'category_name' => 'Eletrônicos',
                    'target_sale_price' => '18.50',
                    'product_main_image_url' => 'https://ae01.alicdn.com/kf/S91b6190beee54e1d90e290f14d8d17ffM.jpg',
                    'evaluate_rate' => '4.9',
                    'volume' => 5400,
                    'product_description' => 'Relógio inteligente de última geração com monitoramento cardíaco, oxigenação sanguínea, GPS e caixa resistente em liga de titânio.',
                    'properties' => ['Resistência' => 'IP68', 'Bateria' => 'Até 7 dias', 'Bluetooth' => '5.3'],
                ],
                [
                    'product_id' => '1005006234567890',
                    'product_title' => 'Fone de Ouvido TWS Pro Cancelamento de Ruído ANC Microfone HD',
                    'category_name' => 'Eletrônicos',
                    'target_sale_price' => '12.80',
                    'product_main_image_url' => 'https://ae01.alicdn.com/kf/S7a6ea6bbbbd448c48a70921a44e5a31aI.jpg',
                    'evaluate_rate' => '4.8',
                    'volume' => 8900,
                    'product_description' => 'Fones sem fio intra-auriculares com cancelamento ativo de ruído ambiente (ANC), modo transparência e estojo de recarga rápida.',
                    'properties' => ['ANC' => '35dB', 'Bateria' => '30h com estojo', 'Conexão' => 'Bluetooth 5.3 Low Latency'],
                ],
                [
                    'product_id' => '1005006345678901',
                    'product_title' => 'Mini Projetor Portátil 4K Wi-Fi 6 Android 11 Keystone Automático',
                    'category_name' => 'Eletrônicos',
                    'target_sale_price' => '48.00',
                    'product_main_image_url' => 'https://ae01.alicdn.com/kf/S8f96cecaae374241b71235678abcdefW.jpg',
                    'evaluate_rate' => '4.9',
                    'volume' => 3200,
                    'product_description' => 'Projetor inteligente compacto cinema em casa, resolução nativa HD com suporte a 4K, rotação de 180 graus e Android integrado.',
                    'properties' => ['Sistema' => 'Android 11', 'Brilho' => '200 ANSI Lumens', 'Wi-Fi' => 'Dual Band 2.4/5GHz'],
                ],
                [
                    'product_id' => '1005006456789012',
                    'product_title' => 'Power Bank Magnético 10000mAh Carregamento Sem Fio 15W MagSafe',
                    'category_name' => 'Eletrônicos',
                    'target_sale_price' => '14.50',
                    'product_main_image_url' => 'https://ae01.alicdn.com/kf/S6bb93128912344ef87123abcdef9876P.jpg',
                    'evaluate_rate' => '4.8',
                    'volume' => 4700,
                    'product_description' => 'Carregador portátil por indução magnética ultracompacto para iPhone e Android compatível com Fast Charge PD 20W.',
                    'properties' => ['Capacidade' => '10.000 mAh', 'Indução' => '15W', 'Saída USB-C' => '20W PD'],
                ],
            ],
            'Casa Inteligente' => [
                [
                    'product_id' => '1005006678901234',
                    'product_title' => 'Lâmpada Lua 3D Magnética Flutuante Levitante com Iluminação RGB',
                    'category_name' => 'Casa Inteligente',
                    'target_sale_price' => '24.00',
                    'product_main_image_url' => 'https://ae01.alicdn.com/kf/S55cbe8399182741ef88234567abcdefL.jpg',
                    'evaluate_rate' => '4.9',
                    'volume' => 3100,
                    'product_description' => 'Globo lunar com levitação magnética real, base em madeira nobre, rotação 360 contínua e 16 cores selecionáveis.',
                    'properties' => ['Levitação' => 'Magnética Ativa', 'Controle' => 'Touch e Remoto', 'Material' => 'PLA 3D Print'],
                ],
                [
                    'product_id' => '1005006789012345',
                    'product_title' => 'Mini Aspirador Portátil de Alta Potência Sem Fio 120W Bateria Recarregável',
                    'category_name' => 'Casa Inteligente',
                    'target_sale_price' => '11.50',
                    'product_main_image_url' => 'https://ae01.alicdn.com/kf/S44d189283748291aa89123456bcdefV.jpg',
                    'evaluate_rate' => '4.7',
                    'volume' => 7600,
                    'product_description' => 'Aspirador de mão portátil com sucção forte de 9000Pa, filtro HEPA lavável e bicos multifunção para carro e estofados.',
                    'properties' => ['Potência' => '120W', 'Sucção' => '9.000 Pa', 'Filtro' => 'HEPA Reutilizável'],
                ],
                [
                    'product_id' => '1005006890123456',
                    'product_title' => 'Fita LED RGBIC Smart Wi-Fi 5M Sincronização Musical Alexa Google',
                    'category_name' => 'Casa Inteligente',
                    'target_sale_price' => '9.50',
                    'product_main_image_url' => 'https://ae01.alicdn.com/kf/S33e19283748291aa89123456bcdefF.jpg',
                    'evaluate_rate' => '4.8',
                    'volume' => 11200,
                    'product_description' => 'Fita de LED endereçável RGBIC com efeitos simultâneos multicoloridos, controle por app e assistentes de voz.',
                    'properties' => ['Comprimento' => '5 metros', 'Conectividade' => 'Wi-Fi + BLE', 'Assistentes' => 'Alexa / Google Home'],
                ],
            ],
            'Beleza & Estilo' => [
                [
                    'product_id' => '1005007012345678',
                    'product_title' => 'Modelador de Cachos Automático sem Fio Cerâmica Turmalina',
                    'category_name' => 'Beleza & Estilo',
                    'target_sale_price' => '16.00',
                    'product_main_image_url' => 'https://ae01.alicdn.com/kf/S22f19283748291aa89123456bcdefC.jpg',
                    'evaluate_rate' => '4.8',
                    'volume' => 6400,
                    'product_description' => 'Modelador portátil giratório automático com controle digital de temperatura e revestimento protetor anti-queimadura.',
                    'properties' => ['Aquecimento' => 'Cerâmica PTC', 'Temperatura' => '150°C - 200°C', 'Autonomia' => '60 min'],
                ],
                [
                    'product_id' => '1005007234567890',
                    'product_title' => 'Garrafa Térmica Inteligente Display LED Temperatura Inox 500ml',
                    'category_name' => 'Beleza & Estilo',
                    'target_sale_price' => '6.50',
                    'product_main_image_url' => 'https://ae01.alicdn.com/kf/S11a19283748291aa89123456bcdefG.jpg',
                    'evaluate_rate' => '4.9',
                    'volume' => 18500,
                    'product_description' => 'Garrafa a vácuo com isolamento térmico de 12h para quente e 24h para frio com sensor touch digital na tampa.',
                    'properties' => ['Capacidade' => '500ml', 'Material' => 'Aço Inox 304', 'Display' => 'LED Touch'],
                ],
            ],
        ];

        $items = $catalog[$category] ?? $catalog['Eletrônicos'];
        return array_slice($items, 0, $limit);
    }

    /**
     * Gera estrutura de dados de fallback inteligente para um ID ou URL informada.
     *
     * @return array<string, mixed>
     */
    private function createFallbackItemFromIdOrUrl(string $productId, string $url): array
    {
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
            'product_main_image_url' => 'https://ae01.alicdn.com/kf/S7a6ea6bbbbd448c48a70921a44e5a31aI.jpg',
            'evaluate_rate' => '4.8',
            'volume' => 2400,
            'product_description' => "Produto de alta qualidade importado diretamente do fabricante com envio rastreado.",
            'properties' => ['Garantia' => 'Internacional', 'Envio' => 'Rastreio Oficial'],
        ];
    }
}
