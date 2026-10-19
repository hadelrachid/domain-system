<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Value;

use InvalidArgumentException;

final readonly class SchemaResult
{
    /**
     * @param list<string> $executed
     */
    public function __construct(
        public array $executed,
        public ?string $failed = null,
        public ?string $error = null,
    ) {
        foreach ($this->executed as $name) {
            if (!is_string($name) || trim($name) === '') {
                throw new InvalidArgumentException('Cada migration em SchemaResult::executed deve ser uma string não vazia.');
            }
        }
        if ($this->failed !== null && trim($this->failed) === '') {
            throw new InvalidArgumentException('O campo failed de SchemaResult não pode ser string vazia.');
        }
        if ($this->error !== null && trim($this->error) === '') {
            throw new InvalidArgumentException('O campo error de SchemaResult não pode ser string vazia.');
        }
    }

    public function isOk(): bool
    {
        return $this->failed === null && $this->error === null;
    }
}
