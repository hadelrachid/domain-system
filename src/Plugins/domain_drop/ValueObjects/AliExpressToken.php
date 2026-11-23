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
     * Desembrulha respostas com envelopes arbitrários (IOP SDK, simplify=false, gateways rest).
     *
     * @param array<string, mixed> $r
     * @return array<string, mixed>
     */
    public static function unwrapPayload(array $r): array
    {
        $current = $r;
        $knownKeys = [
            'data',
            'resp_result',
            'result',
            'aliexpress_open_token_create_response',
            '/auth/token/create_response',
            '/auth/token/refresh_response',
            '/wl/gray/first_response',
            'aliexpress_ds_text_search_response',
        ];

        for ($i = 0; $i < 4; $i++) {
            $unwrapped = false;
            foreach ($knownKeys as $k) {
                if (isset($current[$k]) && is_array($current[$k])) {
                    $current = array_merge($current, $current[$k]);
                    $unwrapped = true;
                    break;
                }
            }
            if (!$unwrapped && count($current) === 1) {
                $firstKey = (string)array_key_first($current);
                if ((str_ends_with($firstKey, '_response') || str_starts_with($firstKey, '/')) && is_array($current[$firstKey])) {
                    $current = array_merge($current, $current[$firstKey]);
                    $unwrapped = true;
                }
            }
            if (!$unwrapped) {
                break;
            }
        }

        return $current;
    }

    /**
     * Constrói o objeto a partir da resposta decodificada da API AliExpress.
     *
     * @param array<string, mixed> $r
     */
    public static function fromApiResponse(array $r): self
    {
        $data = self::unwrapPayload($r);

        $accessToken = trim((string)($data['access_token'] ?? ''));
        if ($accessToken === '') {
            $raw = json_encode($r, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
            error_log("[AliExpressToken] Resposta do AliExpress sem access_token válido: {$raw}");
            throw new RuntimeException("Resposta do AliExpress não trouxe access_token válido.");
        }

        // Normalização de expiração: prioriza expires_in (segundos relativos)
        if (isset($data['expires_in']) && (int)$data['expires_in'] > 0) {
            $expiresInSeconds = (int)$data['expires_in'];
        } elseif (isset($data['expire_time'])) {
            $rawExpires = (int)$data['expire_time'];
            if ($rawExpires > 100000000000) {
                $expiresInSeconds = max(60, (int)round(($rawExpires / 1000) - time()));
            } elseif ($rawExpires > 1000000000) {
                $expiresInSeconds = max(60, $rawExpires - time());
            } else {
                $expiresInSeconds = max(60, $rawExpires);
            }
        } else {
            $expiresInSeconds = 2592000;
        }

        // Normalização de expiração do refresh token
        if (isset($data['refresh_expires_in']) && (int)$data['refresh_expires_in'] > 0) {
            $refreshSeconds = (int)$data['refresh_expires_in'];
        } elseif (isset($data['refresh_token_valid_time'])) {
            $rVal = (int)$data['refresh_token_valid_time'];
            if ($rVal > 100000000000) {
                $refreshSeconds = max(0, (int)round(($rVal / 1000) - time()));
            } elseif ($rVal > 1000000000) {
                $refreshSeconds = max(0, $rVal - time());
            } else {
                $refreshSeconds = max(0, $rVal);
            }
        } else {
            $refreshSeconds = 0;
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
