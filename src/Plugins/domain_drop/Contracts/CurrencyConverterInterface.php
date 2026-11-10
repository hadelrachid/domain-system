<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Contracts;

/**
 * Contrato de Conversão Cambial e Precificação com Margem de Lucro (DIP / SOLID).
 */
interface CurrencyConverterInterface
{
    /**
     * Retorna a taxa de câmbio atual de USD para a moeda de destino (ex: BRL).
     */
    public function getExchangeRate(string $from = 'USD', string $to = 'BRL'): float;

    /**
     * Converte o custo original em USD para BRL e calcula o preço final de venda
     * aplicando o markup percentual e margem mínima de segurança.
     * 
     * @param float $costUsd Custo original do fornecedor em Dólar
     * @param float|null $customMarkupPercent Percentual de margem (ex: 100.0 para 2x)
     * @param float|null $minProfitBrl Margem mínima em reais de lucro garantido
     * @return float Preço final sugerido de venda em Reais (BRL)
     */
    public function calculateSalePrice(float $costUsd, ?float $customMarkupPercent = null, ?float $minProfitBrl = null): float;
}
