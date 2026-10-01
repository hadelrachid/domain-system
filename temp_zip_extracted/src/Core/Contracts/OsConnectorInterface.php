<?php

namespace DomainSystem\Core\Contracts;

interface OsConnectorInterface
{
    public function requireLink(string $linkName): self;
    public function provideLink(string $linkName, string $className): self;
    public function requestSlot(string $slotName): self;
    public function listenHook(string $hookName): self;
    public function provideHook(string $hookName): self;

    public function getRequiredLinks(): array;
    public function getProvidedLinks(): array;
    public function getRequestedSlots(): array;
    public function getListenedHooks(): array;
    public function getProvidedHooks(): array;
}
