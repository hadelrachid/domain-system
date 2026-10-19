<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Support;

use Closure;
use DomainSystem\Core\Contracts\Update\HttpClientInterface;
use DomainSystem\Core\Update\Exception\TransportException;
use DomainSystem\Core\Update\Value\HttpResponse;

final class ArrayHttpClient implements HttpClientInterface
{
    /**
     * @var array<string, HttpResponse|Closure(string, array<string, string>): HttpResponse>
     */
    private array $routes = [];

    /**
     * @var list<array{method: string, url: string, headers: array<string, string>}>
     */
    private array $requests = [];

    /**
     * @param array<string, HttpResponse|Closure(string, array<string, string>): HttpResponse> $routes
     */
    public function __construct(array $routes = [])
    {
        $this->routes = $routes;
    }

    /**
     * @param HttpResponse|Closure(string, array<string, string>): HttpResponse $handler
     */
    public function when(string $url, HttpResponse|Closure $handler): void
    {
        $this->routes[$url] = $handler;
    }

    public function get(string $url, array $headers = [], int $timeoutSeconds = 10): HttpResponse
    {
        $this->requests[] = ['method' => 'GET', 'url' => $url, 'headers' => $headers];

        return $this->resolve($url, $headers);
    }

    public function download(
        string $url,
        string $destinationFile,
        int $maxBytes,
        array $headers = [],
        int $timeoutSeconds = 60,
    ): HttpResponse {
        $this->requests[] = ['method' => 'DOWNLOAD', 'url' => $url, 'headers' => $headers];

        $response = $this->resolve($url, $headers);
        if ($response->statusCode >= 400) {
            return $response;
        }

        if (strlen($response->body) > $maxBytes) {
            if (is_file($destinationFile)) {
                @unlink($destinationFile);
            }
            throw new TransportException("Download excedeu o limite máximo de {$maxBytes} bytes.");
        }

        $dir = dirname($destinationFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($destinationFile, $response->body);

        return $response;
    }

    /**
     * @return list<array{method: string, url: string, headers: array<string, string>}>
     */
    public function recordedRequests(): array
    {
        return $this->requests;
    }

    /**
     * @param array<string, string> $headers
     */
    private function resolve(string $url, array $headers): HttpResponse
    {
        if (!array_key_exists($url, $this->routes)) {
            throw new TransportException("Nenhuma resposta falsa configurada para a URL: {$url}");
        }

        $entry = $this->routes[$url];
        if ($entry instanceof Closure) {
            return $entry($url, $headers);
        }

        return $entry;
    }
}
