<?php

declare(strict_types=1);

namespace Tests\Unit;

use DomainSystem\Plugins\domain_drop\Contracts\WarehouseOriginResolverInterface;
use DomainSystem\Plugins\domain_drop\Services\WarehouseOriginResolver;
use DomainSystem\Plugins\domain_drop\Services\Validation\Guards\WarehouseOriginGuard;
use PHPUnit\Framework\TestCase;

/**
 * Bateria de Testes Unitários de Conformidade Logística e Entrepostos (BR / CN / UNKNOWN).
 * 
 * Atende estritamente às exigências da documentação oficial do AliExpress
 * (ae-freight-shipment.pdf e ae-logistics.pdf).
 */
class DomainDropWarehouseOriginTest extends TestCase
{
    protected function setUp(): void
    {
        if (!is_dir(dirname(__DIR__, 2) . '/src/Plugins/domain_drop')) {
            $this->markTestSkipped('Plugin agregado [domain_drop] não está presente no checkout.');
            return;
        }
        parent::setUp();
    }

    /**
     * Teste 1: Loja chinesa com estoque comprovadamente no Brasil (ship_from = BR).
     */
    public function testChineseStoreWithProvenBrazilWarehouse(): void
    {
        $resolver = new WarehouseOriginResolver();
        $item = [
            'store_info' => ['store_country_code' => 'CN', 'store_name' => 'Shenzhen Electronics Co.'],
            'ship_from' => 'BR',
            'title' => 'Carregador Turbo 65W GaN',
        ];

        $origin = $resolver->resolve($item);
        $this->assertSame(WarehouseOriginResolverInterface::ORIGIN_BR, $origin, 'Loja chinesa com despacho no Brasil é classificada como BR');
    }

    /**
     * Teste 2: Loja brasileira (store_country_code = BR) mas com despacho da China.
     * PROVA: O código NÃO confunde país cadastral com origem física do estoque.
     */
    public function testBrazilianStoreWithChinaShippingIsClassifiedAsCn(): void
    {
        $resolver = new WarehouseOriginResolver();
        $item = [
            'store_info' => ['store_country_code' => 'BR', 'store_name' => 'Importados Brasil LTDA'],
            'ship_from' => 'CN',
            'remessa_conforme' => true,
            'title' => 'Smartwatch Pro 4K',
        ];

        $origin = $resolver->resolve($item);
        $this->assertSame(WarehouseOriginResolverInterface::ORIGIN_CN, $origin, 'Loja com cadastro no Brasil e despacho da China é classificada como CN (elimina falso positivo)');
    }

    /**
     * Teste 3: Produto sem informação de despacho é classificado como UNKNOWN.
     * PROVA: A ausência de informação NUNCA é convertida automaticamente em CN.
     */
    public function testUnknownOriginIsPreservedAndNeverConvertedToCn(): void
    {
        $resolver = new WarehouseOriginResolver();
        $item = [
            'product_id' => '1005009988776655',
            'title' => 'Cabo USB Tipo C Trançado',
        ];

        $origin = $resolver->resolve($item);
        $this->assertSame(WarehouseOriginResolverInterface::ORIGIN_UNKNOWN, $origin, 'Produto sem evidência de expedição física permanece como UNKNOWN (nunca converte para CN)');
    }

    /**
     * Teste 4: Consulta oficial de frete com ship_from_country = BR.
     */
    public function testFreightQueryResponseWithShipFromCountryBr(): void
    {
        $resolver = new WarehouseOriginResolver();
        $item = [
            'product_id' => '3256802900954148',
            'title' => 'Fone de Ouvido Bluetooth TWS',
            'freight_info' => [
                'company' => 'Correios Sedex BR',
                'ship_from_country' => 'BR',
                'available_stock' => 150,
            ],
        ];

        $origin = $resolver->resolve($item);
        $this->assertSame(WarehouseOriginResolverInterface::ORIGIN_BR, $origin, 'Resposta oficial de frete com ship_from_country = BR confirma entreposto brasileiro');
    }

    /**
     * Teste 5: Consulta oficial de frete com ship_from_country = CN.
     */
    public function testFreightQueryResponseWithShipFromCountryCn(): void
    {
        $resolver = new WarehouseOriginResolver();
        $item = [
            'product_id' => '1005007788990011',
            'title' => 'Teclado Mecânico RGB',
            'freight_info' => [
                'company' => 'Cainiao Standard Shipping',
                'ship_from_country' => 'CN',
            ],
        ];

        $origin = $resolver->resolve($item);
        $this->assertSame(WarehouseOriginResolverInterface::ORIGIN_CN, $origin, 'Resposta oficial de frete com ship_from_country = CN confirma entreposto chinês');
    }

