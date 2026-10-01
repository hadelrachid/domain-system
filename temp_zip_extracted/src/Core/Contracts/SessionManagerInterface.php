<?php

namespace DomainSystem\Core\Contracts;

interface SessionManagerInterface
{
    public function start(): void;
    public function set(string $key, $value): void;
    public function get(string $key, $default = null);
    public function has(string $key): bool;
    public function remove(string $key): void;
    public function destroy(): void;
    public function regenerate(): void;
    public function setFlash(string $type, string $message): void;
    public function getFlash(): ?array;
    public function getCsrfToken(): string;
    public function validateCsrfToken(?string $token): bool;
}
