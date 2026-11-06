<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\commerce\Contracts;

interface WebhookSignatureVerifierInterface
{
    /**
     * @param array<string,string> $headers Cabeçalhos relevantes normalizados
     */
    public function verify(string $rawBody, array $headers): bool;
}
