<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Services;

use DomainSystem\Plugins\domain_drop\Contracts\AliExpressApiClientInterface;
use DomainSystem\Plugins\domain_drop\Exceptions\AliExpressApiException;
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
    private ?string $lastError = null;

    public function __construct(
        string $appKey,
        string $appSecret,
        string $gatewayUrl = 'https://api-sg.aliexpress.com/sync',
        int $timeout = 15,
        private ?string $accessToken = null
    ) {
        $this->appKey = trim($appKey);
        $this->appSecret = trim($appSecret);
        $this->gatewayUrl = rtrim($gatewayUrl, '/');
        $this->timeout = $timeout;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function execute(string $apiMethod, array $params = [], ?string $accessToken = null): array
    {
        if ($this->appKey === '' || $this->appSecret === '') {
            throw new RuntimeException('[AliExpressApiClient] AppKey ou AppSecret não configurados no DomainDrop.');
        }

        $token = $accessToken ?? $this->accessToken;

        $sysParams = [
            'app_key'     => $this->appKey,
            'sign_method' => 'sha256',
            'timestamp'   => $this->getMillisecondTimestamp(),
            'method'      => $apiMethod,
            'partner_id'  => $this->sdkVersion,
            'format'      => 'json',
            'simplify'    => 'true',
            'v'           => '2.0',
        ];

        if ($token !== null && $token !== '') {
            $sysParams['session'] = $token;
        }

        // Filtra parâmetros nulos ou de string vazia para manter paridade exata com o cálculo de assinatura IOP
        $cleanParams = [];
        foreach ($params as $k => $v) {
            if ($v !== null && $v !== '') {
                $cleanParams[$k] = (string)$v;
            }
        }

        // Combina parâmetros de sistema e de API para cálculo da assinatura
        $allParams = array_merge($cleanParams, $sysParams);
        $sysParams['sign'] = $this->generateSignature($apiMethod, $allParams);

        // Monta a Query String dos parâmetros de sistema
        $requestUrl = $this->gatewayUrl . '?' . http_build_query($sysParams);

        return $this->sendHttpRequest($requestUrl, $cleanParams);
    }

    public function getOAuthAuthorizeUrl(string $redirectUri, string $state = ''): string
    {
        $query = [
            'response_type' => 'code',
            'force_auth'    => 'true',
            'redirect_uri'  => $redirectUri,
            'client_id'     => $this->appKey,
        ];
        if ($state !== '') {
            $query['state'] = $state;
        }
        return 'https://api-sg.aliexpress.com/oauth/authorize?' . http_build_query($query);
    }

    /**
     * Troca o código OAuth (`code`) pelo `access_token` e `refresh_token` via `/rest/auth/token/create`.
     *
     * @return array<string, mixed>
     */
    public function createAccessToken(string $code): array
    {
        $raw = $this->executeRest('/auth/token/create', ['code' => trim($code)]);
        $unwrapped = \DomainSystem\Plugins\domain_drop\ValueObjects\AliExpressToken::unwrapPayload($raw);
        return array_merge($raw, $unwrapped);
    }

    /**
     * Renova o `access_token` usando o `refresh_token` via `/rest/auth/token/refresh`.
     *
     * @return array<string, mixed>
     */
    public function refreshAccessToken(string $refreshToken): array
    {
        $raw = $this->executeRest('/auth/token/refresh', ['refresh_token' => trim($refreshToken)]);
        $unwrapped = \DomainSystem\Plugins\domain_drop\ValueObjects\AliExpressToken::unwrapPayload($raw);
        return array_merge($raw, $unwrapped);
    }

    /**
     * Executa chamada IOP System/Auth (`/rest/...`), onde o caminho da API é incluído no cálculo HMAC-SHA256.
     * Segue estritamente a especificação oficial do AliExpress IOP SDK (IopClient).
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function executeRest(string $apiPath, array $params = []): array
    {
        if ($this->appKey === '' || $this->appSecret === '') {
            throw new RuntimeException('[AliExpressApiClient] AppKey ou AppSecret não configurados no DomainDrop.');
        }

        $cleanPath = '/' . ltrim($apiPath, '/');
        $cleanParams = [];
        foreach ($params as $k => $v) {
            if ($v !== null && $v !== '') {
                $cleanParams[$k] = (string)$v;
            }
        }

        $sysParams = [
            'app_key'     => $this->appKey,
            'sign_method' => 'sha256',
            'timestamp'   => $this->getMillisecondTimestamp(),
            'method'      => $cleanPath,
            'partner_id'  => $this->sdkVersion,
            'format'      => 'json',
            'simplify'    => 'false',
        ];

        $allParams = array_merge($cleanParams, $sysParams);
        $sysParams['sign'] = $this->generateSignature($cleanPath, $allParams);

        $restBase = str_ends_with($this->gatewayUrl, '/sync')
            ? substr($this->gatewayUrl, 0, -5) . '/rest'
            : (str_ends_with($this->gatewayUrl, '/rest') ? $this->gatewayUrl : 'https://api-sg.aliexpress.com/rest');

        $requestUrl = rtrim($restBase, '/') . '?' . http_build_query($sysParams);
        return $this->sendHttpRequest($requestUrl, $cleanParams);
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

        return $this->execute('aliexpress.ds.product.get', $params, $this->accessToken);
    }

    /**
     * Realiza busca textual dropshipping oficial via aliexpress.ds.text.search (dropshiper.pdf páginas 39-41).
     * Não exige session token obrigatório.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function textSearch(array $filters = []): array
    {
        $sort = (string)($filters['sort_by'] ?? ($filters['sort'] ?? 'orders,desc'));
        $mappedSort = match ($sort) {
            'min_price,asc', 'price_asc'   => 'min_price,asc',
            'min_price,desc', 'price_desc' => 'min_price,desc',
            'comments,desc', 'rating'      => 'comments,desc',
            default                        => 'orders,desc',
        };

        $params = [
            'local'       => (string)($filters['local'] ?? 'pt_BR'),
            'countryCode' => (string)($filters['country_code'] ?? ($filters['countryCode'] ?? ($filters['country'] ?? 'BR'))),
            'sortBy'      => $mappedSort,
            'pageSize'    => (string)($filters['page_size'] ?? ($filters['pageSize'] ?? '20')),
            'pageIndex'   => (string)($filters['page_index'] ?? ($filters['pageIndex'] ?? ($filters['page_no'] ?? '1'))),
            'currency'    => (string)($filters['currency'] ?? 'USD'),
        ];

        $keyword = trim((string)($filters['keyWord'] ?? ($filters['keyword'] ?? ($filters['query'] ?? ($filters['key_word'] ?? '')))));
        if ($keyword !== '') {
            $params['keyWord'] = $keyword;
        }

        $categoryId = $filters['categoryId'] ?? ($filters['category_id'] ?? ($filters['cate_id'] ?? null));
        if (!empty($categoryId)) {
            $params['categoryId'] = (string)$categoryId;
        }

        try {
            $response = $this->execute('aliexpress.ds.text.search', $params, $this->accessToken);
            $this->lastError = null;
            return $response;
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            error_log("[AliExpressApiClient] textSearch falhou: " . $e->getMessage());
            return [];
        }
    }

    public function searchProducts(array $filters = []): array
    {
        $hasTextQuery = !empty($filters['query']) || !empty($filters['keyword']) || !empty($filters['keyWord']) || !empty($filters['search']);
        if ($hasTextQuery) {
            $textRes = $this->textSearch($filters);
            $unwrapped = \DomainSystem\Plugins\domain_drop\ValueObjects\AliExpressToken::unwrapPayload($textRes);
            $products = $unwrapped['products']['traffic_product_d_t_o']
                ?? ($unwrapped['products']['product']
                ?? ($unwrapped['products'] ?? []));
            if (is_array($products) && !empty($products)) {
                return $products;
            }
        }

        $sort = (string)($filters['sort'] ?? 'volume_desc');
        $mappedSort = match ($sort) {
            'evaluate_rate_desc', 'rating' => 'DSRratingDesc',
            'price_asc'                    => 'priceAsc',
            'price_desc'                   => 'priceDesc',
            default                        => 'volumeDesc',
        };

        $params = [
            'feed_name'       => (string)($filters['feed_name'] ?? 'DS bestseller'),
            'target_language' => 'pt',
            'target_currency' => 'USD',
            'country'         => (string)($filters['country'] ?? ($filters['ship_to_country'] ?? 'BR')),
            'page_size'       => (string)($filters['page_size'] ?? '20'),
            'page_no'         => (string)($filters['page_no'] ?? '1'),
            'sort'            => $mappedSort,
        ];

        if (!empty($filters['category_id'])) {
            $params['category_id'] = (string)$filters['category_id'];
        }

        try {
            $response = $this->execute('aliexpress.ds.recommend.feed.get', $params, $this->accessToken);
            $this->lastError = null;
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            error_log("[AliExpressApiClient] searchProducts falhou: " . $e->getMessage());
            return [];
        }

        // Normaliza diferentes formatos de payload do IOP SDK (feed ou busca de produtos)
        $products = $response['result']['products']['traffic_product_d_t_o']
            ?? ($response['result']['products']['product'] ?? null)
            ?? ($response['aliexpress_ds_recommend_feed_get_response']['result']['products']['traffic_product_d_t_o'] ?? null)
            ?? ($response['aliexpress_ds_recommend_feed_get_response']['result']['products'] ?? []);

        return is_array($products) ? $products : [];
    }

    public function queryTrackingInfo(
        string $logisticsNo,
        string $origin = 'ESCROW',
        string $outRef = '',
        string $serviceName = 'CAINIAO',
        string $toArea = 'BR'
    ): array {
        $logisticsNo = trim($logisticsNo);
        if ($logisticsNo === '' && trim($outRef) === '') {
            return [];
        }

        $params = [
            'logistics_no' => $logisticsNo,
            'origin'       => $origin !== '' ? $origin : 'ESCROW',
            'out_ref'      => trim($outRef),
            'service_name' => $serviceName !== '' ? $serviceName : 'CAINIAO',
            'to_area'      => $toArea !== '' ? strtoupper($toArea) : 'BR',
        ];

        $response = $this->execute('aliexpress.logistics.ds.trackinginfo.query', $params, $this->accessToken);

        $details = $response['result']['details']['details']
            ?? ($response['result']['details'] ?? null)
            ?? ($response['aliexpress_logistics_ds_trackinginfo_query_response']['details']['details'] ?? null)
            ?? ($response['aliexpress_logistics_ds_trackinginfo_query_response']['result']['details']['details'] ?? null)
            ?? ($response['aliexpress_logistics_ds_trackinginfo_query_response']['result']['details'] ?? []);

        return [
            'official_website' => (string)($response['result']['official_website'] ?? $response['aliexpress_logistics_ds_trackinginfo_query_response']['official_website'] ?? ''),
            'details'          => is_array($details) ? $details : [],
            'raw'              => $response,
        ];
    }

    public function getOfficialCategories(int|string $categoryId = 0, string $language = 'pt', bool $forceRefresh = false): array
    {
        $langKey = strtolower(trim($language)) ?: 'pt';
        $cacheFile = dirname(__DIR__, 4) . '/storage/cache/aliexpress_categories_' . $langKey . '.json';

        if (!$forceRefresh && file_exists($cacheFile)) {
            $mtime = filemtime($cacheFile);
            // Cache oficial de 7 dias (categorias de e-commerce global são altamente estáveis)
            if ((time() - $mtime) < 604800) {
                $cached = json_decode((string)file_get_contents($cacheFile), true);
                if (is_array($cached) && !empty($cached)) {
                    return $cached;
                }
            }
        }

        try {
            $response = $this->execute('aliexpress.ds.category.get', [
                'category_id' => (string)$categoryId,
                'language'    => $langKey,
            ]);

            $categories = $response['resp_result']['result']['categories']
                ?? ($response['result']['categories']
                ?? ($response['aliexpress_ds_category_get_response']['resp_result']['result']['categories'] ?? []));

            if (is_array($categories) && !empty($categories)) {
                $dir = dirname($cacheFile);
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
                file_put_contents($cacheFile, json_encode($categories, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                return $categories;
            }
        } catch (Throwable $e) {
            // Em caso de instabilidade na conexão, utiliza cache em disco se existir
            if (file_exists($cacheFile)) {
                $cached = json_decode((string)file_get_contents($cacheFile), true);
                if (is_array($cached) && !empty($cached)) {
                    return $cached;
                }
            }
        }

        return [];
    }

    public function getSkuAttributes(int|string $categoryId): array
    {
        $params = [
            'query_sku_attribute_info_request' => json_encode([
                'aliexpress_category_id' => (string)$categoryId,
            ], JSON_UNESCAPED_SLASHES),
        ];

        try {
            $response = $this->execute('aliexpress.solution.sku.attribute.query', $params, $this->accessToken);
            $this->lastError = null;
            $unwrapped = \DomainSystem\Plugins\domain_drop\ValueObjects\AliExpressToken::unwrapPayload($response);
            $res = $unwrapped['result'] ?? ($unwrapped['data'] ?? $unwrapped);
            return is_array($res) ? $res : [];
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            error_log("[AliExpressApiClient] getSkuAttributes falhou: " . $e->getMessage());
            return [];
        }
    }

    public function getProductEanCode(string $eanCode): array
    {
        $code = trim($eanCode);
        if ($code === '') {
            return [];
        }

        try {
            $response = $this->execute('aliexpress.product.eancode.info.query', ['ean_code' => $code], $this->accessToken);
            $this->lastError = null;
            $unwrapped = \DomainSystem\Plugins\domain_drop\ValueObjects\AliExpressToken::unwrapPayload($response);
            $res = $unwrapped['result'] ?? ($unwrapped['data'] ?? $unwrapped);
            return is_array($res) ? $res : [];
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            error_log("[AliExpressApiClient] getProductEanCode falhou: " . $e->getMessage());
            return [];
        }
    }

    public function queryProductInventory(int|string $productId): array
    {
        try {
            $response = $this->execute('aliexpress.solution.product.inventory.query', ['product_id' => (string)$productId], $this->accessToken);
            $this->lastError = null;
            $unwrapped = \DomainSystem\Plugins\domain_drop\ValueObjects\AliExpressToken::unwrapPayload($response);
            $res = $unwrapped['result'] ?? ($unwrapped['data'] ?? $unwrapped);
            return is_array($res) ? $res : [];
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            error_log("[AliExpressApiClient] queryProductInventory falhou: " . $e->getMessage());
            return [];
        }
    }

    public function updateBatchProductPrices(array $priceUpdates): array
    {
        if (empty($priceUpdates)) {
            return [];
        }

        try {
            $response = $this->execute('aliexpress.solution.batch.product.price.update', [
                'mutiple_product_update_list' => json_encode($priceUpdates, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ], $this->accessToken);
            $this->lastError = null;
            return \DomainSystem\Plugins\domain_drop\ValueObjects\AliExpressToken::unwrapPayload($response);
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            error_log("[AliExpressApiClient] updateBatchProductPrices falhou: " . $e->getMessage());
            return [];
        }
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

        (new ApiUsageTracker())->recordRequest();

        if ($rawResponse === false) {
            throw new AliExpressApiException("[AliExpressApiClient] Falha na conexão com o gateway do AliExpress: {$error}", 0, 'CONNECTION_ERROR');
        }

        // 1. Validar Status HTTP primeiro (HTTP 2xx é sucesso HTTP)
        if ($httpCode < 200 || $httpCode >= 300) {
            $decodedErr = json_decode((string)$rawResponse, true);
            if (is_array($decodedErr)) {
                $errCode = (string)($decodedErr['code'] ?? ($decodedErr['error_response']['code'] ?? "HTTP_{$httpCode}"));
                $errMsg = (string)($decodedErr['message'] ?? ($decodedErr['error_response']['msg'] ?? 'Falha de resposta HTTP do gateway AliExpress.'));
                throw new AliExpressApiException("[AliExpress Gateway HTTP {$httpCode} - {$errCode}] {$errMsg}", $httpCode, $errCode);
            }
            throw new AliExpressApiException(
                "[AliExpress Gateway HTTP {$httpCode}] Falha de resposta HTTP do gateway AliExpress.",
                $httpCode,
                'HTTP_ERROR'
            );
        }

        // 2. Decodificar resposta JSON
        $decoded = json_decode((string)$rawResponse, true);

        if (!is_array($decoded)) {
            // Se vier XML ou formato inesperado
            return [
                'raw_response' => (string)$rawResponse,
                'http_code'    => $httpCode,
            ];
        }

        // 3. Se o AliExpress retornar erro formal na resposta (formato TOP error_response ou IOP code)
        if (isset($decoded['error_response']) && is_array($decoded['error_response'])) {
            $err = $decoded['error_response'];
            $errCode = (string)($err['sub_code'] ?? ($err['code'] ?? 'API_ERROR'));
            $msg = (string)($err['sub_msg'] ?? ($err['msg'] ?? 'Erro desconhecido da AliExpress API'));
            throw new AliExpressApiException("[AliExpress Gateway Error: {$errCode}] {$msg}", 0, $errCode);
        }

        if (isset($decoded['code']) && (string)$decoded['code'] !== '0' && (string)$decoded['code'] !== '200') {
            $codeStr = (string)$decoded['code'];
            $msg = (string)($decoded['message'] ?? ($decoded['msg'] ?? ($decoded['sub_msg'] ?? 'Erro retornado pela AliExpress API')));
            $subCode = (string)($decoded['sub_code'] ?? $codeStr);
            throw new AliExpressApiException("[AliExpress Gateway Error: {$codeStr}] {$msg}", 0, $subCode);
        }

        return $decoded;
    }
}
