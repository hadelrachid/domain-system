<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Contracts;

/**
 * Contrato de comunicação com a AliExpress Open Platform API (DIP / SOLID).
 */
interface AliExpressApiClientInterface
{
    /**
     * Executa uma chamada oficial ao gateway da AliExpress Open Platform.
     * 
     * @param string $apiMethod Nome oficial do método (ex: 'aliexpress.ds.product.get')
     * @param array<string, mixed> $params Parâmetros específicos da API
     * @param string|null $accessToken Token OAuth2 de sessão (opcional)
     * @return array<string, mixed> Resposta decodificada em array
     */
    public function execute(string $apiMethod, array $params = [], ?string $accessToken = null): array;

    /**
     * Consulta detalhes completos de um produto pelo ID do AliExpress.
     * 
     * @param string|int $productId ID numérico do produto no AliExpress
     * @param string $targetLanguage Idioma desejado (padrão 'pt')
     * @param string $targetCurrency Moeda base (padrão 'USD')
     * @return array<string, mixed>
     */
    public function getProductDetails(string|int $productId, string $targetLanguage = 'pt', string $targetCurrency = 'USD'): array;

    /**
     * Pesquisa ou minera produtos em alta por termo ou categoria.
     * 
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function searchProducts(array $filters = []): array;

    /**
     * Consulta eventos logísticos e rastreamento oficial de um envio no SDK AliExpress.
     *
     * @param string $logisticsNo Código de rastreio da transportadora (Correios/Cainiao)
     * @param string $origin Origem da ordem no AliExpress (padrão 'ESCROW')
     * @param string $outRef ID do pedido no AliExpress
     * @param string $serviceName Serviço logístico / transportadora (ex: 'CAINIAO', 'CORREIOS')
     * @param string $toArea País de destino ISO-2 (padrão 'BR')
     * @return array<string, mixed>
     */
    public function queryTrackingInfo(
        string $logisticsNo,
        string $origin = 'ESCROW',
        string $outRef = '',
        string $serviceName = 'CAINIAO',
        string $toArea = 'BR'
    ): array;
}
