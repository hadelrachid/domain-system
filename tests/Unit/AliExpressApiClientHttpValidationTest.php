<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Plugins\domain_drop\Exceptions\AliExpressApiException;
use DomainSystem\Plugins\domain_drop\Services\AliExpressApiClient;
use PHPUnit\Framework\TestCase;

/**
 * Validação de integridade do protocolo HTTP e exceções tipadas no AliExpressApiClient.
 */
final class AliExpressApiClientHttpValidationTest extends TestCase
{
    public function testAliExpressApiExceptionCarriesTypeAndCode(): void
    {
        $ex = new AliExpressApiException('Falha de conexão', 500, 'HTTP_ERROR');
        $this->assertSame('Falha de conexão', $ex->getMessage());
        $this->assertSame(500, $ex->getCode());
        $this->assertSame('HTTP_ERROR', $ex->type);
        $this->assertInstanceOf(\RuntimeException::class, $ex);
    }

    public function testPurchasableItemResolverNormalizesAliasesAndPassesContext(): void
    {
        $resolver = new \DomainSystem\Plugins\commerce\Services\PurchasableItemResolver();

        $receivedContext = null;
        $resolver->registerResolver('dropship_product', function (int $id, array $context = []) use (&$receivedContext) {
            $receivedContext = $context;
            $mockItem = $this->createMock(\DomainSystem\Plugins\commerce\Contracts\PurchasableItemInterface::class);
            $mockItem->method('getId')->willReturn($id);
            $mockItem->method('getItemType')->willReturn('dropship_product');
            return $mockItem;
        });

        // 1. Testa resolução direta pelo tipo canônico com contexto
        $item1 = $resolver->resolve('dropship_product', 101, ['sku' => 'SKU-TEST-RED']);
        $this->assertNotNull($item1);
        $this->assertSame(101, $item1->getId());
        $this->assertSame(['sku' => 'SKU-TEST-RED'], $receivedContext);

        // 2. Testa resolução através de alias 'dropship'
        $item2 = $resolver->resolve('dropship', 102, ['sku' => 'SKU-TEST-BLUE']);
        $this->assertNotNull($item2);
        $this->assertSame(102, $item2->getId());
        $this->assertSame(['sku' => 'SKU-TEST-BLUE'], $receivedContext);

        // 3. Testa resolução através de alias 'product'
        $item3 = $resolver->resolve('product', 103, ['sku' => 'SKU-TEST-GOLD']);
        $this->assertNotNull($item3);
        $this->assertSame(103, $item3->getId());
        $this->assertSame(['sku' => 'SKU-TEST-GOLD'], $receivedContext);
    }
}
