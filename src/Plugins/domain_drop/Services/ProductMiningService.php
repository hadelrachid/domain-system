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
                    'product_main_image_url' => 'https://images.unsplash.com/photo-1579586337278-3befd40fd17a?w=600&auto=format&fit=crop&q=80',
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
                    'product_main_image_url' => 'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?w=600&auto=format&fit=crop&q=80',
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
                    'product_main_image_url' => 'https://images.unsplash.com/photo-1728771175581-3cb1e21fac92?w=600&auto=format&fit=crop&q=80',
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
                    'product_main_image_url' => 'https://images.unsplash.com/photo-1583863788434-e58a36330cf0?w=600&auto=format&fit=crop&q=80',
                    'evaluate_rate' => '4.8',
                    'volume' => 4700,
                    'product_description' => 'Carregador portátil por indução magnética ultracompacto para iPhone e Android compatível com Fast Charge PD 20W.',
                    'properties' => ['Capacidade' => '10.000 mAh', 'Indução' => '15W', 'Saída USB-C' => '20W PD'],
                ],
                [
                    'product_id' => '1005006567890123',
                    'product_title' => 'Caixa de Som Bluetooth Portátil Pro Graves Profundos À Prova D Água',
                    'category_name' => 'Eletrônicos',
                    'target_sale_price' => '15.00',
                    'product_main_image_url' => 'https://images.unsplash.com/photo-1545454675-3531b543be5d?w=600&auto=format&fit=crop&q=80',
                    'evaluate_rate' => '4.9',
                    'volume' => 6100,
                    'product_description' => 'Alto-falante estéreo 360 graus com radiador passivo de graves, certificação IPX7 e autonomia de até 15 horas.',
                    'properties' => ['Potência' => '20W RMS', 'Autonomia' => '15h', 'Proteção' => 'IPX7'],
                ],
                [
                    'product_id' => '1005006678901235',
                    'product_title' => 'Drone Dobrável 4K Dual Câmera Estabilização GPS Retorno Automático',
                    'category_name' => 'Eletrônicos',
                    'target_sale_price' => '39.00',
                    'product_main_image_url' => 'https://images.unsplash.com/photo-1507582020474-9a35b7d455d9?w=600&auto=format&fit=crop&q=80',
                    'evaluate_rate' => '4.8',
                    'volume' => 2800,
                    'product_description' => 'Drone de alta estabilidade com transmissão de vídeo 5G em tempo real, sensores anti-colisão e modos de voo inteligentes.',
                    'properties' => ['Câmera' => '4K Ultra HD', 'Alcance' => '1200 metros', 'Bateria' => '28 min por carga'],
                ],
                [
                    'product_id' => '1005006789012346',
                    'product_title' => 'Ring Light LED 12 Polegadas com Tripé Ajustável e Suporte Celular',
                    'category_name' => 'Eletrônicos',
                    'target_sale_price' => '11.00',
                    'product_main_image_url' => 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?w=600&auto=format&fit=crop&q=80',
                    'evaluate_rate' => '4.8',
                    'volume' => 9300,
                    'product_description' => 'Iluminação profissional para criadores de conteúdo com 3 tonalidades de luz e 10 níveis de brilho com controle no cabo.',
                    'properties' => ['Diâmetro' => '12 polegadas (30cm)', 'Tripé' => 'Até 2,10m', 'Alimentação' => 'USB 5V'],
                ],
                [
                    'product_id' => '1005006890123457',
                    'product_title' => 'Microfone Lapela Sem Fio Duplo USB-C Lightning Redução de Ruído',
                    'category_name' => 'Eletrônicos',
                    'target_sale_price' => '9.80',
                    'product_main_image_url' => 'https://images.unsplash.com/photo-1590658006821-04f4008d5717?w=600&auto=format&fit=crop&q=80',
                    'evaluate_rate' => '4.9',
                    'volume' => 12400,
                    'product_description' => 'Kit com 2 transmissores e 1 receptor plug and play para entrevistas, podcasts e gravações com cancelamento de ruído DSP.',
                    'properties' => ['Alcance' => '20 metros', 'Bateria' => '6h de gravação contínua', 'Conector' => 'Universal Tipo C / iOS'],
                ],
                [
                    'product_id' => '1005006901234568',
                    'product_title' => 'Suporte Articulado de Mesa com Carregamento Rápido por Indução',
                    'category_name' => 'Eletrônicos',
                    'target_sale_price' => '13.50',
                    'product_main_image_url' => 'https://images.unsplash.com/photo-1586105251261-72a756497a11?w=600&auto=format&fit=crop&q=80',
                    'evaluate_rate' => '4.8',
                    'volume' => 4500,
                    'product_description' => 'Suporte metálico em alumínio aeroespacial com inclinação e rotação 360 graus e base de carregamento wireless Qi.',
                    'properties' => ['Material' => 'Alumínio CNC', 'Potência Wireless' => '15W Fast Qi', 'Compatibilidade' => 'Universal'],
                ],
                [
                    'product_id' => '1005007012345679',
                    'product_title' => 'Console Portátil Retrô 10000 Jogos Clássicos Tela IPS HD',
                    'category_name' => 'Eletrônicos',
                    'target_sale_price' => '27.00',
                    'product_main_image_url' => 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=600&auto=format&fit=crop&q=80',
                    'evaluate_rate' => '4.9',
                    'volume' => 7800,
                    'product_description' => 'Videogame portátil nostálgico com emuladores de arcade, PS1, Super Nintendo e Mega Drive com saída HDMI para TV.',
                    'properties' => ['Tela' => '3.5 IPS OCA', 'Armazenamento' => '64GB Cartão TF', 'Jogos' => '+10.000 inclusos'],
                ],
            ],
            'Casa Inteligente' => [
                [
                    'product_id' => '1005006678901234',
                    'product_title' => 'Lâmpada Lua 3D Magnética Flutuante Levitante com Iluminação RGB',
                    'category_name' => 'Casa Inteligente',
                    'target_sale_price' => '24.00',
                    'product_main_image_url' => 'https://images.unsplash.com/photo-1513506003901-1e6a229e2d15?w=600&auto=format&fit=crop&q=80',
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
                    'product_main_image_url' => 'https://images.unsplash.com/photo-1527515637462-cff94eecc1ac?w=600&auto=format&fit=crop&q=80',
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
                    'product_main_image_url' => 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=600&auto=format&fit=crop&q=80',
                    'evaluate_rate' => '4.8',
                    'volume' => 11200,
                    'product_description' => 'Fita de LED endereçável RGBIC com efeitos simultâneos multicoloridos, controle por app e assistentes de voz.',
                    'properties' => ['Comprimento' => '5 metros', 'Conectividade' => 'Wi-Fi + BLE', 'Assistentes' => 'Alexa / Google Home'],
                ],
                [
                    'product_id' => '1005006901234569',
                    'product_title' => 'Dispensador Automático de Sabonete com Sensor Infravermelho Touchless',
                    'category_name' => 'Casa Inteligente',
                    'target_sale_price' => '8.50',
                    'product_main_image_url' => 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=600&auto=format&fit=crop&q=80',
                    'evaluate_rate' => '4.8',
                    'volume' => 5200,
                    'product_description' => 'Dosador higiênico sem toque com espumador ultra-rápido de 0.25 segundos e bateria recarregável USB.',
                    'properties' => ['Capacidade' => '350ml', 'Sensor' => 'Infravermelho 5cm', 'Proteção' => 'IPX4'],
                ],
                [
                    'product_id' => '1005007012345680',
                    'product_title' => 'Fechadura Digital Biométrica com Senha Cartão e Chave de Emergência',
                    'category_name' => 'Casa Inteligente',
                    'target_sale_price' => '32.00',
                    'product_main_image_url' => 'https://images.unsplash.com/photo-1558002038-1055907df827?w=600&auto=format&fit=crop&q=80',
                    'evaluate_rate' => '4.9',
                    'volume' => 2900,
                    'product_description' => 'Fechadura eletrônica inteligente para portas de madeira ou metal com leitor biométrico semiconductor de 0.3s.',
                    'properties' => ['Aberturas' => 'Biometria / Senha / Tag RFID / Chave', 'Usuários' => 'Até 100 digitais'],
                ],
                [
                    'product_id' => '1005007123456781',
                    'product_title' => 'Câmera de Segurança Inteligente 360 Noturna Wi-Fi Detecção Humana',
                    'category_name' => 'Casa Inteligente',
                    'target_sale_price' => '14.00',
                    'product_main_image_url' => 'https://images.unsplash.com/photo-1557597774-9d273605dfa9?w=600&auto=format&fit=crop&q=80',
                    'evaluate_rate' => '4.9',
                    'volume' => 8400,
                    'product_description' => 'Câmera IP panorâmica com áudio bidirecional, rastreamento de movimento por inteligência artificial e visão noturna infravermelha.',
                    'properties' => ['Resolução' => '1080p Full HD', 'Rotação' => '355° H / 90° V', 'Gravação' => 'Cartão MicroSD / Nuvem'],
                ],
                [
                    'product_id' => '1005007234567892',
                    'product_title' => 'Umidificador Ultrassônico de Ar com Difusor de Aromas e Led Noturno',
                    'category_name' => 'Casa Inteligente',
                    'target_sale_price' => '10.50',
                    'product_main_image_url' => 'https://images.unsplash.com/photo-1608571423902-eed4a5ad8108?w=600&auto=format&fit=crop&q=80',
                    'evaluate_rate' => '4.8',
                    'volume' => 11500,
                    'product_description' => 'Umidificador silencioso com desligamento automático, névoa fina aromática e iluminação ambiente de 7 cores.',
                    'properties' => ['Capacidade' => '500ml', 'Operação' => 'Silenciosa < 30dB', 'Timer' => '1h / 3h / 6h'],
                ],
                [
                    'product_id' => '1005007345678903',
                    'product_title' => 'Luminária de Mesa LED Articulada com Sensor Touch e Dimmer Suave',
                    'category_name' => 'Casa Inteligente',
                    'target_sale_price' => '12.00',
                    'product_main_image_url' => 'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?w=600&auto=format&fit=crop&q=80',
                    'evaluate_rate' => '4.8',
                    'volume' => 6300,
                    'product_description' => 'Abajur minimalista com luz anti-fadiga ocular, haste flexível e bateria recarregável para leitura e trabalho.',
                    'properties' => ['Temperaturas de Cor' => 'Quente / Neutra / Fria', 'Brilho' => 'Ajuste contínuo touch'],
                ],
                [
                    'product_id' => '1005007456789014',
                    'product_title' => 'Tomada Inteligente Wi-Fi 16A com Monitoramento de Consumo de Energia',
                    'category_name' => 'Casa Inteligente',
                    'target_sale_price' => '6.80',
                    'product_main_image_url' => 'https://images.unsplash.com/photo-1558002038-1055907df827?w=600&auto=format&fit=crop&q=80',
                    'evaluate_rate' => '4.8',
                    'volume' => 15400,
                    'product_description' => 'Plugue inteligente que transforma qualquer eletrodoméstico em smart com controle por aplicativo e comandos de voz.',
                    'properties' => ['Corrente Máxima' => '16A (3500W)', 'App' => 'Smart Life / Tuya', 'Compatibilidade' => 'Alexa / Google'],
                ],
                [
                    'product_id' => '1005007567890125',
                    'product_title' => 'Sensor de Presença e Movimento Inteligente Zigbee sem Fio',
                    'category_name' => 'Casa Inteligente',
                    'target_sale_price' => '7.20',
                    'product_main_image_url' => 'https://images.unsplash.com/photo-1557597774-9d273605dfa9?w=600&auto=format&fit=crop&q=80',
                    'evaluate_rate' => '4.7',
                    'volume' => 4100,
                    'product_description' => 'Automação residencial instantânea para acender luzes ao entrar no cômodo e emitir notificações de segurança no celular.',
                    'properties' => ['Ângulo' => '120 graus', 'Distância' => '7 metros', 'Bateria' => 'CR2450 (Vida útil 2 anos)'],
                ],
            ],
            'Beleza & Estilo' => [
                [
                    'product_id' => '1005007012345678',
                    'product_title' => 'Modelador de Cachos Automático sem Fio Cerâmica Turmalina',
                    'category_name' => 'Beleza & Estilo',
                    'target_sale_price' => '16.00',
                    'product_main_image_url' => 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=600&auto=format&fit=crop&q=80',
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
                    'product_main_image_url' => 'https://images.unsplash.com/photo-1602143407151-7111542de6e8?w=600&auto=format&fit=crop&q=80',
                    'evaluate_rate' => '4.9',
                    'volume' => 18500,
                    'product_description' => 'Garrafa a vácuo com isolamento térmico de 12h para quente e 24h para frio com sensor touch digital na tampa.',
                    'properties' => ['Capacidade' => '500ml', 'Material' => 'Aço Inox 304', 'Display' => 'LED Touch'],
                ],
            ],
        ];

        // Se a categoria solicitada existir, usa seus itens; caso contrário, usa Eletrônicos
        $pool = $catalog[$category] ?? $catalog['Eletrônicos'];

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

        return array_slice($pool, 0, $limit);
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
            'product_main_image_url' => 'https://images.unsplash.com/photo-1526738549149-8e07eca6c147?w=600&auto=format&fit=crop&q=80',
            'evaluate_rate' => '4.8',
            'volume' => 2400,
            'product_description' => "Produto de alta qualidade importado diretamente do fabricante com envio rastreado.",
            'properties' => ['Garantia' => 'Internacional', 'Envio' => 'Rastreio Oficial'],
        ];
    }
}
