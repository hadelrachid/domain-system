<?php

namespace DomainSystem\Core\Http;

class Request
{
    public array $query;
    public array $request;
    public array $server;
    public array $cookies;
    public array $files;

    public function __construct(array $query = [], array $request = [], array $server = [], array $cookies = [], array $files = [])
    {
        $this->query = $query;
        $this->request = $request;
        $this->server = $server;
        $this->cookies = $cookies;
        $this->files = $files;
    }

    public static function capture(): self
    {
        $post = $_POST;

        // Se o corpo for JSON (enviado pelo fetch do front-end), parsear aqui
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $rawBody = file_get_contents('php://input');
            if ($rawBody) {
                $jsonData = json_decode($rawBody, true);
                if (is_array($jsonData)) {
                    $post = array_merge($post, $jsonData);
                }
            }
        }

        return new self($_GET, $post, $_SERVER, $_COOKIE, $_FILES);
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
}
