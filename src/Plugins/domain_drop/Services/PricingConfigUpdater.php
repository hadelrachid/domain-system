<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Services;

use DomainSystem\Core\Http\Request;

/**
 * Atualizador e validador de configurações de precificação e margens (SRP / DRY).
 */
final class PricingConfigUpdater
{
    /**
     * Atualiza as regras de precificação a partir dos inputs da requisição HTTP.
     *
     * @param array<string, mixed> $config
     * @param Request $request
     * @return array<string, mixed>
     */
    public function updateFromRequest(array $config, Request $request): array
    {
        if ($request->input('default_markup_percent') !== null) {
            $config['default_markup_percent'] = max(10.0, (float)$request->input('default_markup_percent', 100.0));
        }

        if ($request->input('min_profit_margin_brl') !== null) {
            $config['min_profit_margin_brl'] = max(0.0, (float)$request->input('min_profit_margin_brl', 30.0));
        }

        if ($request->input('price_sync_interval_hours') !== null) {
            $intervalHours = max(1, min(24, (int)$request->input('price_sync_interval_hours', 6)));
            $config['price_sync_interval_hours'] = $intervalHours;
            $config['price_sync_interval_seconds'] = $intervalHours * 3600;
        }

        if ($request->input('volatility_index_percent') !== null) {
            $config['volatility_index_percent'] = max(0.0, min(50.0, (float)$request->input('volatility_index_percent', 12.0)));
        }

        if ($request->input('price_sync_enabled') !== null) {
            $config['price_sync_enabled'] = (bool)$request->input('price_sync_enabled', 1);
        }

        return $config;
    }
}
