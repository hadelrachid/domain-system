<?php

declare(strict_types=1);

// Carregamento de autoload do DomainSystem ou classes do plugin
require_once __DIR__ . '/../../plugins/domain_drop/Contracts/Assembly/AssemblyStationInterface.php';
require_once __DIR__ . '/../../plugins/domain_drop/Contracts/Assembly/ProductAssemblyContextInterface.php';
require_once __DIR__ . '/../../plugins/domain_drop/Contracts/Assembly/ProductAssemblyLineInterface.php';
require_once __DIR__ . '/../../plugins/domain_drop/Contracts/WarehouseOriginResolverInterface.php';
require_once __DIR__ . '/../../plugins/domain_drop/Contracts/CurrencyConverterInterface.php';
require_once __DIR__ . '/../../plugins/domain_drop/Contracts/AliExpressApiClientInterface.php';

require_once __DIR__ . '/../../plugins/domain_drop/Services/Assembly/ProductAssemblyContext.php';
require_once __DIR__ . '/../../plugins/domain_drop/Services/Assembly/ProductAssemblyLine.php';
require_once __DIR__ . '/../../plugins/domain_drop/Services/Assembly/Stations/CandidateNormalizationStation.php';
require_once __DIR__ . '/../../plugins/domain_drop/Services/Assembly/Stations/ProductDetailExtractionStation.php';
require_once __DIR__ . '/../../plugins/domain_drop/Services/Assembly/Stations/FreightInspectionStation.php';
require_once __DIR__ . '/../../plugins/domain_drop/Services/Assembly/Stations/MediaSanitizationStation.php';
require_once __DIR__ . '/../../plugins/domain_drop/Services/Assembly/Stations/FinancialPricingStation.php';
require_once __DIR__ . '/../../plugins/domain_drop/Services/Assembly/Stations/CatalogPackagingStation.php';

use DomainSystem\Plugins\domain_drop\Services\Assembly\ProductAssemblyContext;
use DomainSystem\Plugins\domain_drop\Services\Assembly\ProductAssemblyLine;
use DomainSystem\Plugins\domain_drop\Services\Assembly\Stations\CandidateNormalizationStation;
use DomainSystem\Plugins\domain_drop\Services\Assembly\Stations\ProductDetailExtractionStation;
use DomainSystem\Plugins\domain_drop\Services\Assembly\Stations\FreightInspectionStation;
use DomainSystem\Plugins\domain_drop\Services\Assembly\Stations\MediaSanitizationStation;
use DomainSystem\Plugins\domain_drop\Services\Assembly\Stations\FinancialPricingStation;
use DomainSystem\Plugins\domain_drop\Services\Assembly\Stations\CatalogPackagingStation;

echo "=== BATERIA DE TESTES UNITÁRIOS: LINHA DE MONTAGEM (ASSEMBLY LINE SOLID) ===\n\n";

$testsPassed = 0;
$testsFailed = 0;

function assertCondition(bool $condition, string $description): void {
    global $testsPassed, $testsFailed;
    if ($condition) {
        echo " [PASS] " . $description . "\n";
        $testsPassed++;
    } else {
        echo " [FAIL] " . $description . "\n";
        $testsFailed++;
    }
}

// 1. Testes da Estação 1: Normalização
$s1 = new CandidateNormalizationStation();
$ctx1 = new ProductAssemblyContext(['id' => 12345678901, 'title' => 'Fone Bluetooth']);
$res1 = $s1->process($ctx1);
assertCondition(!$res1->isAborted(), "Estação 1 aprova candidato válido com ID e título");
assertCondition($res1->get('id') === '12345678901', "Estação 1 normaliza ID para string canônica");

$ctx1Fail = new ProductAssemblyContext(['title' => 'Produto sem ID']);
$res1Fail = $s1->process($ctx1Fail);
assertCondition($res1Fail->isAborted(), "Estação 1 aborta candidato sem ID");
assertCondition($res1Fail->getAbortedStation() === 'CandidateNormalization', "Estação 1 registra seu nome na rejeição");

// 2. Testes da Estação 2: Detalhes e SKUs
$s2 = new ProductDetailExtractionStation();
$ctx2 = new ProductAssemblyContext([
    'id' => '1005001',
    'title' => 'Relógio Masculino',
    'price' => 50.00,
    'main_image' => 'https://ae01.alicdn.com/kf/relogio.jpg'
]);
$res2 = $s2->process($ctx2);
assertCondition(!$res2->isAborted(), "Estação 2 processa dados com preço e imagem já presentes");
assertCondition($res2->get('raw_price') === 50.00, "Estação 2 preserva preço de custo de entrada");

// 3. Testes da Estação 3: Logística e Entreposto (Tri-State & Zero-Contamination)
$s3 = new FreightInspectionStation();

// Caso BR em modo BR
$ctx3Br = new ProductAssemblyContext(['id' => '1', 'title' => 'Item BR', 'ship_from' => 'BR'], ['warehouse' => 'BR']);
$res3Br = $s3->process($ctx3Br);
assertCondition(!$res3Br->isAborted() && $res3Br->get('warehouse_origin') === 'BR', "Estação 3 aprova produto BR no modo BR");

// Caso CN em modo BR -> REJEIÇÃO
$ctx3Cn = new ProductAssemblyContext(['id' => '2', 'title' => 'Item CN', 'ship_from' => 'CN'], ['warehouse' => 'BR']);
$res3Cn = $s3->process($ctx3Cn);
assertCondition($res3Cn->isAborted(), "Estação 3 rejeita produto CN quando modo BR é exigido");

