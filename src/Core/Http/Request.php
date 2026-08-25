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
        return new self($_GET, $_POST, $_SERVER, $_COOKIE, $_FILES);
    }

    public function input(string $key, $default = null)
    {
        return $this->request[$key] ?? $this->query[$key] ?? $default;
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
