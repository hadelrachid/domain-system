<?php

declare(strict_types=1);

namespace Tests\Unit;

use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Plugins\domain_drop\Contracts\CategoryTaxonomyInterface;
use DomainSystem\Plugins\domain_drop\Controllers\DropshipAdminController;
use DomainSystem\Plugins\domain_drop\DTO\DropshipProductDto;
use DomainSystem\Plugins\domain_drop\Repositories\DomainDropSchemaEnsurer;
use DomainSystem\Plugins\domain_drop\Repositories\PdoDropshipCatalogRepository;
use DomainSystem\Plugins\domain_drop\Services\AliExpressApiClient;
use DomainSystem\Plugins\domain_drop\Services\CategoryTaxonomyService;
use DomainSystem\Plugins\domain_drop\Services\CurrencyConverterService;
use DomainSystem\Plugins\domain_drop\Services\ProductMiningService;
use PDO;
use PHPUnit\Framework\TestCase;

class DomainDropCatalogPurgeAndComparisonTest extends TestCase
{
    private PDO $pdo;
    private PdoDropshipCatalogRepository $catalogRepo;
    private CategoryTaxonomyInterface $taxonomy;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        DomainDropSchemaEnsurer::resetCache();
        DomainDropSchemaEnsurer::ensure($this->pdo);