// Caso UNKNOWN em modo BR -> REJEIÇÃO (Zero-Contamination)
$ctx3Unk = new ProductAssemblyContext(['id' => '3', 'title' => 'Item Desconhecido'], ['warehouse' => 'BR']);
$res3Unk = $s3->process($ctx3Unk);
assertCondition($res3Unk->isAborted(), "Estação 3 rejeita produto UNKNOWN no modo BR (Zero-Contamination)");

// Caso UNKNOWN em modo ALL -> APROVAÇÃO mantendo UNKNOWN
$ctx3All = new ProductAssemblyContext(['id' => '4', 'title' => 'Item Global'], ['warehouse' => 'ALL']);
$res3All = $s3->process($ctx3All);
assertCondition(!$res3All->isAborted() && $res3All->get('warehouse_origin') === 'UNKNOWN', "Estação 3 aceita UNKNOWN no modo ALL preservando rótulo");

// 4. Testes da Estação 4: Higienização de Mídia
$s4 = new MediaSanitizationStation();

$ctx4Valid = new ProductAssemblyContext(['main_image' => 'https://ae01.alicdn.com/kf/S123456.jpg']);
$res4Valid = $s4->process($ctx4Valid);
assertCondition(!$res4Valid->isAborted(), "Estação 4 aceita imagem autêntica válida");

$ctx4Empty = new ProductAssemblyContext(['main_image' => '']);
$res4Empty = $s4->process($ctx4Empty);
assertCondition($res4Empty->isAborted(), "Estação 4 rejeita produto sem imagem");

$ctx4Synth = new ProductAssemblyContext(['main_image' => 'https://ae-pic-a1.aliexpress-media.com/kf/default_a1b2c3d4.jpg']);
$res4Synth = $s4->process($ctx4Synth);
assertCondition($res4Synth->isAborted(), "Estação 4 rejeita terminantemente imagem sintética/placeholder (default_*.jpg)");

// 5. Testes da Estação 5: Motor Financeiro
$s5 = new FinancialPricingStation();
$ctx5 = new ProductAssemblyContext([
    'raw_price' => 10.00,
    'currency' => 'USD'
], ['markup' => 1.5, 'exchange_rate' => 5.50]);
$res5 = $s5->process($ctx5);
assertCondition(!$res5->isAborted(), "Estação 5 processa precificação de produto válido");
assertCondition($res5->get('cost_brl') === 55.00, "Estação 5 converte USD 10.00 a R$ 5.50 para R$ 55.00");
assertCondition($res5->get('price_brl') === 82.50, "Estação 5 aplica markup 1.5 resultando em R$ 82.50");
assertCondition($res5->get('profit_brl') === 27.50, "Estação 5 calcula lucro exato de R$ 27.50");

// 6. Testes da Linha de Montagem Completa (Orquestrador runBatch)
$pipeline = ProductAssemblyLine::createStandard();

$candidates = [
    // 1. Válido BR
    [
        'id' => '1001',
        'title' => 'Mochila Impermeável Nacional',
        'price' => 20.00,
        'currency' => 'USD',
        'ship_from' => 'BR',
        'main_image' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=800'
    ],
    // 2. Válido CN
    [
        'id' => '1002',
        'title' => 'Smartwatch China Central',
        'price' => 30.00,
        'currency' => 'USD',
        'ship_from' => 'CN',
        'main_image' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=800'
    ],
    // 3. Inválido: Sem ID
    [
        'title' => 'Item Sem ID',
        'price' => 15.00,
        'main_image' => 'https://ae01.alicdn.com/kf/foto.jpg'
    ],
    // 4. Inválido: Sem Imagem
    [
        'id' => '1004',
        'title' => 'Item Sem Imagem',
        'price' => 10.00,
        'main_image' => ''
    ],
    // 5. Inválido: Imagem Sintética
    [
        'id' => '1005',
        'title' => 'Item Imagem Fake',
        'price' => 10.00,
        'main_image' => 'https://ae-pic-a1.aliexpress-media.com/kf/default_12345678.jpg'
    ],
    // 6. Inválido: Preço Zero
    [
        'id' => '1006',
        'title' => 'Item Preço Zero',
        'price' => 0.00,
        'main_image' => 'https://ae01.alicdn.com/kf/foto.jpg'
    ]
];

// Execução em modo BR
$batchResultBr = $pipeline->runBatch($candidates, [
    'warehouse' => 'BR',
    'markup' => 1.4,
    'exchange_rate' => 5.50
]);

assertCondition(count($batchResultBr['approved']) === 1, "Linha em modo BR aprova estritamente apenas o produto BR legítimo (1 de 6)");
assertCondition($batchResultBr['approved'][0]['id'] === '1001', "O único aprovado é o item 1001");
assertCondition(count($batchResultBr['rejected']) === 5, "Os outros 5 produtos irregulares foram rejeitados pela esteira");
assertCondition($batchResultBr['telemetry']['br_confirmed'] === 1, "Telemetria computa 1 BR confirmado");

// Execução em modo ALL
$batchResultAll = $pipeline->runBatch($candidates, [
    'warehouse' => 'ALL',
    'markup' => 1.4,
    'exchange_rate' => 5.50
]);
assertCondition(count($batchResultAll['approved']) === 2, "Linha em modo ALL aprova 2 produtos legítimos (BR e CN)");
assertCondition(count($batchResultAll['rejected']) === 4, "Linha em modo ALL descarta 4 produtos com vícios");

echo "\n=================================================================\n";
echo "RESULTADO DOS TESTES: {$testsPassed} PASSOU, {$testsFailed} FALHOU\n";
echo "=================================================================\n";

if ($testsFailed > 0) {
    exit(1);
}
