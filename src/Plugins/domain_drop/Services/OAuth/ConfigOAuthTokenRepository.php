<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Services\OAuth;

use DomainSystem\Plugins\domain_drop\Contracts\OAuthTokenRepositoryInterface;
use DomainSystem\Plugins\domain_drop\ValueObjects\AliExpressToken;

/**
 * Repositório de persistência de tokens no arquivo de configuração do DomainDrop (DIP).
 */
final class ConfigOAuthTokenRepository implements OAuthTokenRepositoryInterface
{
    private string $configFile;

    public function __construct(?string $basePath = null)
    {
        $root = $basePath ?? (defined('DOMAIN_SYSTEM_ROOT') ? DOMAIN_SYSTEM_ROOT : dirname(__DIR__, 5));
        $this->configFile = $root . '/config/plugins/domain_drop.json';
    }

    public function store(AliExpressToken $token): void
    {
        $config = $this->loadConfig();
        $tokenData = $token->toArray();

        $config['access_token'] = $tokenData['access_token'];
        if ($tokenData['refresh_token'] !== '') {
            $config['refresh_token'] = $tokenData['refresh_token'];
        }
        $config['access_token_expires_at'] = $tokenData['access_token_expires_at'];
        if ($tokenData['refresh_token_expires_at'] !== null) {
            $config['refresh_token_expires_at'] = $tokenData['refresh_token_expires_at'];
        }
        if ($tokenData['account'] !== '') {
            $config['oauth_account'] = $tokenData['account'];
        }

        $this->saveConfig($config);
    }

    public function load(): ?AliExpressToken
    {
        $config = $this->loadConfig();
        $at = trim((string)($config['access_token'] ?? ''));
        if ($at === '') {
            return null;
        }

        $expiresAt = (int)($config['access_token_expires_at'] ?? 0);
        $expiresIn = max(0, $expiresAt - time());

        $refreshExpiresAt = (int)($config['refresh_token_expires_at'] ?? 0);
        $refreshExpiresIn = max(0, $refreshExpiresAt - time());

        return new AliExpressToken(
            accessToken:      $at,
            refreshToken:     (string)($config['refresh_token'] ?? ''),
            expiresIn:        $expiresIn,
            refreshExpiresIn: $refreshExpiresIn,
            account:          (string)($config['oauth_account'] ?? ''),
            createdAt:        $expiresAt > 0 ? ($expiresAt - 2592000) : time()
        );
    }

    public function clear(): void
    {
        $config = $this->loadConfig();
        unset(
            $config['access_token'],
            $config['refresh_token'],
            $config['access_token_expires_at'],
            $config['refresh_token_expires_at'],
            $config['oauth_account']
        );
        $this->saveConfig($config);
    }

    /**
     * @return array<string, mixed>
     */
    private function loadConfig(): array
    {
        if (file_exists($this->configFile)) {
            $raw = file_get_contents($this->configFile);
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }
        return [];
    }

    /**
     * @param array<string, mixed> $config
     */
    private function saveConfig(array $config): void
    {
        $dir = dirname($this->configFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents(
            $this->configFile,
            json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }
}
