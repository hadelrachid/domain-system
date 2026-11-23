<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Services\OAuth;

use DomainSystem\Plugins\domain_drop\Contracts\IopHttpClientInterface;
use DomainSystem\Plugins\domain_drop\Contracts\OAuthTokenRepositoryInterface;
use DomainSystem\Plugins\domain_drop\Contracts\TokenExchangeServiceInterface;
use DomainSystem\Plugins\domain_drop\ValueObjects\AliExpressToken;

/**
 * Serviço de negócio para troca de autorização OAuth2 por Access Token (SRP / DIP).
 */
final class TokenExchangeService implements TokenExchangeServiceInterface
{
    public function __construct(
        private readonly IopHttpClientInterface $httpClient,
        private readonly OAuthTokenRepositoryInterface $repository
    ) {}

    public function exchangeCode(string $code): AliExpressToken
    {
        $data = $this->httpClient->call('/auth/token/create', ['code' => trim($code)]);
        $token = AliExpressToken::fromApiResponse($data);
        $this->repository->store($token);
        return $token;
    }

    public function refresh(string $refreshToken): AliExpressToken
    {
        $data = $this->httpClient->call('/auth/token/refresh', ['refresh_token' => trim($refreshToken)]);
        $token = AliExpressToken::fromApiResponse($data);
        $this->repository->store($token);
        return $token;
    }
}
