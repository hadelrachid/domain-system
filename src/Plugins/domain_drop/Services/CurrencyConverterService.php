<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Services;

use DomainSystem\Plugins\domain_drop\Contracts\CurrencyConverterInterface;
use Throwable;

/**
 * Serviço de Conversão Cambial Dinâmica e Precificação (SRP / DIP).
 * 
 * Obtém cotação USD/BRL em tempo real com cache inteligente de 12 horas
 * e aplica regras de margem de lucro (markup percentual e lucro mínimo garantido).
 */
class CurrencyConverterService implements CurrencyConverterInterface
{
    private string $cacheFile;
    private float $defaultMarkupPercent;
    private float $minProfitBrl;
    private float $fallbackUsdRate = 5.80;

    public function __construct(
        ?string $cacheDirOrFile = null,
        float $defaultMarkupPercent = 100.0,
        float $minProfitBrl = 30.0,
        float $fallbackUsdRate = 5.80
    ) {
        if ($cacheDirOrFile !== null && str_ends_with(strtolower($cacheDirOrFile), '.json')) {
            $this->cacheFile = $cacheDirOrFile;
            $dir = dirname($cacheDirOrFile);
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
        } else {
            $baseDir = $cacheDirOrFile ?? (defined('DOMAIN_SYSTEM_ROOT') ? DOMAIN_SYSTEM_ROOT . '/storage/cache' : dirname(__DIR__, 4) . '/storage/cache');
            if (!is_dir($baseDir)) {
                @mkdir($baseDir, 0775, true);
            }
            $this->cacheFile = $baseDir . '/exchange_rates.json';
        }
        $this->defaultMarkupPercent = $defaultMarkupPercent;
        $this->minProfitBrl = $minProfitBrl;
        $this->fallbackUsdRate = $fallbackUsdRate;
    }

    public function getExchangeRate(string $from = 'USD', string $to = 'BRL'): float
    {
        if (strtoupper($from) === strtoupper($to)) {
            return 1.0;
        }

        // 1. Tenta carregar do cache local (válido por 12 horas)
        $cached = $this->loadCachedRate($from, $to);
        if ($cached !== null) {
            return $cached;
        }

        // 2. Consulta API pública de cotação (AwesomeAPI - Banco Central)
        try {
            $url = "https://economia.awesomeapi.com.br/last/{$from}-{$to}";
            $ctx = stream_context_create([
                'http' => [
                    'timeout'    => 3,
                    'user_agent' => 'DomainDrop-CurrencyConverter/1.0',
                ],
            ]);

            $json = @file_get_contents($url, false, $ctx);
            if ($json !== false) {
                $data = json_decode($json, true);
                $key = "{$from}{$to}";
                if (isset($data[$key]['bid'])) {
                    $rate = (float)$data[$key]['bid'];
                    if ($rate > 0) {
                        $this->saveCachedRate($from, $to, $rate);
                        return $rate;
                    }
                }
            }
        } catch (Throwable $e) {
            // Silencia para fallback defensivo
        }

        // 3. Fallback defensivo para não travar a loja offline
        return $this->fallbackUsdRate;
    }

    public function calculateSalePrice(float $costUsd, ?float $customMarkupPercent = null, ?float $minProfitBrl = null): float
    {
        if ($costUsd <= 0.0) {
            return 0.0;
        }

        $rate = $this->getExchangeRate('USD', 'BRL');
        $costBrl = $costUsd * $rate;

        $markup = $customMarkupPercent ?? $this->defaultMarkupPercent;
        $minProfit = $minProfitBrl ?? $this->minProfitBrl;

        // Calcula com o markup (ex: 100% de markup dobra o custo original)
        $calculatedSalePrice = $costBrl * (1 + ($markup / 100.0));

        // Assegura que o lucro líquido em Reais seja no mínimo o configurado
        $profit = $calculatedSalePrice - $costBrl;
        if ($profit < $minProfit) {
            $calculatedSalePrice = $costBrl + $minProfit;
        }

        // Arredondamento comercial atraente (ex: termina em .90 ou .00)
        return round($calculatedSalePrice, 2);
    }

    private function loadCachedRate(string $from, string $to): ?float
    {
        if (!file_exists($this->cacheFile)) {
            return null;
        }

        $raw = @file_get_contents($this->cacheFile);
        if ($raw === false) {
            return null;
        }

        $cache = json_decode($raw, true);
        $key = "{$from}_{$to}";

        if (isset($cache[$key]['rate'], $cache[$key]['time'])) {
            // Expira em 12 horas (43.200 segundos)
            if ((time() - (int)$cache[$key]['time']) < 43200) {
                return (float)$cache[$key]['rate'];
            }
        } elseif (isset($cache[$from]) && is_numeric($cache[$from])) {
            return (float)$cache[$from];
        } elseif (isset($cache[$key]) && is_numeric($cache[$key])) {
            return (float)$cache[$key];
        }

        return null;
    }

    private function saveCachedRate(string $from, string $to, float $rate): void
    {
        $cache = [];
        if (file_exists($this->cacheFile)) {
            $raw = @file_get_contents($this->cacheFile);
            if ($raw !== false) {
                $cache = json_decode($raw, true) ?: [];
            }
        }

        $key = "{$from}_{$to}";
        $cache[$key] = [
            'rate' => $rate,
            'time' => time(),
        ];

        @file_put_contents($this->cacheFile, json_encode($cache, JSON_PRETTY_PRINT));
    }
}