        $this->taxonomy = new CategoryTaxonomyService();
        $this->catalogRepo = new PdoDropshipCatalogRepository($this->pdo, $this->taxonomy);
    }

    public function testClearAllPreservesOrderedProducts(): void
    {
        // 1. Insere 4 produtos
        $p1Id = $this->catalogRepo->save(new DropshipProductDto(
            aliexpressId: '1001',
            title: 'Produto Com Pedido 1',
            slug: 'prod-1',
            costPriceUsd: 10.0,
            salePriceBrl: 100.0,
            mainImageUrl: 'https://example.com/1.jpg',
            images: [],
            category: 'Eletrônicos & Telefonia',
            description: 'Desc 1',
            status: 'published'
        ));

        $p2Id = $this->catalogRepo->save(new DropshipProductDto(
            aliexpressId: '1002',
            title: 'Produto Com Pedido 2',
            slug: 'prod-2',
            costPriceUsd: 15.0,
            salePriceBrl: 150.0,
            mainImageUrl: 'https://example.com/2.jpg',
            images: [],
            category: 'Eletrônicos & Telefonia',
            description: 'Desc 2',
            status: 'published'
        ));

        $p3Id = $this->catalogRepo->save(new DropshipProductDto(
            aliexpressId: '1003',
            title: 'Produto Teste Sem Pedido 1',
            slug: 'prod-3',
            costPriceUsd: 5.0,
            salePriceBrl: 50.0,
            mainImageUrl: 'https://example.com/3.jpg',
            images: [],
            category: 'Eletrônicos & Telefonia',
            description: 'Desc 3',
            status: 'published'
        ));

        $p4Id = $this->catalogRepo->save(new DropshipProductDto(
            aliexpressId: '1004',
            title: 'Produto Teste Sem Pedido 2',
            slug: 'prod-4',
            costPriceUsd: 8.0,
            salePriceBrl: 80.0,
            mainImageUrl: 'https://example.com/4.jpg',
            images: [],
            category: 'Casa, Iluminação & Eletro',
            description: 'Desc 4',
            status: 'published'
        ));

        // 2. Cria pedido para o produto '1001' e '1002'
        $this->pdo->exec("
            INSERT INTO domain_drop_orders (order_ref, customer_name, customer_email, aliexpress_product_id, status)
            VALUES 
            ('ORD-001', 'Cliente 1', 'cli1@test.com', '1001', 'paid'),
            ('ORD-002', 'Cliente 2', 'cli2@test.com', '1002', 'shipped')
        ");

        $this->assertSame(4, $this->catalogRepo->count());

        // 3. Executa clearAll preservando pedidos
        $deleted = $this->catalogRepo->clearAll(preserveOrdered: true);
        $this->assertSame(2, $deleted);
        $this->assertSame(2, $this->catalogRepo->count());

        // Confirma que apenas os 2 vinculados a pedidos foram mantidos
        $this->assertNotNull($this->catalogRepo->findByAliExpressId('1001'));
        $this->assertNotNull($this->catalogRepo->findByAliExpressId('1002'));
        $this->assertNull($this->catalogRepo->findByAliExpressId('1003'));
        $this->assertNull($this->catalogRepo->findByAliExpressId('1004'));
    }

    public function testPurgeDraftsAndPublishAll(): void
    {
        $this->catalogRepo->save(new DropshipProductDto(
            aliexpressId: '2001',
            title: 'Produto Publicado',
            slug: 'prod-pub',
            costPriceUsd: 10.0,
            salePriceBrl: 100.0,
            mainImageUrl: 'https://example.com/pub.jpg',
            images: [],
            category: 'Eletrônicos & Telefonia',
            description: 'Desc',
            status: 'published'
        ));

        $this->catalogRepo->save(new DropshipProductDto(
            aliexpressId: '2002',
            title: 'Produto Rascunho 1',
            slug: 'prod-draft-1',
            costPriceUsd: 10.0,
            salePriceBrl: 100.0,
            mainImageUrl: 'https://example.com/d1.jpg',
            images: [],
            category: 'Eletrônicos & Telefonia',
            description: 'Desc',
            status: 'draft'
        ));

        $this->catalogRepo->save(new DropshipProductDto(
            aliexpressId: '2003',
            title: 'Produto Rascunho 2',
            slug: 'prod-draft-2',
            costPriceUsd: 10.0,
            salePriceBrl: 100.0,
            mainImageUrl: 'https://example.com/d2.jpg',
            images: [],
            category: 'Eletrônicos & Telefonia',
            description: 'Desc',
            status: 'draft'
        ));

        $this->assertSame(3, $this->catalogRepo->count());

        // Teste de publicar todos
        $published = $this->catalogRepo->publishAll();
        $this->assertSame(2, $published);
        $this->assertSame(3, $this->catalogRepo->count(['status' => 'published']));

        // Converte 1 de volta para draft para testar o purgeDrafts
        $p2 = $this->catalogRepo->findByAliExpressId('2002');
        $this->assertNotNull($p2);
        $this->catalogRepo->updateStatus((int)$p2->id, 'draft');

        $purged = $this->catalogRepo->purgeDrafts();
        $this->assertSame(1, $purged);
        $this->assertSame(2, $this->catalogRepo->count());
        $this->assertNull($this->catalogRepo->findByAliExpressId('2002'));
    }

    public function testFindSimilarOffersOrdersByPriceAscending(): void
    {
        $pMainId = $this->catalogRepo->save(new DropshipProductDto(
            aliexpressId: '3001',
            title: 'Smartwatch Series 9 Original',
            slug: 'smartwatch-main',
            costPriceUsd: 20.0,
            salePriceBrl: 199.90,
            mainImageUrl: 'https://example.com/main.jpg',
            images: [],
            category: 'Relógios & Smartwatches',
            description: 'Main watch',
            status: 'published'
        ));

        // Oferta 1: R$ 149,90 (mais barata)
        $pCheapId = $this->catalogRepo->save(new DropshipProductDto(
            aliexpressId: '3002',
            title: 'Smartwatch Series 9 Vendedor B',
            slug: 'smartwatch-b',
            costPriceUsd: 15.0,
            salePriceBrl: 149.90,
            mainImageUrl: 'https://example.com/cheap.jpg',
            images: [],
            category: 'Relógios & Smartwatches',
            description: 'Cheaper watch',
            status: 'published'
        ));

        // Oferta 2: R$ 179,90
        $pMidId = $this->catalogRepo->save(new DropshipProductDto(
            aliexpressId: '3003',
            title: 'Smartwatch Series 9 Vendedor C',
            slug: 'smartwatch-c',
            costPriceUsd: 18.0,
            salePriceBrl: 179.90,
            mainImageUrl: 'https://example.com/mid.jpg',
            images: [],
            category: 'Relógios & Smartwatches',
            description: 'Mid watch',
            status: 'published'
        ));

        // Oferta 3: R$ 220,00 (mais cara)
        $pExpId = $this->catalogRepo->save(new DropshipProductDto(
            aliexpressId: '3004',
            title: 'Smartwatch Series 9 Vendedor D',
            slug: 'smartwatch-d',
            costPriceUsd: 22.0,
            salePriceBrl: 220.00,
            mainImageUrl: 'https://example.com/exp.jpg',
            images: [],
            category: 'Relógios & Smartwatches',
            description: 'Exp watch',
            status: 'published'
        ));

        $similar = $this->catalogRepo->findSimilarOffers(
            excludeId: $pMainId,
            category: 'Relógios & Smartwatches',
            title: 'Smartwatch Series 9',
            limit: 4
        );

        $this->assertCount(3, $similar);
        // Primeiro item deve ser o mais acessível (R$ 149,90)
        $this->assertSame($pCheapId, $similar[0]->id);
        $this->assertSame(149.90, $similar[0]->salePriceBrl);

        // Segundo item deve ser R$ 179,90
        $this->assertSame($pMidId, $similar[1]->id);

        // O produto principal não pode estar na lista de similares
        foreach ($similar as $item) {
            $this->assertNotSame($pMainId, $item->id);
        }
    }

    public function testAliExpressApiClientSignatureAndCleanParams(): void
    {
        $client = new AliExpressApiClient(
            appKey: '560750',
            appSecret: '5mkoNtGJmwTXY60PpUUekNOVW58BSVWy'
        );

        $params = [
            'feed_name'   => 'DS bestseller',
            'category_id' => '', // deve ser omitido da assinatura e do corpo
            'country'     => 'BR',
        ];

        // Limpeza de parâmetros nulos / vazios
        $clean = array_filter($params, fn($v) => $v !== null && $v !== '');
        $this->assertArrayNotHasKey('category_id', $clean);
        $this->assertArrayHasKey('feed_name', $clean);
        $this->assertArrayHasKey('country', $clean);

        $sig = $client->generateSignature('aliexpress.ds.recommend.feed.get', $clean);
        $this->assertNotEmpty($sig);
        $this->assertSame(64, strlen($sig)); // SHA256 hex string = 64 chars
        $this->assertSame(strtoupper($sig), $sig); // IOP signatures are uppercase
    }
}
