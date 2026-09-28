<?php

namespace DomainSystem\Core\Contracts;

interface OsRuntimeInterface
{
    public function getLink(string $linkName);
    public function contributeTo(string $slotName, mixed $payload): void;
    public function onHook(string $hookName, callable $callback, int $priority = 0): void;
    public function dispatchHook(string $hookName, mixed ...$payload): void;
}
