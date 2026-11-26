<?php

declare(strict_types=1);

namespace Tests\Unit;

use DomainSystem\Plugins\domain_drop\Services\Assembly\ProductAssemblyContext;
use DomainSystem\Plugins\domain_drop\Services\Assembly\ProductAssemblyLine;
use DomainSystem\Plugins\domain_drop\Services\Assembly\Stations\CandidateNormalizationStation;
use DomainSystem\Plugins\domain_drop\Services\Assembly\Stations\ProductDetailExtractionStation;
use DomainSystem\Plugins\domain_drop\Services\Assembly\Stations\FreightInspectionStation;
use DomainSystem\Plugins\domain_drop\Services\Assembly\Stations\MediaSanitizationStation;
use DomainSystem\Plugins\domain_drop\Services\Assembly\Stations\FinancialPricingStation;
use DomainSystem\Plugins\domain_drop\Services\Assembly\Stations\CatalogPackagingStation;
use PHPUnit\Framework\TestCase;

/**
 * Bateria de Testes Unitários da Linha de Montagem de Produtos (Pipeline Pattern / SOLID).
 */
class ProductAssemblyLineTest extends TestCase
{
    protected function setUp(): void
    {
        if (!is_dir(dirname(__DIR__, 2) . '/src/Plugins/domain_drop')) {
            $this->markTestSkipped('Plugin agregado [domain_drop] não está presente no checkout.');
            return;
        }
        parent::setUp();
    }

    public function testStation1CandidateNormalizationApprovesValidCandidate(): void
    {
        $s1 = new CandidateNormalizationStation();
        $ctx = new ProductAssemblyContext(['id' => 12345678901, 'title' => 'Fone Bluetooth']);
        $res = $s1->process($ctx);

        $this->assertFalse($res->isAborted(), 'Estação 1 aprova candidato válido com ID e título');
        $this->assertSame('12345678901', $res->get('id'), 'Estação 1 normaliza ID para string canônica');
    }

    public function testStation1CandidateNormalizationAbortsCandidateWithoutId(): void
    {
        $s1 = new CandidateNormalizationStation();
        $ctx = new ProductAssemblyContext(['title' => 'Produto sem ID']);
        $res = $s1->process($ctx);

        $this->assertTrue($res->isAborted(), 'Estação 1 aborta candidato sem ID');
        $this->assertSame('CandidateNormalization', $res->getAbortedStation(), 'Estação 1 registra seu nome na rejeição');
    }

    public function testStation2ProductDetailExtraction(): void
    {
        $s2 = new ProductDetailExtractionStation();
        $ctx = new ProductAssemblyContext([
            'id' => '1001',
            'title' => 'Smartwatch Pro',
            'cost_price_usd' => 15.00,
            'main_image_url' => 'https://ae-pic.com/img1.jpg',
        ]);
        $res = $s2->process($ctx);

        $this->assertFalse($res->isAborted(), 'Estação 2 processa dados com preço e imagem já presentes');
        $this->assertEquals(15.00, $res->get('cost_price_usd'), 'Estação 2 preserva preço de custo de entrada');
    }

    public function testStation3FreightInspection(): void
    {
        $s3Br = new FreightInspectionStation('BR');
        $ctxBr = new ProductAssemblyContext(['id' => '1001', 'ship_from' => 'BR']);
        $resBr = $s3Br->process($ctxBr);
        $this->assertFalse($resBr->isAborted(), 'Estação 3 aprova produto BR no modo BR');

        $ctxCn = new ProductAssemblyContext(['id' => '1002', 'ship_from' => 'CN']);
        $resCn = $s3Br->process($ctxCn);
        $this->assertTrue($resCn->isAborted(), 'Estação 3 rejeita produto CN quando modo BR é exigido');

        $ctxUnknown = new ProductAssemblyContext(['id' => '1003']);
        $resUnknown = $s3Br->process($ctxUnknown);
        $this->assertTrue($resUnknown->isAborted(), 'Estação 3 rejeita produto UNKNOWN no modo BR (Zero-Contamination)');

        $ctxUnknownAll = new ProductAssemblyContext(['id' => '1003']);
        $s3All = new FreightInspectionStation('ALL');
        $resAll = $s3All->process($ctxUnknownAll);
        $this->assertFalse($resAll->isAborted(), 'Estação 3 aceita UNKNOWN no modo ALL');
        $this->assertSame('UNKNOWN', $resAll->get('warehouse_origin'), 'Preserva rótulo UNKNOWN');
    }

    public function testStation4MediaSanitization(): void
    {
        $s4 = new MediaSanitizationStation();

        $ctxValid = new ProductAssemblyContext([
            'id' => '1001',
            'main_image_url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S552697abcf6046e7b172a5a54eeb3d5eu.jpg'
        ]);
        $resValid = $s4->process($ctxValid);
        $this->assertFalse($resValid->isAborted(), 'Estação 4 aceita imagem autêntica válida');

        $ctxNoImg = new ProductAssemblyContext(['id' => '1002', 'main_image_url' => '']);
        $resNoImg = $s4->process($ctxNoImg);
        $this->assertTrue($resNoImg->isAborted(), 'Estação 4 rejeita produto sem imagem');

        $ctxSynth = new ProductAssemblyContext([
            'id' => '1003',
            'main_image_url' => 'https://ae-pic.com/default_123.jpg'
        ]);
        $resSynth = $s4->process($ctxSynth);
        $this->assertTrue($resSynth->isAborted(), 'Estação 4 rejeita terminantemente imagem sintética/placeholder (default_*.jpg)');
    }

