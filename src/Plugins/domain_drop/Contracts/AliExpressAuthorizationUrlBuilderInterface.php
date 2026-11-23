<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Contracts;

/**
 * Construtor de URL de Autorização OAuth2 do AliExpress (SRP / OCP).
 */
interface AliExpressAuthorizationUrlBuilderInterface
{
    public function build(string $redirectUri, string $state): string;
}
