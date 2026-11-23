<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Services\OAuth;

use DomainSystem\Plugins\domain_drop\Contracts\AliExpressAuthorizationUrlBuilderInterface;

final class AliExpressAuthorizationUrlBuilder implements AliExpressAuthorizationUrlBuilderInterface
{
    public function __construct(
        private readonly string $appKey,
        private readonly string $authorizeEndpoint = 'https://api-sg.aliexpress.com/oauth/authorize'
    ) {}

    public function build(string $redirectUri, string $state): string
    {
        $query = [
            'response_type' => 'code',
            'force_auth'    => 'true',
            'redirect_uri'  => $redirectUri,
            'client_id'     => trim($this->appKey),
        ];
        if ($state !== '') {
            $query['state'] = trim($state);
        }

        return rtrim($this->authorizeEndpoint, '/') . '?' . http_build_query($query);
    }
}
