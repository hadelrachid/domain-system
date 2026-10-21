<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Repositories;

use DomainSystem\Core\Theme\Parts\Contracts\ThemePartInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartRepositoryInterface;
use DomainSystem\Core\Theme\Parts\Models\ThemePart;

/**
 * Repositório de Persistência em JSON Estruturado de Partes de Tema (config/theme_parts/).
 */
class JsonThemePartRepository implements ThemePartRepositoryInterface
{
    private string $storageDir;

    public function __construct(?string $storageDir = null)
    {
        $this->storageDir = $storageDir ?? (defined('DOMAIN_SYSTEM_ROOT') ? DOMAIN_SYSTEM_ROOT . '/config/theme_parts' : __DIR__ . '/../../../../../config/theme_parts');
        $this->ensureDirectory();
    }

    private function ensureDirectory(): void
    {
        if (!is_dir($this->storageDir)) {
            @mkdir($this->storageDir, 0755, true);
        }
    }

    private function getFilePath(string $id): string
    {
        $safeId = preg_replace('/[^a-zA-Z0-9_-]/', '_', $id);
        return rtrim($this->storageDir, '/\\') . DIRECTORY_SEPARATOR . $safeId . '.json';
    }

    public function findByType(string $type): array
    {
        $all = $this->all();
        return array_values(array_filter($all, fn(ThemePartInterface $p) => $p->getType() === $type));
    }

    public function findById(string $id): ?ThemePartInterface
    {
        $path = $this->getFilePath($id);
        if (!file_exists($path)) {
            return null;
        }

        $content = @file_get_contents($path);
        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);
        if (!is_array($data)) {
            return null;
        }

        return ThemePart::fromArray($data);
    }

    public function save(ThemePartInterface $part): bool
    {
        $this->ensureDirectory();
        $path = $this->getFilePath($part->getId());
        $json = json_encode($part->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return @file_put_contents($path, $json, LOCK_EX) !== false;
    }

    public function delete(string $id): bool
    {
        $path = $this->getFilePath($id);
        if (file_exists($path)) {
            return @unlink($path);
        }

        return false;
    }

    public function all(): array
    {
        $this->ensureDirectory();
        $parts = [];
        $files = glob(rtrim($this->storageDir, '/\\') . DIRECTORY_SEPARATOR . '*.json');

        if (is_array($files)) {
            foreach ($files as $file) {
                $raw = @file_get_contents($file);
                if ($raw !== false) {
                    $data = json_decode($raw, true);
                    if (is_array($data) && isset($data['id'])) {
                        $parts[] = ThemePart::fromArray($data);
                    }
                }
            }
        }

        return $parts;
    }
}
