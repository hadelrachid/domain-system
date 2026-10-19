<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Infrastructure;

use Closure;
use DomainSystem\Core\Contracts\Update\HttpClientInterface;
use DomainSystem\Core\Update\Exception\TransportException;
use DomainSystem\Core\Update\Value\HttpResponse;

final class CurlHttpClient implements HttpClientInterface
{
    private const MAX_REDIRECTS = 3;
    private const USER_AGENT = 'DomainSystem-Updater/1.0 (+https://github.com/hadelrachid/domain-system)';

    private HostAllowlist $allowlist;

    /**
     * @var (Closure(string, array<string, string>, int, ?string, ?int): HttpResponse)|null
     */
    private ?Closure $transportOverride;

    /**
     * @param (Closure(string, array<string, string>, int, ?string, ?int): HttpResponse)|null $transportOverride
     */
    public function __construct(?HostAllowlist $allowlist = null, ?Closure $transportOverride = null)
    {
        $this->allowlist = $allowlist ?? new HostAllowlist();
        $this->transportOverride = $transportOverride;
    }

    public function get(string $url, array $headers = [], int $timeoutSeconds = 10): HttpResponse
    {
        return $this->executeWithRedirects($url, $headers, $timeoutSeconds, null, null);
    }

    public function download(
        string $url,
        string $destinationFile,
        int $maxBytes,
        array $headers = [],
        int $timeoutSeconds = 60,
    ): HttpResponse {
        if ($maxBytes <= 0) {
            throw new TransportException('O limite máximo de bytes ($maxBytes) deve ser positivo.');
        }

        $dir = dirname($destinationFile);
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new TransportException("Não foi possível criar o diretório de destino para download: {$dir}");
        }

