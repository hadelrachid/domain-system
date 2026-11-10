<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Contracts;

/**
 * Contrato para Enriquecimento, Tradução e Higienização Comercial de Produtos via I.A. (DIP / SOLID).
 */
interface AiProductEnricherInterface
{
    /**
     * Higieniza e encurta o título poluído do AliExpress para um título comercial atraente em português.
     * 
     * @param string $rawTitle Título bruto do AliExpress
     * @param string|null $category Categoria opcional para contexto
     * @return string Título limpo e comercial
     */
    public function polishTitle(string $rawTitle, ?string $category = null): string;

    /**
     * Gera uma descrição comercial persuasiva (benefícios, tópicos e especificações limpas).
     * 
     * @param string $rawDescription Conteúdo original do fornecedor
     * @param array<string, mixed> $attributes Atributos e especificações do produto
     * @return string HTML/Markdown limpo e estruturado para a página do produto
     */
    public function generateSalesCopy(string $rawDescription, array $attributes = []): string;
}
