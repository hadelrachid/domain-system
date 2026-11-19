<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Plugins\domain_drop\Contracts\AliExpressApiClientInterface;
use DomainSystem\Plugins\domain_drop\Services\AliExpressApiClient;
use DomainSystem\Plugins\domain_drop\Services\CategoryTaxonomyService;
use PHPUnit\Framework\TestCase;

class AliExpressOfficialCategoriesTest extends TestCase
{
    private string $cacheFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheFile = dirname(__DIR__, 2) . '/storage/cache/aliexpress_categories_pt.json';
        if (file_exists($this->cacheFile)) {
            @unlink($this->cacheFile);
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->cacheFile)) {
            @unlink($this->cacheFile);
        }
        parent::tearDown();
    }

    public function testGetOfficialCategoriesReturnsTreeAndCachesOnDisk(): void
    {
        $mockClient = $this->getMockBuilder(AliExpressApiClient::class)
            ->setConstructorArgs(['test_key', 'test_secret'])
            ->onlyMethods(['execute'])
            ->getMock();

        $mockPayload = [
            'resp_result' => [
                'result' => [
                    'total_result_count' => 3,
                    'categories' => [
                        ['category_id' => 200574005, 'category_name' => 'Roupa íntima'],
                        ['category_id' => 200000349, 'category_name' => 'Roupa íntima feminina', 'parent_category_id' => 200574005],
                        ['category_id' => 1524, 'category_name' => 'Bolsas e malas'],
                    ]
                ]
            ]
        ];

        // Deve chamar a API remota exatamente 1 vez
        $mockClient->expects($this->once())
            ->method('execute')
            ->with(
                $this->equalTo('aliexpress.ds.category.get'),
                $this->equalTo(['category_id' => '0', 'language' => 'pt'])
            )
            ->willReturn($mockPayload);

        // 1. Primeira chamada: busca via execute e salva cache
        $categories1 = $mockClient->getOfficialCategories(0, 'pt');
        $this->assertCount(3, $categories1);
        $this->assertSame('Roupa íntima', $categories1[0]['category_name']);
        $this->assertSame(200574005, $categories1[0]['category_id']);
        $this->assertFileExists($this->cacheFile);

        // 2. Segunda chamada imediata: deve ler do cache em disco sem chamar execute de novo
        $categories2 = $mockClient->getOfficialCategories(0, 'pt');
        $this->assertCount(3, $categories2);
        $this->assertSame($categories1, $categories2);
    }

    public function testGetOfficialCategoriesForceRefreshRequeriesApi(): void
    {
        $mockClient = $this->getMockBuilder(AliExpressApiClient::class)
            ->setConstructorArgs(['test_key', 'test_secret'])
            ->onlyMethods(['execute'])
            ->getMock();

        $mockClient->expects($this->exactly(2))
            ->method('execute')
            ->willReturn([
                'result' => [
                    'categories' => [
                        ['category_id' => 322, 'category_name' => 'Sapatos']
                    ]
                ]
            ]);

        $mockClient->getOfficialCategories(0, 'pt');
        // Segunda chamada com forceRefresh=true deve acionar a API novamente
        $categories = $mockClient->getOfficialCategories(0, 'pt', true);
        $this->assertCount(1, $categories);
        $this->assertSame('Sapatos', $categories[0]['category_name']);
    }

    public function testOfficialAliExpressCategoriesMapToCanonicalDepartmentsWithoutGuessing(): void
    {
        $taxonomy = new CategoryTaxonomyService();

        // 1. Roupa Íntima Oficial (ID 200574005)
        $catLingerie = $taxonomy->classifyFromText('Kit 4 Cuecas Boxer Sem Costura', 'Roupa íntima');
        $this->assertSame(CategoryTaxonomyService::CAT_LINGERIE, $catLingerie);

        $catCalcinha = $taxonomy->classifyFromText('Conjunto Calcinha e Sutiã Renda', 'Roupa íntima feminina');
        $this->assertSame(CategoryTaxonomyService::CAT_LINGERIE, $catCalcinha);

        // 2. Bolsas e Mochilas (ID 1524 / 202236005)
        $catMochila = $taxonomy->classifyFromText('Mochila Impermeável Masculina Executiva', 'Bolsas e malas');
        $this->assertSame(CategoryTaxonomyService::CAT_FASHION, $catMochila);

        $catCarteira = $taxonomy->classifyFromText('Carteira Slim de Couro Genuíno', 'Carteiras e porta-cartões');
        $this->assertSame(CategoryTaxonomyService::CAT_FASHION, $catCarteira);

        // 3. Sapatos e Tênis (ID 322 / 200000950)
        $catTenis = $taxonomy->classifyFromText('Tênis Esportivo Corrida Amortecedor Ultra', 'Sapatos');
        $this->assertSame(CategoryTaxonomyService::CAT_FASHION, $catTenis);

        // 4. Maquiagem (ID 660103)
        $catMake = $taxonomy->classifyFromText('Paleta de Sombras 18 Cores Nude Matte', 'Maquiagem');
        $this->assertSame(CategoryTaxonomyService::CAT_BEAUTY, $catMake);

        // 5. Joias e Acessórios / Chaveiros (ID 36)
        $catChaveiro = $taxonomy->classifyFromText('Chaveiro Tático Mosquetão em Aço Inox', 'Joias e acessórios');
        $this->assertSame(CategoryTaxonomyService::CAT_FASHION, $catChaveiro);
    }
}
