<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\ValueObjects;

use RuntimeException;

/**
 * Value Object imutável representando o Token OAuth2 do AliExpress (SRP).
 */
final class AliExpressToken
{
    public function __construct(
        public readonly string $accessToken,
        public readonly string $refreshToken,
        public readonly int $expiresIn,
        public readonly int $refreshExpiresIn,
        public readonly string $sellerId = '',
        public readonly string $accountId = '',
        public readonly string $accountPlatform = '',
        public readonly string $account = '',
        public readonly int $createdAt = 0
    ) {}

    /**
     * Constrói o objeto a partir da resposta decodificada da API AliExpress.
     *
     * @param array<string, mixed> $r
     */
    public static function fromApiResponse(array $r): self
    {
        $data = $r;
        if (!isset($data['access_token'])) {
            $data = $r['data'] ?? ($r['resp_result'] ?? ($r['result'] ?? $r));
        }

        $accessToken = trim((string)($data['access_token'] ?? ''));
        if ($accessToken === '') {
            $raw = json_encode($r, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
            throw new RuntimeException("Resposta do AliExpress não trouxe access_token válido. Retorno bruto: {$raw}");
        }

        $rawExpires = (int)($data['expire_time'] ?? ($data['expires_in'] ?? 2592000));
        if ($rawExpires > 100000000000) {
            $expiresInSeconds = max(60, (int)round(($rawExpires / 1000) - time()));
        } else {
            $expiresInSeconds = $rawExpires > 0 ? $rawExpires : 2592000;
        }

        $rVal = (int)($data['refresh_token_valid_time'] ?? ($data['refresh_expires_in'] ?? 0));
        if ($rVal > 100000000000) {
            $refreshSeconds = max(0, (int)round(($rVal / 1000) - time()));
        } else {
            $refreshSeconds = $rVal;
        }

        $account = (string)($data['account'] ?? ($data['user_nick'] ?? ''));

        return new self(
            accessToken:      $accessToken,
            refreshToken:     (string)($data['refresh_token'] ?? ''),
            expiresIn:        $expiresInSeconds,
            refreshExpiresIn: $refreshSeconds,
            sellerId:         (string)($data['seller_id'] ?? ''),
            accountId:        (string)($data['account_id'] ?? ''),
            accountPlatform:  (string)($data['account_platform'] ?? ''),
            account:          $account,
            createdAt:        time()
        );
    }

    public function isExpired(): bool
    {
        if ($this->createdAt <= 0 || $this->expiresIn <= 0) {
            return false;
        }
        return time() >= ($this->createdAt + $this->expiresIn);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'access_token'             => $this->accessToken,
            'refresh_token'            => $this->refreshToken,
            'expires_in'               => $this->expiresIn,
            'refresh_expires_in'       => $this->refreshExpiresIn,
            'seller_id'                => $this->sellerId,
            'account_id'               => $this->accountId,
            'account_platform'         => $this->accountPlatform,
            'account'                  => $this->account,
            'created_at'               => $this->createdAt,
            'access_token_expires_at'  => $this->createdAt + $this->expiresIn,
            'refresh_token_expires_at' => $this->refreshExpiresIn > 0 ? ($this->createdAt + $this->refreshExpiresIn) : null,
        ];
    }
}
