<?php

declare(strict_types=1);

namespace DomainSystem\Core\Monitoring;

/**
 * Registra métricas reais de pico de memória e tempo de execução por requisição HTTP (H-05).
 *
 * Em ambientes PHP-FPM / Apache (shared-nothing), o worker de polling do monitor só enxerga
 * sua própria memória (~2 MB) se não houver um agregador compartilhado das requisições recentes.
 */
class RequestMetricsRecorder
{
    private string $metricsFile;
    private int $maxEntries;

    public function __construct(?string $basePath = null, int $maxEntries = 30)
    {
        $base = $basePath ?? (defined('DOMAIN_SYSTEM_ROOT') ? DOMAIN_SYSTEM_ROOT : dirname(__DIR__, 3));
        $this->metricsFile = rtrim($base, '/\\') . '/temp/request_metrics.json';
        $this->maxEntries = max(5, $maxEntries);
    }

    /**
     * Registra uma amostra de requisição concluída.
     */
    public function record(string $uri, string $method = 'GET', ?float $peakMb = null, ?float $allocatedMb = null, ?float $durationMs = null): void
    {
        // Ignora o próprio endpoint de polling do monitor para não poluir os picos reais
        if (str_contains($uri, '/api/os/monitor/stack')) {
            return;
        }

        $dir = dirname($this->metricsFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $entries = $this->getRecentEntries();
        $entries[] = [
            'uri'          => $uri,
            'method'       => strtoupper($method),
            'peak_mb'      => $peakMb ?? round(memory_get_peak_usage(true) / (1024 * 1024), 2),
            'allocated_mb' => $allocatedMb ?? round(memory_get_usage(true) / (1024 * 1024), 2),
            'duration_ms'  => $durationMs ?? 0.0,
            'timestamp'    => time(),
            'time_str'     => date('H:i:s'),
        ];

        if (count($entries) > $this->maxEntries) {
            $entries = array_slice($entries, -$this->maxEntries);
        }

        $json = json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return;
        }

        $tmpFile = $this->metricsFile . '.tmp.' . bin2hex(random_bytes(4));
        if (@file_put_contents($tmpFile, $json, LOCK_EX) !== false) {
            if (!@rename($tmpFile, $this->metricsFile)) {
                @file_put_contents($this->metricsFile, $json, LOCK_EX);
                @unlink($tmpFile);
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRecentEntries(): array
    {
        if (!file_exists($this->metricsFile)) {
            return [];
        }

        $content = @file_get_contents($this->metricsFile);
        if (!$content) {
            return [];
        }

        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Retorna o maior pico de memória (em MB) observado nas requisições recentes.
     */
    public function getMaxRecentPeakMb(): float
    {
        $max = 0.0;
        foreach ($this->getRecentEntries() as $entry) {
            $peak = (float)($entry['peak_mb'] ?? 0.0);
            if ($peak > $max) {
                $max = $peak;
            }
        }
        return round($max, 2);
    }

    public function clear(): void
    {
        if (file_exists($this->metricsFile)) {
            @unlink($this->metricsFile);
        }
    }
}