    public function testStation5FinancialPricing(): void
    {
        $s5 = new FinancialPricingStation(rate: 5.50, markup: 1.50);
        $ctx = new ProductAssemblyContext(['id' => '1001', 'cost_price_usd' => 10.00]);
        $res = $s5->process($ctx);

        $this->assertFalse($res->isAborted(), 'Estação 5 processa precificação de produto válido');
        $this->assertEquals(55.00, $res->get('cost_price_brl'), 'Estação 5 converte USD 10.00 a R$ 5.50 para R$ 55.00');
        $this->assertEquals(82.50, $res->get('sale_price_brl'), 'Estação 5 aplica markup 1.5 resultando em R$ 82.50');
        $this->assertEquals(27.50, $res->get('profit_brl'), 'Estação 5 calcula lucro exato de R$ 27.50');
    }

    public function testFullAssemblyLineExecutionModeBr(): void
    {
        $lineBr = new ProductAssemblyLine(warehouseMode: 'BR', exchangeRate: 5.50, markup: 1.50);
        $lineBr->addStation(new CandidateNormalizationStation())
               ->addStation(new ProductDetailExtractionStation())
               ->addStation(new FreightInspectionStation('BR'))
               ->addStation(new MediaSanitizationStation())
               ->addStation(new FinancialPricingStation(5.50, 1.50))
               ->addStation(new CatalogPackagingStation());

        $candidates = [
            ['id' => '1001', 'title' => 'Item BR Válido', 'ship_from' => 'BR', 'cost_price_usd' => 10, 'main_image_url' => 'https://ae-pic.com/real.jpg'],
            ['id' => '1002', 'title' => 'Item CN', 'ship_from' => 'CN', 'cost_price_usd' => 10, 'main_image_url' => 'https://ae-pic.com/real.jpg'],
            ['id' => '1003', 'title' => 'Item Unknown', 'cost_price_usd' => 10, 'main_image_url' => 'https://ae-pic.com/real.jpg'],
            ['id' => '1004', 'title' => 'Item Placeholder', 'ship_from' => 'BR', 'cost_price_usd' => 10, 'main_image_url' => 'https://ae-pic.com/default_test.jpg'],
            ['id' => '1005', 'title' => 'Item Grátis/Vício', 'ship_from' => 'BR', 'cost_price_usd' => 0.0, 'main_image_url' => 'https://ae-pic.com/real.jpg'],
            ['title' => 'Item Sem ID', 'ship_from' => 'BR', 'cost_price_usd' => 10, 'main_image_url' => 'https://ae-pic.com/real.jpg'],
        ];

        $approved = $lineBr->assembleBatch($candidates);

        $this->assertCount(1, $approved, 'Linha em modo BR aprova estritamente apenas o produto BR legítimo (1 de 6)');
        $this->assertSame('1001', $approved[0]->aliexpressId, 'O único aprovado é o item 1001');
        $this->assertCount(5, $lineBr->getRejections(), 'Os outros 5 produtos irregulares foram rejeitados pela esteira');
        $this->assertSame(1, $lineBr->getTelemetry()['br_confirmed'], 'Telemetria computa 1 BR confirmado');
    }

    public function testFullAssemblyLineExecutionModeAll(): void
    {
        $lineAll = new ProductAssemblyLine(warehouseMode: 'ALL', exchangeRate: 5.50, markup: 1.50);
        $lineAll->addStation(new CandidateNormalizationStation())
                ->addStation(new ProductDetailExtractionStation())
                ->addStation(new FreightInspectionStation('ALL'))
                ->addStation(new MediaSanitizationStation())
                ->addStation(new FinancialPricingStation(5.50, 1.50))
                ->addStation(new CatalogPackagingStation());

        $candidates = [
            ['id' => '1001', 'title' => 'Item BR Válido', 'ship_from' => 'BR', 'cost_price_usd' => 10, 'main_image_url' => 'https://ae-pic.com/real.jpg'],
            ['id' => '1002', 'title' => 'Item CN', 'ship_from' => 'CN', 'cost_price_usd' => 10, 'main_image_url' => 'https://ae-pic.com/real.jpg'],
            ['id' => '1003', 'title' => 'Item Unknown', 'cost_price_usd' => 10, 'main_image_url' => 'https://ae-pic.com/real.jpg'],
            ['id' => '1004', 'title' => 'Item Placeholder', 'ship_from' => 'BR', 'cost_price_usd' => 10, 'main_image_url' => 'https://ae-pic.com/default_test.jpg'],
            ['id' => '1005', 'title' => 'Item Grátis/Vício', 'ship_from' => 'BR', 'cost_price_usd' => 0.0, 'main_image_url' => 'https://ae-pic.com/real.jpg'],
            ['title' => 'Item Sem ID', 'ship_from' => 'BR', 'cost_price_usd' => 10, 'main_image_url' => 'https://ae-pic.com/real.jpg'],
        ];

        $approvedAll = $lineAll->assembleBatch($candidates);

        $this->assertCount(3, $approvedAll, 'Linha em modo ALL aprova produtos legítimos BR, CN e UNKNOWN');
        $this->assertCount(3, $lineAll->getRejections(), 'Linha em modo ALL descarta 3 produtos com vícios');
    }
}
