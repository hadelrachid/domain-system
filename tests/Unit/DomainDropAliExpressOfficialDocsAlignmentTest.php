<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Plugins\domain_drop\Contracts\AiProductEnricherInterface;
use DomainSystem\Plugins\domain_drop\Contracts\AliExpressApiClientInterface;
use DomainSystem\Plugins\domain_drop\Contracts\CurrencyConverterInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipCatalogRepositoryInterface;
use DomainSystem\Plugins\domain_drop\DTO\DropshipProductDto;
use DomainSystem\Plugins\domain_drop\Services\AliExpressApiClient;
use DomainSystem\Plugins\domain_drop\Services\CategoryTaxonomyService;
use DomainSystem\Plugins\domain_drop\Services\ProductMiningService;
use PHPUnit\Framework\TestCase;

class DomainDropAliExpressOfficialDocsAlignmentTest extends TestCase
{
    public function testCategoryTaxonomyReturnsOfficialAliExpressCategoryIdsAndKeywords(): void
    {
        $taxonomy = new CategoryTaxonomyService();

        // 1. Testa IDs numéricos oficiais (categoria-e-atributos.pdf e dropshiper.pdf)
        $techIds = $taxonomy->getAliExpressCategoryIds(CategoryTaxonomyService::CAT_TECH);
        $this->assertContains(44, $techIds);
        $this->assertContains(509, $techIds);

        $watchIds = $taxonomy->getAliExpressCategoryIds(CategoryTaxonomyService::CAT_SMARTWATCH);
        $this->assertContains(1511, $watchIds);

        $homeIds = $taxonomy->getAliExpressCategoryIds(CategoryTaxonomyService::CAT_HOME);
        $this->assertContains(15, $homeIds);

        $toolIds = $taxonomy->getAliExpressCategoryIds(CategoryTaxonomyService::CAT_TOOLS);
        $this->assertContains(1420, $toolIds);

        $beautyIds = $taxonomy->getAliExpressCategoryIds(CategoryTaxonomyService::CAT_BEAUTY);
        $this->assertContains(66, $beautyIds);

        $autoIds = $taxonomy->getAliExpressCategoryIds(CategoryTaxonomyService::CAT_AUTO);
        $this->assertContains(34, $autoIds);

        $fashionIds = $taxonomy->getAliExpressCategoryIds(CategoryTaxonomyService::CAT_FASHION);
        $this->assertContains(200000345, $fashionIds);

        // 2. Testa palavras-chave de busca
        $techKeywords = $taxonomy->getAliExpressSearchKeywords(CategoryTaxonomyService::CAT_TECH);
        $this->assertNotEmpty($techKeywords);
        $this->assertStringContainsString('fone', $techKeywords[0]);

        $toolsKeywords = $taxonomy->getAliExpressSearchKeywords(CategoryTaxonomyService::CAT_TOOLS);
        $this->assertNotEmpty($toolsKeywords);
        $this->assertStringContainsString('alicate', $toolsKeywords[0]);
    }

