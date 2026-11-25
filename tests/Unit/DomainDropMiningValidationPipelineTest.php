<?php

declare(strict_types=1);

namespace Tests\Unit;

use DomainSystem\Plugins\domain_drop\Contracts\Validation\ProductIngestionContainerInterface;
use DomainSystem\Plugins\domain_drop\Contracts\Validation\ProductValidationGuardInterface;
use DomainSystem\Plugins\domain_drop\Contracts\Validation\ProductValidationPipelineInterface;
use DomainSystem\Plugins\domain_drop\DTO\Validation\ValidationResult;
use DomainSystem\Plugins\domain_drop\Services\Validation\Guards\ContentIntegrityGuard;
use DomainSystem\Plugins\domain_drop\Services\Validation\Guards\PriceIntegrityGuard;
use DomainSystem\Plugins\domain_drop\Services\Validation\Guards\ProductExistenceGuard;
use DomainSystem\Plugins\domain_drop\Services\Validation\Guards\RegionalLogisticsGuard;
use DomainSystem\Plugins\domain_drop\Services\Validation\MiningValidationHub;
use DomainSystem\Plugins\domain_drop\Services\Validation\ProductIngestionContainer;
use DomainSystem\Plugins\domain_drop\Services\Validation\ProductValidationPipeline;
use PHPUnit\Framework\TestCase;

class DomainDropMiningValidationPipelineTest extends TestCase
{
    protected function setUp(): void
    {
        if (!is_dir(dirname(__DIR__, 2) . '/src/Plugins/domain_drop')) {
            $this->markTestSkipped('Plugin agregado externo [domain_drop] não está presente no checkout.');
            return;
        }
        parent::setUp();
    }

    public function testContainerIngestionAndQuarantineOperations(): void
    {
        $container = new ProductIngestionContainer();
        $this->assertSame(0, $container->count());

        $container->add('1005001234567890', [
            'title' => 'Smartwatch Pro GPS Brasil',
            'sale_price_brl' => 149.90,
        ]);

        $this->assertTrue($container->has('1005001234567890'));
        $this->assertSame(1, $container->count());
        $this->assertNotNull($container->get('1005001234567890'));

        // Mover para quarentena
        $container->quarantine('1005001234567890', 'Link 404 confirmado', 'ProductExistenceGuard', ['http_code' => 404]);

        $this->assertFalse($container->has('1005001234567890'));
        $this->assertSame(0, $container->count());

        $quarantined = $container->getQuarantined();
        $this->assertArrayHasKey('1005001234567890', $quarantined);
        $this->assertSame('Link 404 confirmado', $quarantined['1005001234567890']['reason']);
        $this->assertSame('ProductExistenceGuard', $quarantined['1005001234567890']['guard']);
        $this->assertSame(404, $quarantined['1005001234567890']['details']['http_code']);
    }

    public function testProductExistenceGuardValidatesRealNumericIdAndOnSellingStatus(): void
    {
        $guard = new ProductExistenceGuard();

        // 1. Item válido e onSelling
        $validItem = [
            'ae_item_base_info_dto' => [
                'product_status_type' => 'onSelling',
            ],
        ];
        $res = $guard->validate('1005004567891234', $validItem);
        $this->assertTrue($res->isValid());

        // 2. ID inválido (vazio ou com caracteres ilegais)
        $invalidId = $guard->validate('invalid id with spaces', $validItem);
        $this->assertFalse($invalidId->isValid());
        $this->assertStringContainsString('caracteres ilegais detectados', $invalidId->getReason());

        // 3. Status offline no fornecedor
        $offlineItem = [
            'ae_item_base_info_dto' => [
                'product_status_type' => 'offline',
            ],
        ];
        $resOffline = $guard->validate('1005004567891234', $offlineItem);
        $this->assertFalse($resOffline->isValid());
        $this->assertStringContainsString('inativo no fornecedor', $resOffline->getReason());

        // 4. ITEM_ID_NOT_FOUND do SDK AliExpress
        $notFoundItem = [
            'rsp_code' => '404',
            'rsp_msg'  => 'ITEM_ID_NOT_FOUND The product itemid is wrong',
        ];
        $resNotFound = $guard->validate('1005004567891234', $notFoundItem);
        $this->assertFalse($resNotFound->isValid());
        $this->assertStringContainsString('ITEM_ID_NOT_FOUND', $resNotFound->getReason());
    }

