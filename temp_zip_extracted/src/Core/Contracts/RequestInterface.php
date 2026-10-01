<?php

namespace DomainSystem\Core\Contracts;

interface RequestInterface
{
    public function input(string $key, $default = null);
    public function has(string $key): bool;
    public function all(): array;
    public function method(): string;
    public function uri(): string;
    public function file(string $key);
}