        try {
            return $this->executeWithRedirects($url, $headers, $timeoutSeconds, $destinationFile, $maxBytes);
        } catch (\Throwable $e) {
            if (is_file($destinationFile)) {
                @unlink($destinationFile);
            }
            if ($e instanceof TransportException) {
                throw $e;
            }
            throw new TransportException('Falha durante o download: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Valida estritamente se a URL utiliza HTTPS, não contém userinfo, usa porta padrão e pertence à allowlist.
     *
     * @throws TransportException
     */
    public function validateUrl(string $url): void
    {
        $trimmed = trim($url);
        if ($trimmed === '') {
            throw new TransportException('URL vazia fornecida ao cliente HTTP.');
        }

        // Rejeita userinfo explícito antes mesmo de parse_url ambíguo
        if (preg_match('#^https?://[^/]*@#i', $trimmed) === 1) {
            throw new TransportException("URL com userinfo não é permitida: {$url}");
        }

        $parts = parse_url($trimmed);
        if (!is_array($parts)) {
            throw new TransportException("URL malformada: {$url}");
        }

        $scheme = strtolower((string)($parts['scheme'] ?? ''));
        if ($scheme !== 'https') {
            throw new TransportException("Apenas protocolo HTTPS é permitido: {$url}");
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new TransportException("Credenciais na URL (userinfo) não são permitidas: {$url}");
        }

        if (isset($parts['port']) && (int)$parts['port'] !== 443) {
            throw new TransportException("Porta não padrão não é permitida: {$url}");
        }

        $host = (string)($parts['host'] ?? '');
        if (!$this->allowlist->isAllowed($host)) {
            throw new TransportException("Host fora da allowlist de segurança: {$host}");
        }
    }

    /**
     * Resolve e valida uma URL de redirecionamento contra a allowlist.
     *
     * @throws TransportException
     */
    public function resolveRedirectUrl(string $currentUrl, string $location): string
    {
        $target = trim($location);
        if ($target === '') {
            throw new TransportException('Cabeçalho Location de redirecionamento vazio.');
        }

        if (str_starts_with($target, '/')) {
            $parts = parse_url($currentUrl);
            $host = (string)($parts['host'] ?? '');
            $target = 'https://' . $host . $target;
        }

        $this->validateUrl($target);
        return $target;
    }

    /**
     * @param array<string, string> $headers
     */
    private function executeWithRedirects(
        string $url,
        array $headers,
        int $timeoutSeconds,
        ?string $destinationFile,
        ?int $maxBytes,
    ): HttpResponse {
        $currentUrl = $url;
        $hops = 0;

        while (true) {
            $this->validateUrl($currentUrl);

            $response = $this->transportOverride !== null
                ? ($this->transportOverride)($currentUrl, $headers, $timeoutSeconds, $destinationFile, $maxBytes)
                : $this->performCurlRequest($currentUrl, $headers, $timeoutSeconds, $destinationFile, $maxBytes);

            if (in_array($response->statusCode, [301, 302, 303, 307, 308], true)) {
                $hops++;
                if ($hops > self::MAX_REDIRECTS) {
                    throw new TransportException('Limite máximo de redirecionamentos (' . self::MAX_REDIRECTS . ') excedido.');
                }

                $location = $response->headers['location'] ?? '';
                $currentUrl = $this->resolveRedirectUrl($currentUrl, $location);
                continue;
            }

            if ($destinationFile !== null && $maxBytes !== null && is_file($destinationFile)) {
                $size = (int)filesize($destinationFile);
                if ($size > $maxBytes) {
                    @unlink($destinationFile);
                    throw new TransportException("Arquivo baixado excedeu o limite de {$maxBytes} bytes.");
                }
            }

            return $response;
        }
    }

    /**
     * @param array<string, string> $headers
     */
    private function performCurlRequest(
        string $url,
        array $headers,
        int $timeoutSeconds,
        ?string $destinationFile,
        ?int $maxBytes,
    ): HttpResponse {
        if (!function_exists('curl_init')) {
            throw new TransportException('A extensão cURL do PHP não está disponível neste servidor.');
        }

        $ch = curl_init($url);
        if ($ch === false) {
            throw new TransportException('Falha ao inicializar sessão cURL.');
        }

        $responseHeaders = [];
        $formattedHeaders = ['Accept: application/vnd.github+json'];
        foreach ($headers as $name => $value) {
            $formattedHeaders[] = "{$name}: {$value}";
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $formattedHeaders);
        curl_setopt($ch, CURLOPT_USERAGENT, self::USER_AGENT);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, min(10, max(1, $timeoutSeconds)));
        curl_setopt($ch, CURLOPT_TIMEOUT, max(1, $timeoutSeconds));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        if (defined('CURLPROTO_HTTPS')) {
            curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
        }

        curl_setopt(
            $ch,
            CURLOPT_HEADERFUNCTION,
            static function ($curl, string $headerLine) use (&$responseHeaders): int {
                $len = strlen($headerLine);
                $parts = explode(':', $headerLine, 2);
                if (count($parts) === 2) {
                    $name = strtolower(trim($parts[0]));
                    $val = trim($parts[1]);
                    if ($name !== '') {
                        $responseHeaders[$name] = $val;
                    }
                }
                return $len;
            }
        );

        $fileHandle = null;
        $writtenBytes = 0;
        $exceededMaxBytes = false;

        if ($destinationFile !== null) {
            $fileHandle = @fopen($destinationFile, 'wb');
            if ($fileHandle === false) {
                curl_close($ch);
                throw new TransportException("Não foi possível abrir o arquivo para gravação: {$destinationFile}");
            }

            curl_setopt(
                $ch,
                CURLOPT_WRITEFUNCTION,
                static function ($curl, string $chunk) use ($fileHandle, $maxBytes, &$writtenBytes, &$exceededMaxBytes): int {
                    $chunkLen = strlen($chunk);
                    $writtenBytes += $chunkLen;
                    if ($maxBytes !== null && $writtenBytes > $maxBytes) {
                        $exceededMaxBytes = true;
                        return 0; // Aborta transferência cURL imediatamente
                    }
                    $written = fwrite($fileHandle, $chunk);
                    return $written === false ? 0 : $written;
                }
            );
        } else {
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        }

        $result = curl_exec($ch);
        $errno = curl_errno($ch);
        $errorMsg = curl_error($ch);
        $statusCode = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($fileHandle !== null) {
            fclose($fileHandle);
        }

        if ($exceededMaxBytes) {
            if ($destinationFile !== null && is_file($destinationFile)) {
                @unlink($destinationFile);
            }
            throw new TransportException("Download abortado: tamanho excedeu o limite de {$maxBytes} bytes.");
        }

        if ($result === false || $errno !== 0) {
            if ($destinationFile !== null && is_file($destinationFile)) {
                @unlink($destinationFile);
            }
            throw new TransportException("Erro de transporte cURL ({$errno}): {$errorMsg}");
        }

        if ($statusCode < 100 || $statusCode > 599) {
            if ($destinationFile !== null && is_file($destinationFile)) {
                @unlink($destinationFile);
            }
            throw new TransportException("Código de status HTTP inválido recebido: {$statusCode}");
        }

        // Se for redirecionamento durante um download, limpa arquivo vazio/intermediário antes do próximo salto
        if ($destinationFile !== null && in_array($statusCode, [301, 302, 303, 307, 308], true) && is_file($destinationFile)) {
            @unlink($destinationFile);
        }

        $body = is_string($result) ? $result : '';
        $etag = $responseHeaders['etag'] ?? null;

        return new HttpResponse(
            statusCode: $statusCode,
            headers: $responseHeaders,
            body: $body,
            etag: $etag,
        );
    }
}
