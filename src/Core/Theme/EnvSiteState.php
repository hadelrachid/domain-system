<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme;

use DomainSystem\Core\Contracts\SiteStateInterface;

/**
 * Implementação do estado do site baseada em config/active_theme.json e variáveis de ambiente (.env).
 */
class EnvSiteState implements SiteStateInterface
{
    private string $status;
    private string $activeTheme;

    public function __construct(?string $status = null, ?string $theme = null, ?string $basePath = null)
    {
        $base = $basePath ?? (defined('DOMAIN_SYSTEM_ROOT') ? DOMAIN_SYSTEM_ROOT : dirname(__DIR__, 3));
        $configFile = $base . '/config/active_theme.json';
        $configData = [];
        if (file_exists($configFile)) {
            $configData = json_decode((string)file_get_contents($configFile), true) ?: [];
        }

        if ($basePath !== null) {
            $rawStatus = $status ?? ($configData['status'] ?? (getenv('SITE_STATUS') ?: ($_ENV['SITE_STATUS'] ?? self::STATUS_LIVE)));
            $this->activeTheme = $theme ?? ($configData['active_theme'] ?? (getenv('ACTIVE_THEME') ?: ($_ENV['ACTIVE_THEME'] ?? 'default')));
        } else {
            $envStatus = getenv('SITE_STATUS') ?: ($_ENV['SITE_STATUS'] ?? null);
            $rawStatus = $status ?? ($envStatus ?: ($configData['status'] ?? self::STATUS_LIVE));

            $envTheme = getenv('ACTIVE_THEME') ?: ($_ENV['ACTIVE_THEME'] ?? null);
            $this->activeTheme = $theme ?? ($envTheme ?: ($configData['active_theme'] ?? 'default'));
        }

        $validStatuses = [self::STATUS_COMING_SOON, self::STATUS_MAINTENANCE, self::STATUS_LIVE];
        $this->status = in_array($rawStatus, $validStatuses, true) ? $rawStatus : self::STATUS_LIVE;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isComingSoon(): bool
    {
        return $this->status === self::STATUS_COMING_SOON;
    }

    public function isMaintenance(): bool
    {
        return $this->status === self::STATUS_MAINTENANCE;
    }

    public function isLive(): bool
    {
        return $this->status === self::STATUS_LIVE;
    }

    public function getActiveTheme(): string
    {
        return $this->activeTheme;
    }
}
