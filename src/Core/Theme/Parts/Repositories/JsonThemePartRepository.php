<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Repositories;

use DomainSystem\Core\Theme\Parts\Contracts\ThemePartInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartRepositoryInterface;
use DomainSystem\Core\Theme\Parts\Models\ThemePart;
use InvalidArgumentException;

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

    /**
     * Valida estritamente o ID contra Path Traversal e caracteres inválidos.
     */
    private function validateId(string $id): string
    {
        $trimmed = trim($id);
        if (
            $trimmed === '' ||
            str_contains($trimmed, '..') ||
            str_contains($trimmed, '/') ||
            str_contains($trimmed, '\\') ||
            !preg_match('/^[a-zA-Z0-9_.-]{1,128}$/', $trimmed)
        ) {
            throw new InvalidArgumentException("ID de parte de tema inválido ou inseguro: '{$id}'.");
        }

        // Mantém compatibilidade com nomes de arquivos onde '.' vira '_'
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $trimmed) ?? $trimmed;
    }

    private function getFilePath(string $id): string
    {
        $safeId = $this->validateId($id);
        return rtrim($this->storageDir, '/\\') . DIRECTORY_SEPARATOR . $safeId . '.json';
    }

    public function findByType(string $type): array
    {
        $all = $this->all();
        return array_values(array_filter($all, fn(ThemePartInterface $p) => $p->getType() === $type));
    }

    public function findById(string $id): ?ThemePartInterface
    {
        try {
            $path = $this->getFilePath($id);
        } catch (InvalidArgumentException) {
            return null;
        }

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
        if ($json === false) {
            return false;
        }

        $tmpPath = $path . '.' . uniqid('tmp_', true) . '.tmp';
        if (@file_put_contents($tmpPath, $json, LOCK_EX) === false) {
            return false;
        }

        if (!@rename($tmpPath, $path)) {
            $copied = @copy($tmpPath, $path);
            @unlink($tmpPath);
            return $copied;
        }

        return true;
    }

    public function delete(string $id): bool
    {
        try {
            $path = $this->getFilePath($id);
        } catch (InvalidArgumentException) {
            return false;
        }

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
