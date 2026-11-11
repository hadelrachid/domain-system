<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Contracts;

use DomainSystem\Plugins\domain_drop\DTO\ScrapedProductDto;

/**
 * Contrato para Motores de Scraping e Extração por Link Oficial (SRP / DIP).
 * 
 * Responsável exclusivo por inspecionar a URL oficial do fornecedor,
 * extrair em tempo real imagens Full HD (capa e galeria), título oficial e precificação.
 */
interface ProductLinkScraperInterface
{
    /**
     * Verifica se este scraper suporta a URL ou identificador informado.
     */
    public function canHandle(string $urlOrId): bool;

    /**
     * Executa a extração em tempo real diretamente do link oficial do produto.
     * Retorna o DTO fortemente tipado com garantias de integridade de mídia.
     * 
     * @param string $urlOrId URL pública ou ID do produto
     * @return ScrapedProductDto|null Retorna null se a página estiver indisponível ou inacessível
     */
    public function scrape(string $urlOrId): ?ScrapedProductDto;

    /**
     * Analisa o código-fonte bruto (HTML ou JSON do anúncio copiado do navegador)
     * e extrai os dados estruturados do produto sem realizar requisições de rede.
     * 
     * @param string $rawSource Código HTML ou payload JSON
     * @param string $sourceUrl URL de origem (opcional)
     * @return ScrapedProductDto|null
     */
    public function parseRawHtml(string $rawSource, string $sourceUrl = ''): ?ScrapedProductDto;

    /**
     * Verifica rapidamente se o link do produto existe e está ativo (filtra páginas 404/indisponíveis).
     */
    public function isProductAlive(string $urlOrId): bool;
}
