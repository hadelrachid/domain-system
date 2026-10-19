<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Value;

use InvalidArgumentException;

final readonly class HttpResponse
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public int $statusCode,
        public array $headers,
        public string $body,
        public ?string $etag = null,
    ) {
        if ($this->statusCode < 100 || $this->statusCode > 599) {
            throw new InvalidArgumentException('O statusCode de HttpResponse deve estar entre 100 e 599.');
        }
        foreach ($this->headers as $name => $val) {
            if (!is_string($name) || trim($name) === '' || !is_string($val)) {
                throw new InvalidArgumentException('Cabeçalhos de HttpResponse devem ter chave string não vazia e valor string.');
            }
        }
    }

    public function isNotModified(): bool
    {
        return $this->statusCode === 304;
    }
}
