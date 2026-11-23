<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Contracts;

use DomainSystem\Plugins\domain_drop\ValueObjects\AliExpressToken;

/**
 * Contrato de repositório para persistência do Access Token e Refresh Token (DIP / SOLID).
 */
interface OAuthTokenRepositoryInterface
{
    public function store(AliExpressToken $token): void;
    public function load(): ?AliExpressToken;
    public function clear(): void;
}
