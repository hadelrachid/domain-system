<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\commerce\Contracts;

/**
 * Contrato de Entrega / Liberação Automática de Produtos Vendidos (DIP / SOLID).
 * 
 * Desacopla a finalização financeira da entrega real do produto.
 * Quando o Pix/Cartão é aprovado, o Commerce aciona quem implementa esta interface.
 */
interface ProductFulfillmentInterface
{
    /**
     * Verifica se este despachante sabe entregar o tipo de item informado (ex: 'course').
     */
    public function supports(string $itemType): bool;

    /**
     * Efetiva a entrega/liberação do produto para o comprador.
     *
     * @param int $userId ID do aluno/cliente no sistema
     * @param int $itemId ID do curso ou item vendido
     * @param string $orderRef Código identificador da fatura/pedido
     * @param array<string, mixed> $metadata Dados complementares da compra
     * @return bool True se a entrega/matrícula foi concluída com sucesso
     */
    public function fulfill(int $userId, int $itemId, string $orderRef, array $metadata = []): bool;
}
