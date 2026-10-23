<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Grid;

use DomainSystem\Core\Theme\Parts\Contracts\Layout\LayoutPresetInterface;
use DomainSystem\Core\Theme\Parts\Contracts\Layout\LayoutPresetRegistryInterface;

/**
 * Registro dinâmico de Presets de Grade e Layout (SOLID - OCP).
 */
class LayoutPresetRegistry implements LayoutPresetRegistryInterface
{
    /** @var array<string, LayoutPresetInterface> */
    private array $presets = [];

    public function register(LayoutPresetInterface $preset): void
    {
        $this->presets[$preset->getId()] = $preset;
    }

    public function get(string $id): ?LayoutPresetInterface
    {
        return $this->presets[$id] ?? null;
    }

    public function has(string $id): bool
    {
        return isset($this->presets[$id]);
    }

    public function all(): array
    {
        return $this->presets;
    }

    public function allAsArray(): array
    {
        $result = [];
        foreach ($this->presets as $id => $preset) {
            $result[$id] = $preset->toArray();
        }
        return $result;
    }

    public function allByCategory(): array
    {
        $grouped = [];
        foreach ($this->presets as $preset) {
            $cat = $preset->getCategory();
            if (!isset($grouped[$cat])) {
                $grouped[$cat] = [];
            }
            $grouped[$cat][] = $preset;
        }
        return $grouped;
    }
}
