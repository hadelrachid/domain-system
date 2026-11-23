<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Contracts;

use DomainSystem\Plugins\domain_drop\ValueObjects\AliExpressToken;

/**
 * Serviço de negócio para troca do authorization_code e refresh do Token (SRP / DIP).
 */
interface TokenExchangeServiceInterface
{
    public function exchangeCode(string $code): AliExpressToken;
    public function refresh(string $refreshToken): AliExpressToken;
}
