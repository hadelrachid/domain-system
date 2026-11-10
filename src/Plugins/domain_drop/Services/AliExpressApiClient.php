<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Services;

use DomainSystem\Plugins\domain_drop\Contracts\AliExpressApiClientInterface;
use RuntimeException;
use Throwable;

/**
 * Cliente Oficial de Comunicação com a AliExpress Open Platform API (DIP / SOLID).
 * 
 * Implementa o protocolo de assinatura criptográfica oficial HMAC-SHA256 (IOP Protocol),
 * compatível com o gateway internacional: https://api-sg.aliexpress.com/sync.
 */
class AliExpressApiClient implements AliExpressApiClientInterface
{
    private string $appKey;
    private string $appSecret;
    private string $gatewayUrl;
    private int $timeout;
    private string $sdkVersion = 'iop-sdk-php-20220608';

    public function __construct(
        string $appKey,
        string $appSecret,
        string $gatewayUrl = 'https://api-sg.aliexpress.com/sync',
        int $timeout = 15
    ) {
        $this->appKey = trim($appKey);
        $this->appSecret = trim($appSecret);
        $this->gatewayUrl = rtrim($gatewayUrl, '/');
        $this->timeout = $timeout;
    }

    public function execute(string $apiMethod, array $params = [], ?string $accessToken = null): array
    {
        if ($this->appKey === '' || $this->appSecret === '') {
            throw new RuntimeException('[AliExpressApiClient] AppKey ou AppSecret não configurados no DomainDrop.');
        }

        $sysParams = [
            'app_key'     => $this->appKey,
            'sign_method' => 'sha256',
            'timestamp'   => $this->getMillisecondTimestamp(),
            'method'      => $apiMethod,
            'partner_id'  => $this->sdkVersion,
            'format'      => 'json',
            'simplify'    => 'true',
        ];

        if ($accessToken !== null && $accessToken !== '') {
            $sysParams['session'] = $accessToken;
        }

        // Combina parâmetros de sistema e de API para cálculo da assinatura
        $allParams = array_merge($params, $sysParams);
        $sysParams['sign'] = $this->generateSignature($apiMethod, $allParams);

        // Monta a Query String dos parâmetros de sistema
        $requestUrl = $this->gatewayUrl . '?' . http_build_query($sysParams);

        return $this->sendHttpRequest($requestUrl, $params);
    }

    public function getProductDetails(string|int $productId, string $targetLanguage = 'pt', string $targetCurrency = 'USD'): array
    {
        $numericId = (string)$productId;
        // Se for URL completa do AliExpress, extrai o ID numérico
        if (preg_match('/\/item\/(\d+)\.html/', $numericId, $matches)) {
            $numericId = $matches[1];
        }

        $params = [
            'product_id'      => $numericId,
            'target_language' => $targetLanguage,
            'target_currency' => $targetCurrency,
            'ship_to_country' => 'BR',
        ];

        return $this->execute('aliexpress.ds.product.get', $params);
    }

    public function searchProducts(array $filters = []): array
    {
        $params = [
            'category_id'      => (string)($filters['category_id'] ?? ''),
            'keywords'         => (string)($filters['keywords'] ?? 'trending'),
            'page_size'        => (string)($filters['page_size'] ?? '20'),
            'page_no'          => (string)($filters['page_no'] ?? '1'),
            'sort'             => (string)($filters['sort'] ?? 'volume_desc'), // Mais vendidos
            'target_language'  => 'pt',
            'target_currency'  => 'USD',
            'ship_to_country'  => 'BR',
        ];

        $response = $this->execute('aliexpress.ds.recommend.feed.get', $params);

        // Tenta normalizar tanto o feed de recomendação quanto a busca direta
        $products = $response['result']['products']['traffic_product_d_t_o']
            ?? ($response['aliexpress_ds_recommend_feed_get_response']['result']['products'] ?? []);

        return is_array($products) ? $products : [];
    }

    /**
     * Algoritmo Oficial de Assinatura IOP da AliExpress Open Platform.
     * 1. Ordena parâmetros alfabeticamente por chave (ksort).
     * 2. Concatena chave e valor em string corrida.
     * 3. Calcula HMAC-SHA256 em maiúsculas usando o AppSecret.
     * 
     * @param array<string, mixed> $params
     */
    public function generateSignature(string $apiMethod, array $params): string
    {
        ksort($params);

        $stringToBeSigned = '';
        if (str_contains($apiMethod, '/')) {
            $stringToBeSigned .= $apiMethod;
        }

        foreach ($params as $k => $v) {
            if ($v !== null && $v !== '') {
                $stringToBeSigned .= "$k$v";
            }
        }

        return strtoupper(hash_hmac('sha256', $stringToBeSigned, $this->appSecret));
    }

    /**
     * Retorna o timestamp em milissegundos exigido pela AliExpress API.
     */
    private function getMillisecondTimestamp(): string
    {
        return (string)(int)(microtime(true) * 1000);
    }

    /**
     * Executa requisição HTTP segura via cURL com POST.
     * 
     * @param array<string, mixed> $postData
     * @return array<string, mixed>
     */
    private function sendHttpRequest(string $url, array $postData = []): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->sdkVersion);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $rawResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($rawResponse === false) {
            throw new RuntimeException("[AliExpressApiClient] Falha na conexão com o gateway do AliExpress: {$error}");
        }

        $decoded = json_decode((string)$rawResponse, true);

        if (!is_array($decoded)) {
            // Se vier XML ou erro de gateway
            return [
                'raw_response' => (string)$rawResponse,
                'http_code'    => $httpCode,
            ];
        }

        // Se o AliExpress retornar erro formal na resposta
        if (isset($decoded['code']) && (int)$decoded['code'] !== 0 && !isset($decoded['result'])) {
            $msg = $decoded['message'] ?? ($decoded['sub_msg'] ?? 'Erro desconhecido da AliExpress API');
            throw new RuntimeException("[AliExpress Gateway Error: {$decoded['code']}] {$msg}");
        }

        return $decoded;
    }
}
