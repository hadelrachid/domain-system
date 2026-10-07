<?php

namespace DomainSystem\Core\Http;

use DomainSystem\Core\Contracts\ResponseInterface;

class Response implements ResponseInterface
{
    protected string $content;
    protected int $statusCode;
    protected array $headers;

    public function __construct(string $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        
        // Ensure default charset for HTML responses to prevent mojibake
        $hasContentType = false;
        foreach (array_keys($this->headers) as $k) {
            if (strtolower($k) === 'content-type') {
                $hasContentType = true;
                break;
            }
        }
        if (!$hasContentType) {
            $this->headers['Content-Type'] = 'text/html; charset=utf-8';
        }
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function getHeader(string $name): ?string
    {
        foreach ($this->headers as $k => $v) {
            if (strtolower($k) === strtolower($name)) return $v;
        }
        return null;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->statusCode);
            foreach ($this->headers as $name => $value) {
                header($name . ': ' . $value);
            }
        }
        echo $this->content;
    }

    public static function json($data, int $status = 200): self
    {
        return new self(json_encode($data), $status, ['Content-Type' => 'application/json; charset=utf-8']);
    }

    public static function redirect(string $url, int $status = 302): self
    {
        return new self('', $status, ['Location' => $url]);
    }
}