    public function testRegionalLogisticsGuardEnforcesBrazilDelivery(): void
    {
        $guard = new RegionalLogisticsGuard();

        // 1. Elegível para o Brasil
        $validBr = [
            'ship_to_country' => 'BR',
            'freight_amount' => 19.90,
        ];
        $this->assertTrue($guard->validate('1005001', $validBr)->isValid());

        // 2. Erro oficial "Item is not allowed to this country" (dropshiper.docx)
        $prohibited = [
            'rsp_msg' => 'Item is not allowed to this country. The product is prohibited from sale in this country.',
        ];
        $resProhibited = $guard->validate('1005001', $prohibited);
        $this->assertFalse($resProhibited->isValid());
        $this->assertStringContainsString('venda proibida ou indisponível para o Brasil', $resProhibited->getReason());

        // 3. Destino incompatível exclusivo (ex.: US)
        $usOnly = [
            'ship_to_country' => 'US',
        ];
        $resUs = $guard->validate('1005001', $usOnly);
        $this->assertFalse($resUs->isValid());
        $this->assertStringContainsString('incompatível com o Brasil', $resUs->getReason());

        // 4. Custo abusivo de frete
        $abusiveFreight = [
            'freight_amount' => 3500.00,
        ];
        $resFreight = $guard->validate('1005001', $abusiveFreight);
        $this->assertFalse($resFreight->isValid());
        $this->assertStringContainsString('frete abusivo', $resFreight->getReason());
    }

    public function testPriceIntegrityGuardPreventsZeroAndAccessoryBaitPrices(): void
    {
        $guard = new PriceIntegrityGuard();

        // 1. Preço de mercado normal
        $normalPrice = ['sale_price_brl' => 89.90];
        $this->assertTrue($guard->validate('1005001', $normalPrice)->isValid());

        // 2. Preço zerado
        $zeroPrice = ['sale_price_brl' => 0.0];
        $resZero = $guard->validate('1005001', $zeroPrice);
        $this->assertFalse($resZero->isValid());
        $this->assertStringContainsString('zerado/inválido', $resZero->getReason());

        // 3. Preço Isca de acessório (R$ 0,80)
        $baitPrice = ['sale_price_brl' => 0.80];
        $resBait = $guard->validate('1005001', $baitPrice);
        $this->assertFalse($resBait->isValid());
        $this->assertStringContainsString('variação isca de acessório', $resBait->getReason());

        // 4. Preço com SKU oficial coerente
        $skuValid = [
            'sale_price_brl' => 49.90,
            'ae_item_sku_info_dtos' => [
                ['sku_id' => '101', 'offer_sale_price' => '49.90'],
                ['sku_id' => '102', 'offer_sale_price' => '54.90'],
            ],
        ];
        $this->assertTrue($guard->validate('1005001', $skuValid)->isValid());

        // 5. Matriz de SKU com todos os preços zerados
        $skuAllZero = [
            'sale_price_brl' => 49.90,
            'ae_item_sku_info_dtos' => [
                ['sku_id' => '101', 'offer_sale_price' => '0.00'],
                ['sku_id' => '102', 'offer_sale_price' => '0.00'],
            ],
        ];
        $resSkuZero = $guard->validate('1005001', $skuAllZero);
        $this->assertFalse($resSkuZero->isValid());
        $this->assertStringContainsString('preços zerados ou inválidos', $resSkuZero->getReason());
    }

