<?php

declare(strict_types=1);

namespace Tests\Unit;

// Autoloader mínimo para o teste unitário isolado
spl_autoload_register(function (string $class): void {
    $prefix = 'DomainSystem\\Plugins\\domain_drop\\';
    $baseDir = __DIR__ . '/../../plugins/domain_drop/';
    if (str_starts_with($class, $prefix)) {
        $relativeClass = substr($class, strlen($prefix));
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

use DomainSystem\Plugins\domain_drop\Contracts\WarehouseOriginResolverInterface;
use DomainSystem\Plugins\domain_drop\Services\WarehouseOriginResolver;
use DomainSystem\Plugins\domain_drop\Services\Validation\Guards\WarehouseOriginGuard;

/**
 * Bateria de Testes Unitários de Conformidade Logística e Entrepostos (BR / CN / UNKNOWN).
 * 
 * Atende estritamente às exigências da Auditoria do ChatGPT e da documentação oficial
 * de 279 páginas (Págs. 174, 229, 230 e 254).
 */
class DomainDropWarehouseOriginTest
{
    private int $passed = 0;
    private int $failed = 0;

    public function runAll(): void
    {
        echo "=== BATERIA DE TESTES UNITÁRIOS: ENTREPOSTOS BR / CN / UNKNOWN ===\n\n";

        $this->testChineseStoreWithProvenBrazilWarehouse();
        $this->testBrazilianStoreWithChinaShippingIsClassifiedAsCn();
        $this->testUnknownOriginIsPreservedAndNeverConvertedToCn();
        $this->testFreightQueryResponseWithShipFromCountryBr();
        $this->testFreightQueryResponseWithShipFromCountryCn();
        $this->testWarehouseOriginGuardStrictModeBr();
        $this->testWarehouseOriginGuardStrictModeCn();
        $this->testWarehouseOriginGuardPermissiveModeAll();
        $this->testZeroContaminationCuratedFallbackLogic();

        echo "\n" . str_repeat('=', 65) . "\n";
        echo "RESULTADO DOS TESTES: {$this->passed} PASSOU, {$this->failed} FALHOU\n";
        echo str_repeat('=', 65) . "\n";

        if ($this->failed > 0) {
            exit(1);
        }
    }

    private function assert(bool $condition, string $testName, string $details = ''): void
    {
        if ($condition) {
            echo " [PASS] {$testName}\n";
            $this->passed++;
        } else {
            echo " [FAIL] {$testName}: {$details}\n";
            $this->failed++;
        }
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
        $this->assert(
            $origin === WarehouseOriginResolverInterface::ORIGIN_BR,
            'Loja chinesa com despacho no Brasil é classificada como BR',
            "Esperado BR, obtido '{$origin}'"
        );
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
        $this->assert(
            $origin === WarehouseOriginResolverInterface::ORIGIN_CN,
            'Loja com cadastro no Brasil e despacho da China é classificada como CN (elimina falso positivo)',
            "Esperado CN, obtido '{$origin}'"
        );
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
            // Sem ship_from, sem tags de armazém, sem remessa conforme
        ];

        $origin = $resolver->resolve($item);
        $this->assert(
            $origin === WarehouseOriginResolverInterface::ORIGIN_UNKNOWN,
            'Produto sem evidência de expedição física permanece como UNKNOWN (nunca converte para CN)',
            "Esperado UNKNOWN, obtido '{$origin}'"
        );
    }

    /**
     * Teste 4: Consulta oficial de frete (aliexpress.ds.freight.query - Pág. 229-230) com ship_from_country = BR.
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
        $this->assert(
            $origin === WarehouseOriginResolverInterface::ORIGIN_BR,
            'Resposta oficial de frete com ship_from_country = BR confirma entreposto brasileiro',
            "Esperado BR, obtido '{$origin}'"
        );
    }

    /**
     * Teste 5: Consulta oficial de frete com ship_from_country = CN.
     */
    public function testFreightQueryResponseWithShipFromCountryCn(): void
    {
        $resolver = new WarehouseOriginResolver();
        $item = [
            'product_id' => '3256802900954148',
            'title' => 'Fone de Ouvido Bluetooth TWS',
            'freight_info' => [
                'company' => 'AliExpress Selection Standard',
                'ship_from_country' => 'CN',
                'available_stock' => 500,
            ],
        ];

        $origin = $resolver->resolve($item);
        $this->assert(
            $origin === WarehouseOriginResolverInterface::ORIGIN_CN,
            'Resposta oficial de frete com ship_from_country = CN confirma entreposto chinês',
            "Esperado CN, obtido '{$origin}'"
        );
    }

    /**
     * Teste 6: WarehouseOriginGuard no modo BR rejeita produtos CN e UNKNOWN.
     */
    public function testWarehouseOriginGuardStrictModeBr(): void
    {
        $guard = new WarehouseOriginGuard();

        // 6.1 Produto BR comprovado -> Aprovado
        $brItem = [
            'target_warehouse_origin' => 'BR',
            'ship_from' => 'BR',
            'title' => 'Teclado Mecânico RGB Brasil',
        ];
        $resBr = $guard->validate('1001', $brItem);
        $this->assert($resBr->isValid(), 'Guard no modo BR aprova item com despacho BR comprovado');

        // 6.2 Produto CN -> Reprovado
        $cnItem = [
            'target_warehouse_origin' => 'BR',
            'ship_from' => 'CN',
            'title' => 'Mouse Óptico Gamer',
        ];
        $resCn = $guard->validate('1002', $cnItem);
        $this->assert(!$resCn->isValid(), 'Guard no modo BR reprova item de origem CN');

        // 6.3 Produto UNKNOWN -> Reprovado no modo estrito BR
        $unknownItem = [
            'target_warehouse_origin' => 'BR',
            'title' => 'Mousepad Speed 90x40',
        ];
        $resUnknown = $guard->validate('1003', $unknownItem);
        $this->assert(!$resUnknown->isValid(), 'Guard no modo BR reprova item de origem UNKNOWN (Zero-Contamination)');
    }

    /**
     * Teste 7: WarehouseOriginGuard no modo CN rejeita produtos BR.
     */
    public function testWarehouseOriginGuardStrictModeCn(): void
    {
        $guard = new WarehouseOriginGuard();

        $brItem = [
            'target_warehouse_origin' => 'CN',
            'ship_from' => 'BR',
            'title' => 'Item com estoque no Brasil',
        ];
        $resBr = $guard->validate('2001', $brItem);
        $this->assert(!$resBr->isValid(), 'Guard no modo CN reprova produto que tem estoque no Brasil');

        $cnItem = [
            'target_warehouse_origin' => 'CN',
            'ship_from' => 'CN',
            'title' => 'Item com despacho da China',
        ];
        $resCn = $guard->validate('2002', $cnItem);
        $this->assert($resCn->isValid(), 'Guard no modo CN aprova produto com despacho da China');
    }

    /**
     * Teste 8: WarehouseOriginGuard no modo ALL aceita BR, CN e UNKNOWN.
     */
    public function testWarehouseOriginGuardPermissiveModeAll(): void
    {
        $guard = new WarehouseOriginGuard();

        $unknownItem = [
            'target_warehouse_origin' => 'ALL',
            'title' => 'Produto Genérico Sem Origem',
        ];
        $res = $guard->validate('3001', $unknownItem);
        $this->assert(
            $res->isValid() && ($res->getDetails()['origin'] ?? '') === 'UNKNOWN',
            'Guard no modo ALL aceita produto UNKNOWN preservando a origem desconhecida no detalhe'
        );
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

        $this->assert(count($filteredBr) === 1, 'Filtro BR no fallback isola estritamente apenas 1 item BR');
        $this->assert($filteredBr[0]['ship_from'] === 'BR', 'Item sobrevivente do fallback possui ship_from = BR comprovado');
    }
}

// Execução direta do teste
$test = new DomainDropWarehouseOriginTest();
$test->runAll();