    /**
     * Teste 6: WarehouseOriginGuard no Modo BR.
     * PROVA: No modo BR, aprova APENAS itens comprovadamente BR. CN e UNKNOWN são reprovados.
     */
    public function testWarehouseOriginGuardStrictModeBr(): void
    {
        $guard = new WarehouseOriginGuard(WarehouseOriginResolverInterface::MODE_BR);

        $itemBr = ['ship_from' => 'BR', 'title' => 'Produto Brasil'];
        $resBr = $guard->validate($itemBr);
        $this->assertTrue($resBr->isValid(), 'Guard no modo BR aprova item com despacho BR comprovado');

        $itemCn = ['ship_from' => 'CN', 'title' => 'Produto China'];
        $resCn = $guard->validate($itemCn);
        $this->assertFalse($resCn->isValid(), 'Guard no modo BR reprova item de origem CN');

        $itemUnknown = ['title' => 'Produto Sem Despacho'];
        $resUnknown = $guard->validate($itemUnknown);
        $this->assertFalse($resUnknown->isValid(), 'Guard no modo BR reprova item de origem UNKNOWN (Zero-Contamination)');
    }

    /**
     * Teste 7: WarehouseOriginGuard no Modo CN.
     * PROVA: No modo CN, aprova itens CN e reprova itens BR.
     */
    public function testWarehouseOriginGuardStrictModeCn(): void
    {
        $guard = new WarehouseOriginGuard(WarehouseOriginResolverInterface::MODE_CN);

        $itemBr = ['ship_from' => 'BR', 'title' => 'Produto Brasil'];
        $resBr = $guard->validate($itemBr);
        $this->assertFalse($resBr->isValid(), 'Guard no modo CN reprova produto que tem estoque no Brasil');

        $itemCn = ['ship_from' => 'CN', 'title' => 'Produto China'];
        $resCn = $guard->validate($itemCn);
        $this->assertTrue($resCn->isValid(), 'Guard no modo CN aprova produto com despacho da China');
    }

    /**
     * Teste 8: WarehouseOriginGuard no Modo GLOBAL (ALL).
     * PROVA: Aceita produtos UNKNOWN, mas preserva a origem desconhecida.
     */
    public function testWarehouseOriginGuardPermissiveModeAll(): void
    {
        $guard = new WarehouseOriginGuard(WarehouseOriginResolverInterface::MODE_ALL);

        $itemUnknown = ['title' => 'Produto Genérico Sem Origem'];
        $res = $guard->validate($itemUnknown);
        $this->assertTrue($res->isValid(), 'Guard no modo ALL aceita produto UNKNOWN');
        $this->assertSame('UNKNOWN', $res->getDetails()['origin'] ?? '', 'Preserva a origem desconhecida no detalhe');
    }

    /**
     * Teste 9: Lógica de Fallback Zero-Contaminação.
     * PROVA: Quando solicitada origem BR, NENHUM produto CN ou UNKNOWN entra pelo fallback.
     */
    public function testZeroContaminationCuratedFallbackLogic(): void
    {
        $resolver = new WarehouseOriginResolver();

        $mockCuratedCatalog = [
            ['product_title' => 'Item 1', 'ship_from' => 'BR'],
            ['product_title' => 'Item 2', 'ship_from' => 'CN'],
            ['product_title' => 'Item 3', 'ship_from' => 'CN'],
            ['product_title' => 'Item 4'], // UNKNOWN
        ];

        // Filtro com warehouseOrigin = 'BR'
        $filteredBr = array_values(array_filter($mockCuratedCatalog, function (array $item) use ($resolver): bool {
            return $resolver->resolve($item, (string)($item['product_title'] ?? '')) === WarehouseOriginResolverInterface::ORIGIN_BR;
        }));

        $this->assertCount(1, $filteredBr, 'Filtro BR no fallback isola estritamente apenas 1 item BR');
        $this->assertSame('BR', $filteredBr[0]['ship_from'], 'Item sobrevivente do fallback possui ship_from = BR comprovado');
    }

    /**
     * Teste 10: Array to string conversion resilience (AliExpress frontend payload).
     */
    public function testWarehouseOriginGuardHandlesStructuredArrayTitleWithoutArrayToStringConversion(): void
    {
        $guard = new WarehouseOriginGuard(WarehouseOriginResolverInterface::MODE_ALL);

        $itemWithArrayTitle = [
            'id' => 1005009988776655,
            'title' => [
                'displayTitle' => 'Vestido Feminino Floral Casual Elegante',
                'title' => 'Vestido Feminino Floral',
            ],
            'ship_from' => 'BR',
        ];

        $res = $guard->validate($itemWithArrayTitle);
        $this->assertTrue($res->isValid(), 'Guard processa itens com title em array estruturado sem erro');
        $this->assertSame('BR', $res->getDetails()['origin'] ?? '');
    }
}
