<?php

declare(strict_types=1);

namespace DomainSystem\Core\Monitoring;

use DomainSystem\Core\Contracts\JsonStoreInterface;
use DomainSystem\Core\Storage\AtomicJsonStore;

/**
 * Registra métricas reais de pico de memória e tempo de execução por requisição HTTP (H-05)
 * utilizando gravação atômica via AtomicJsonStore (Pilar 1 v2.4).
 */
class RequestMetricsRecorder
{
    private string $metricsFile;
    private int $maxEntries;
    private JsonStoreInterface $jsonStore;

    public function __construct(
        ?string $basePath = null,
        int $maxEntries = 30,
        JsonStoreInterface $jsonStore = new AtomicJsonStore()
    ) {
        $base = $basePath ?? (defined('DOMAIN_SYSTEM_ROOT') ? DOMAIN_SYSTEM_ROOT : dirname(__DIR__, 3));
        $this->metricsFile = rtrim($base, '/\\') . '/temp/request_metrics.json';
        $this->maxEntries = max(5, $maxEntries);
        $this->jsonStore = $jsonStore;
    }

    /**
     * Registra uma amostra de requisição concluída.
     */
    public function record(string $uri, string $method = 'GET', ?float $peakMb = null, ?float $allocatedMb = null, ?float $durationMs = null): void
    {
        if (str_contains($uri, '/api/os/monitor/stack')) {
            return;
        }

        $sample = [
            'uri'          => $uri,
            'method'       => strtoupper($method),
            'peak_mb'      => $peakMb ?? round(memory_get_peak_usage(true) / (1024 * 1024), 2),
            'allocated_mb' => $allocatedMb ?? round(memory_get_usage(true) / (1024 * 1024), 2),
            'duration_ms'  => $durationMs ?? 0.0,
            'timestamp'    => time(),
            'time_str'     => date('H:i:s'),
        ];

        $maxEntries = $this->maxEntries;
        $this->jsonStore->update($this->metricsFile, static function (array $entries) use ($sample, $maxEntries): array {
            $entries[] = $sample;
            if (count($entries) > $maxEntries) {
                $entries = array_slice($entries, -$maxEntries);
            }
            return $entries;
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRecentEntries(): array
    {
        return $this->jsonStore->read($this->metricsFile, []);
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
