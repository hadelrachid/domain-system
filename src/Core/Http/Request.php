<?php

declare(strict_types=1);

namespace DomainSystem\Core\Http;

use DomainSystem\Core\Contracts\RequestInterface;

class Request implements RequestInterface
{
    public array $query;
    public array $request;
    public array $server;
    public array $cookies;
    public array $files;
    private array $jsonBody;
    private string $rawBody = '';

    public function __construct(
        array $query = [],
        array $request = [],
        array $server = [],
        array $cookies = [],
        array $files = [],
        array $jsonBody = [],
        string $rawBody = ''
    ) {
        $this->query = $query;
        $this->request = $request;
        $this->server = $server;
        $this->cookies = $cookies;
        $this->files = $files;
        $this->jsonBody = $jsonBody;
        $this->rawBody = $rawBody;
    }

    public static function capture(): self
    {
        $post = $_POST;
        $jsonBody = [];

        // Se o corpo for JSON (enviado pelo fetch do front-end), parsear aqui
        $contentType = $_SERVER['CONTENT_TYPE'] ?? ($_SERVER['HTTP_CONTENT_TYPE'] ?? '');
        $rawBody = file_get_contents('php://input');
        $rawString = is_string($rawBody) ? $rawBody : '';
        if (trim($rawString) !== '') {
            if (
                str_contains(strtolower((string)$contentType), 'application/json') ||
                str_starts_with(ltrim($rawString), '{') ||
                str_starts_with(ltrim($rawString), '[')
            ) {
                $jsonData = json_decode($rawString, true);
                if (is_array($jsonData)) {
                    $jsonBody = $jsonData;
                    $post = array_merge($post, $jsonData);
                }
            }
        }

        return new self($_GET, $post, $_SERVER, $_COOKIE, $_FILES, $jsonBody, $rawString);
    }

    public function rawBody(): string
    {
        return $this->rawBody;
    }

    /**
     * Retorna o payload JSON decodificado associado a esta requisição (sem depender de leitura global repetida).
     *
     * @return array<string, mixed>
     */
    public function json(): array
    {
        return $this->jsonBody;
    }

    /**
     * Define explicitamente o corpo JSON (útil em testes unitários isolados de php://input).
     *
     * @param array<string, mixed> $jsonBody
     */
    public function withJsonBody(array $jsonBody): self
    {
        $clone = clone $this;
        $clone->jsonBody = $jsonBody;
        $clone->request = array_merge($clone->request, $jsonBody);
        $clone->rawBody = (string)json_encode($jsonBody);
        return $clone;
    }

    public function input(string $key, $default = null)
    {
        return $this->request[$key] ?? $this->query[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->request) || array_key_exists($key, $this->query);
    }

    public function all(): array
    {
        return array_merge($this->query, $this->request);
    }

    public function method(): string
    {
        return $this->server['REQUEST_METHOD'] ?? 'GET';
    }

    public function uri(): string
    {
        return $this->server['REQUEST_URI'] ?? '/';
    }

    public function file(string $key)
    {
        return $this->files[$key] ?? null;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        $formatted = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        if (isset($this->server[$formatted])) {
            return (string)$this->server[$formatted];
        }
        $direct = strtoupper(str_replace('-', '_', $name));
        if (isset($this->server[$direct])) {
            return (string)$this->server[$direct];
        }
        return $default;
    }
}
