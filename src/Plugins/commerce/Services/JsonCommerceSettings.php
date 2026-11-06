<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\commerce\Services;

use DomainSystem\Plugins\commerce\Contracts\CommerceSettingsInterface;
use Throwable;

/**
 * Persistência atômica e segura de configurações do DomainCommerce em JSON.
 * Protegido no diretório storage/commerce/.
 */
class JsonCommerceSettings implements CommerceSettingsInterface
{
    private string $filePath;
    /** @var array<string, mixed> */
    private array $data = [];
    private bool $loaded = false;

    public function __construct(?string $filePath = null)
    {
        if ($filePath === null) {
            $baseDir = defined('DS_ROOT') ? DS_ROOT : dirname(__DIR__, 4);
            $filePath = $baseDir . '/storage/commerce/settings.json';
        }
        $this->filePath = $filePath;
    }

    private function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $this->data = [];
        if (is_file($this->filePath)) {
            try {
                $raw = @file_get_contents($this->filePath);
                if ($raw !== false && $raw !== '') {
                    $decoded = json_decode($raw, true);
                    if (is_array($decoded)) {
                        $this->data = $decoded;
                    }
                }
            } catch (Throwable $e) {}
        }

        $this->loaded = true;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->load();
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->load();
        $this->data[$key] = $value;
        $this->persist();
    }

    public function all(): array
    {
        $this->load();
        return $this->data;
    }

    public function save(array $settings): bool
    {
        $this->load();
        foreach ($settings as $key => $val) {
            $this->data[(string)$key] = $val;
        }
        return $this->persist();
    }

    private function persist(): bool
    {
        $dir = dirname($this->filePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $json = json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return false;
        }

        $tmpFile = $this->filePath . '.tmp.' . bin2hex(random_bytes(4));
        if (@file_put_contents($tmpFile, $json) !== false) {
            return @rename($tmpFile, $this->filePath);
        }

        return false;
    }
}