    public function testContentIntegrityGuardValidatesTitlesAndCdnImages(): void
    {
        $guard = new ContentIntegrityGuard();

        // 1. Conteúdo e imagem oficiais válidos
        $validContent = [
            'title' => 'Fone de Ouvido Sem Fio Bluetooth 5.3 com Case',
            'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S12345.jpg',
        ];
        $this->assertTrue($guard->validate('1005001', $validContent)->isValid());

        // 2. Título muito curto
        $shortTitle = [
            'title' => 'Fone',
            'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S12345.jpg',
        ];
        $resShort = $guard->validate('1005001', $shortTitle);
        $this->assertFalse($resShort->isValid());
        $this->assertStringContainsString('muito curto', $resShort->getReason());

        // 3. Imagem 404 ou vazia
        $brokenImg = [
            'title' => 'Fone de Ouvido Sem Fio Bluetooth 5.3 com Case',
            'product_main_image_url' => 'https://example.com/images/404-not-found.png',
        ];
        $resImg = $guard->validate('1005001', $brokenImg);
        $this->assertFalse($resImg->isValid());
        $this->assertStringContainsString('placeholder de erro ou 404', $resImg->getReason());
    }

    public function testValidationPipelineAndMiningValidationHubBatchProcessing(): void
    {
        $hub = new MiningValidationHub();

        $batch = [
            // Item 1: Válido 100%
            [
                'itemId' => '1005006918079912',
                'title' => 'Parafusadeira Elétrica 21V Bateria Lítio Recarregável',
                'sale_price_brl' => 189.90,
                'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S4ecd9088593344a1b794f748c06dc3b0R.jpg',
                'ship_to_country' => 'BR',
            ],
            // Item 2: Inválido - 404 / ITEM_ID_NOT_FOUND
            [
                'itemId' => '1005009999999999',
                'title' => 'Produto Fantasma com Página Não Encontrada',
                'sale_price_brl' => 99.00,
                'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/Svalid.jpg',
                'rsp_code' => '404',
                'rsp_msg' => 'ITEM_ID_NOT_FOUND',
            ],
            // Item 3: Inválido - Preço Isca de R$ 0,50
            [
                'itemId' => '1005007777777777',
                'title' => 'Drone Profissional 4K com Câmera Dupla',
                'sale_price_brl' => 0.50,
                'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/Svalid2.jpg',
                'ship_to_country' => 'BR',
            ],
            // Item 4: Inválido - Proibido para o Brasil
            [
                'itemId' => '1005008888888888',
                'title' => 'Item Restrito Regionalmente para Exportação',
                'sale_price_brl' => 120.00,
                'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/Svalid3.jpg',
                'rsp_msg' => 'Item is not allowed to this country',
            ],
            // Item 5: Válido 100%
            [
                'itemId' => '1005006093554321',
                'title' => 'Relógio Smartwatch Esportivo AMOLED Bluetooth Chamada',
                'sale_price_brl' => 129.90,
                'product_main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/Svalid4.jpg',
                'ship_to_country' => 'BR',
            ],
        ];

        $result = $hub->ingestAndValidateBatch($batch);

        $this->assertSame(5, $result['total_ingested']);
        $this->assertSame(2, $result['total_approved']);
        $this->assertSame(3, $result['total_quarantined']);

        // Aprovados devem conter exatamente os itens 1 e 5
        $this->assertArrayHasKey('1005006918079912', $result['valid_items']);
        $this->assertArrayHasKey('1005006093554321', $result['valid_items']);

        // Quarentenados devem conter os itens 2, 3 e 4
        $this->assertArrayHasKey('1005009999999999', $result['quarantined_items']);
        $this->assertArrayHasKey('1005007777777777', $result['quarantined_items']);
        $this->assertArrayHasKey('1005008888888888', $result['quarantined_items']);
    }
}