    public function testTransformRawItemToDtoParsesOfficialTextSearchSchema(): void
    {
        $mockApi = $this->createMock(AliExpressApiClientInterface::class);
        $mockCurrency = $this->createMock(CurrencyConverterInterface::class);
        $mockCurrency->method('calculateSalePrice')->willReturn(149.90);
        $mockCurrency->method('calculateSalePriceFromBrl')->willReturn(149.90);

        $mockAi = $this->createMock(AiProductEnricherInterface::class);
        $mockAi->method('polishTitle')->willReturn('Fone de Ouvido Bluetooth Sem Fio TWS Gamer');

        $mockRepo = $this->createMock(DropshipCatalogRepositoryInterface::class);

        $service = new ProductMiningService(
            $mockApi,
            $mockCurrency,
            $mockAi,
            $mockRepo
        );

        // Payload fiel retornado por aliexpress.ds.text.search (dropshiper.pdf páginas 2028-2060)
        $officialTextSearchItem = [
            'itemId'                => '1005006789123456',
            'title'                 => 'Original Wireless Bluetooth Earphones Sports TWS',
            'itemMainPic'           => 'https://ae01.alicdn.com/kf/S9876543210.jpg',
            'itemUrl'               => 'https://www.aliexpress.com/item/1005006789123456.html',
            'productVideoUrl'       => 'https://video.aliexpress-media.com/play/123456.mp4',
            'originalPrice'         => '25.00',
            'originalPriceCurrency' => 'USD',
            'salePrice'             => '12.50',
            'salePriceCurrency'     => 'USD',
            'targetSalePrice'       => '12.50',
            'targetOriginalPrice'   => '25.00',
            'discount'              => '50%',
            'evaluateRate'          => '98.5%',
            'orders'                => '3500',
            'score'                 => '4.9',
            'cateId'                => '44',
            'type'                  => 'search',
        ];

        $dto = $service->transformRawItemToDto($officialTextSearchItem, 'Eletrônicos & Telefonia');

        $this->assertInstanceOf(DropshipProductDto::class, $dto);
        $this->assertSame('1005006789123456', $dto->aliexpressId);
        $this->assertSame('Fone de Ouvido Bluetooth Sem Fio TWS Gamer', $dto->title);
        $this->assertSame('https://ae01.alicdn.com/kf/S9876543210.jpg', $dto->mainImageUrl);
        $this->assertSame(12.50, $dto->costPriceUsd);
        $this->assertSame(149.90, $dto->salePriceBrl);
        $this->assertSame('Eletrônicos & Telefonia', $dto->category);
        $this->assertSame('https://www.aliexpress.com/item/1005006789123456.html', $dto->attributes['aliexpress_url']);
        $this->assertSame('https://video.aliexpress-media.com/play/123456.mp4', $dto->attributes['video_url']);
        $this->assertSame('50%', $dto->attributes['discount_label']);
    }

    public function testAliExpressApiClientGetFeedItemIdsExecutesOfficialMethod(): void
    {
        $client = $this->getMockBuilder(AliExpressApiClient::class)
            ->setConstructorArgs(['test_app_key', 'test_app_secret'])
            ->onlyMethods(['execute'])
            ->getMock();

        // Resposta oficial de aliexpress.ds.feed.itemids.get (dropshiper.pdf linhas 1012-1025)
        $client->expects($this->once())
            ->method('execute')
            ->with(
                'aliexpress.ds.feed.itemids.get',
                [
                    'feed_name'   => 'DS bestseller',
                    'page_size'   => '20',
                    'category_id' => '44',
                ],
                $this->anything()
            )
            ->willReturn([
                'result' => [
                    'total'     => '150',
                    'products'  => ['1005001111111111', '1005002222222222'],
                    'search_id' => 'cursor_token_xyz',
                ],
                'code' => '0',
                'rsp_code' => 200,
            ]);

        $res = $client->getFeedItemIds('DS bestseller', '44', 20);

        $this->assertSame(150, $res['total']);
        $this->assertCount(2, $res['products']);
        $this->assertSame('1005001111111111', $res['products'][0]);
        $this->assertSame('cursor_token_xyz', $res['search_id']);
    }

    public function testAliExpressApiClientSearchProductsParsesTextSearchProductsArray(): void
    {
        $client = $this->getMockBuilder(AliExpressApiClient::class)
            ->setConstructorArgs(['test_app_key', 'test_app_secret'])
            ->onlyMethods(['textSearch', 'getFeedItemIds', 'execute'])
            ->getMock();

        // Simula resposta oficial do aliexpress.ds.text.search
        $client->expects($this->once())
            ->method('textSearch')
            ->willReturn([
                'code' => '0',
                'data' => [
                    'pageIndex'  => '1',
                    'pageSize'   => '20',
                    'totalCount' => '100',
                    'products'   => [
                        [
                            'itemId'          => '1005003333333333',
                            'title'           => 'Relógio Smartwatch Esportivo À Prova D’Água',
                            'itemMainPic'     => 'https://ae01.alicdn.com/kf/watch123.jpg',
                            'salePrice'       => '19.90',
                            'targetSalePrice' => '19.90',
                            'orders'          => '850',
                            'evaluateRate'    => '99.0%',
                        ],
                    ],
                ],
            ]);

        $products = $client->searchProducts([
            'keyword'     => 'smartwatch',
            'category_id' => '1511',
        ]);

        $this->assertCount(1, $products);
        $this->assertSame('1005003333333333', $products[0]['itemId']);
        $this->assertSame('Relógio Smartwatch Esportivo À Prova D’Água', $products[0]['title']);
    }
}
