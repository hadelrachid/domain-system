<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Services;

/**
 * Rastreador de telemetria e consumo de cota mensal da AliExpress Open Platform (SRP).
 */
final class ApiUsageTracker
{
    private string $storageFile;
    private int $defaultMonthlyLimit;

    public function __construct(?string $basePath = null, int $defaultMonthlyLimit = 1000000)
    {
        $root = $basePath ?? (defined('DOMAIN_SYSTEM_ROOT') ? DOMAIN_SYSTEM_ROOT : dirname(__DIR__, 4));
        $this->storageFile = $root . '/storage/cache/aliexpress_api_usage.json';
        $this->defaultMonthlyLimit = $defaultMonthlyLimit;
    }

    /**
     * Registra uma requisição disparada ao gateway do AliExpress.
     */
    public function recordRequest(): void
    {
        $stats = $this->loadStats();
        $currentMonth = date('Y-m');

        if (($stats['current_month'] ?? '') !== $currentMonth) {
            $stats['current_month'] = $currentMonth;
            $stats['requests_this_month'] = 0;
        }

        $stats['requests_this_month'] = (int)($stats['requests_this_month'] ?? 0) + 1;
        $stats['requests_total'] = (int)($stats['requests_total'] ?? 0) + 1;
        $stats['last_request_at'] = time();

        $this->saveStats($stats);
    }

    /**
     * Retorna os dados analíticos de consumo da cota e saúde de requisições.
     *
     * @return array{
     *     current_month: string,
     *     requests_this_month: int,
     *     monthly_quota_limit: int,
     *     percentage_used: float,
     *     percentage_available: float,
     *     requests_total: int,
     *     last_request_at: int|null
     * }
     */
    public function getUsageStats(): array
    {
        $stats = $this->loadStats();
        $currentMonth = date('Y-m');

        if (($stats['current_month'] ?? '') !== $currentMonth) {
            $stats['current_month'] = $currentMonth;
            $stats['requests_this_month'] = 0;
        }

        $used = (int)($stats['requests_this_month'] ?? 0);
        $limit = max(1, (int)($stats['monthly_quota_limit'] ?? $this->defaultMonthlyLimit));
        $percentageUsed = round(($used / $limit) * 100, 3);
        $percentageAvailable = max(0.0, round(100.0 - $percentageUsed, 3));

        return [
            'current_month'        => (string)$stats['current_month'],
            'requests_this_month'  => $used,
            'monthly_quota_limit'  => $limit,
            'percentage_used'      => $percentageUsed,
            'percentage_available' => $percentageAvailable,
            'requests_total'       => (int)($stats['requests_total'] ?? $used),
            'last_request_at'      => isset($stats['last_request_at']) ? (int)$stats['last_request_at'] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function loadStats(): array
    {
        if (file_exists($this->storageFile)) {
            $raw = @file_get_contents($this->storageFile);
            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        return [
            'current_month'       => date('Y-m'),
            'requests_this_month' => 0,
            'monthly_quota_limit' => $this->defaultMonthlyLimit,
            'requests_total'      => 0,
            'last_request_at'     => null,
        ];
    }

    /**
     * @param array<string, mixed> $stats
     */
    private function saveStats(array $stats): void
    {
        $dir = dirname($this->storageFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents(
            $this->storageFile,
            json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}
