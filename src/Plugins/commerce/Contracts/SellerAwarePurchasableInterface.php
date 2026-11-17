<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\commerce\Contracts;

/**
 * Capacidade opcional de fornecer informações do vendedor/seller (ISP - Interface Segregation Principle).
 * Itens comercializáveis que possuem fornecedor, loja de dropshipping ou origem específica implementam esta interface.
 */
interface SellerAwarePurchasableInterface
{
    /**
     * Retorna os metadados do vendedor/loja (ex.: is_brazil, store_name, country_code, rating).
     *
     * @return array<string, mixed>
     */
    public function getSellerInfo(): array;
}
