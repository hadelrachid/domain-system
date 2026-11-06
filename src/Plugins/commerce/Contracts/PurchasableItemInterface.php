<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\commerce\Contracts;

/**
 * Contrato de Item Vendável no Commerce (DIP / SOLID / Padrão Adapter).
 * 
 * Permite que qualquer plugin (Academy, Downloads Digitais, Consultas)
 * seja vendido pelo Commerce sem que o motor de pagamento conheça o banco de dados do plugin.
 */
interface PurchasableItemInterface
{
    /**
     * ID do item no plugin de origem.
     */
    public function getId(): int;

    /**
     * Tipo do item comercializável (ex: 'course', 'ebook', 'membership', 'consultancy').
     */
    public function getItemType(): string;

    /**
     * Título do produto exibido no carrinho e fatura.
     */
    public function getTitle(): string;

    /**
     * Preço oficial de venda (em R$ ou moeda corrente).
     */
    public function getPrice(): float;

    /**
     * Indica se o produto está ativo e disponível para compra imediata.
     */
    public function isAvailable(): bool;

    /**
     * Descrição curta do produto para o resumo de pedido.
     */
    public function getDescription(): string;

    /**
     * URL da imagem de capa / thumbnail do produto.
     */
    public function getThumbnailUrl(): ?string;
}
