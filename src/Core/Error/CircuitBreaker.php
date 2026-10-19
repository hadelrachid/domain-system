<?php

namespace DomainSystem\Core\Error;

use DomainSystem\Core\Contracts\JsonStoreInterface;
use DomainSystem\Core\Storage\AtomicJsonStore;
use Throwable;

class CircuitBreaker
{
    private const PROTECTED_PLUGINS = ['SystemMonitor', 'SystemAdmin'];

    private string $configPath;
    private string $disarmedPath;
    private JsonStoreInterface $jsonStore;

    /** @var array<string, int> */
    private array $routeFailureCounts = [];

    public function __construct(
        string $configPath,
        string $disarmedPath,
        JsonStoreInterface $jsonStore = new AtomicJsonStore()
    ) {
        $this->configPath = $configPath;
        $this->disarmedPath = $disarmedPath;
        $this->jsonStore = $jsonStore;
    }

    public function detectCulpritPlugin(Throwable $e): ?string
    {
        $file = $e->getFile();
        $trace = $e->getTraceAsString();
        $message = $e->getMessage();

        if (preg_match("/src[\/\\\\]Plugins[\/\\\\]([a-zA-Z0-9_-]+)/i", $file, $matches)) {
            return $matches[1];
        }
        if (preg_match("/src[\/\\\\]Plugins[\/\\\\]([a-zA-Z0-9_-]+)/i", $trace, $matches)) {
            return $matches[1];
        }
        if (preg_match("/src[\/\\\\]Plugins[\/\\\\]([a-zA-Z0-9_-]+)/i", $message, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public function analyzeAndDisarm(Throwable $e): ?string
    {
        $pluginName = $this->detectCulpritPlugin($e);
        if ($pluginName === null || in_array($pluginName, self::PROTECTED_PLUGINS, true)) {
            return null;
        }

        return $this->disarmPlugin($pluginName);
    }

    /**
     * Registra uma falha durante o despacho de uma rota HTTP de plugin (Pilar 2 v2.4).
     * Só desarma o plugin quando o número de falhas atinge o threshold configurado.
     */
    public function recordRouteException(Throwable $e, int $threshold = 3): ?string
    {
        $pluginName = $this->detectCulpritPlugin($e);
        if ($pluginName === null || in_array($pluginName, self::PROTECTED_PLUGINS, true)) {
            return null;
        }

        $threshold = max(1, $threshold);
        $this->routeFailureCounts[$pluginName] = ($this->routeFailureCounts[$pluginName] ?? 0) + 1;

        if ($this->routeFailureCounts[$pluginName] >= $threshold) {
            return $this->disarmPlugin($pluginName);
        }

        return null;
    }

    public function getRouteFailureCount(string $pluginName): int
    {
        return $this->routeFailureCounts[$pluginName] ?? 0;
    }

    private function disarmPlugin(string $pluginName): ?string
    {
        if (!file_exists($this->configPath)) {
            return null;
        }

        $wasDisarmed = false;
        $this->jsonStore->update($this->configPath, static function (array $states) use ($pluginName, &$wasDisarmed): array {
            if (isset($states[$pluginName]) && $states[$pluginName] === true) {
                $states[$pluginName] = false;
                $wasDisarmed = true;
            }
            return $states;
        });

        if ($wasDisarmed) {
            $this->jsonStore->update($this->disarmedPath, static function (array $disarmed) use ($pluginName): array {
                $disarmed[$pluginName] = date('Y-m-d H:i:s');
                return $disarmed;
            });
            return $pluginName;
        }

        return null;
    }
}
