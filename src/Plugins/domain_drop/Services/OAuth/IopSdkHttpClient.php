<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Services\OAuth;

use DomainSystem\Plugins\domain_drop\Contracts\IopHttpClientInterface;
use DomainSystem\Plugins\domain_drop\Exceptions\AliExpressApiException;
use RuntimeException;

/**
 * Cliente HTTP para o protocolo oficial IOP do AliExpress (DIP / Adapter Pattern).
 * Em conformidade com o documento oficial token_access.pdf e o IOP SDK.
 */
final class IopSdkHttpClient implements IopHttpClientInterface
{
    private string $sdkVersion = 'iop-sdk-php-20220608';

    public function __construct(
        private readonly string $appKey,
        private readonly string $appSecret,
        private readonly string $gatewayUrl = 'https://api-sg.aliexpress.com/rest',
        private readonly int $timeout = 15
    ) {}

    public function call(string $endpoint, array $params): array
    {
        $appKey = trim($this->appKey);
        $appSecret = trim($this->appSecret);

        if ($appKey === '' || $appSecret === '') {
            throw new RuntimeException('[IopSdkHttpClient] AppKey ou AppSecret não configurados.');
        }

        $cleanEndpoint = '/' . ltrim($endpoint, '/');

        // Documentação token_access.pdf alerta: NÃO enviar uuid
        $filteredParams = [];
        foreach ($params as $k => $v) {
            if ($k === 'uuid') {
                continue;
            }
            if ($v !== null && $v !== '') {
                $filteredParams[$k] = (string)$v;
            }
        }

        $msec = (string)(int)(microtime(true) * 1000);
        $sysParams = [
            'app_key'     => $appKey,
            'sign_method' => 'sha256',
            'timestamp'   => $msec,
            'method'      => $cleanEndpoint,
            'partner_id'  => $this->sdkVersion,
            'simplify'    => 'false',
            'format'      => 'json',
        ];

        $allParams = array_merge($filteredParams, $sysParams);
        ksort($allParams);

        // Algoritmo oficial de assinatura REST IOP
        $stringToBeSigned = $cleanEndpoint;
        foreach ($allParams as $k => $v) {
            $stringToBeSigned .= "{$k}{$v}";
        }

        $sign = strtoupper(hash_hmac('sha256', $stringToBeSigned, $appSecret));
        $sysParams['sign'] = $sign;

        $baseUrl = str_ends_with($this->gatewayUrl, '/sync')
            ? substr($this->gatewayUrl, 0, -5) . '/rest'
            : (str_ends_with($this->gatewayUrl, '/rest') ? $this->gatewayUrl : 'https://api-sg.aliexpress.com/rest');

        $requestUrl = rtrim($baseUrl, '/') . '?' . http_build_query($sysParams);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $requestUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($filteredParams));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->sdkVersion);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $rawResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($rawResponse === false) {
            throw new AliExpressApiException("[IopSdkHttpClient] Falha de conexão com gateway do AliExpress: {$curlError}", 0, 'CONNECTION_ERROR');
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $errDecoded = json_decode((string)$rawResponse, true);
            $errCode = (string)($errDecoded['code'] ?? "HTTP_{$httpCode}");
            $errMsg = (string)($errDecoded['message'] ?? 'Falha HTTP do gateway AliExpress.');
            throw new AliExpressApiException("[IopSdkHttpClient HTTP {$httpCode}] {$errMsg}", $httpCode, $errCode);
        }

        $decoded = json_decode((string)$rawResponse, true);
        if (!is_array($decoded)) {
            throw new RuntimeException("[IopSdkHttpClient] Resposta não-JSON recebida da API: {$rawResponse}");
        }

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
