<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Blocks;

use DomainSystem\Core\Theme\Parts\Contracts\BlockInterface;
use DomainSystem\Core\Theme\Parts\Contracts\BlockRegistryInterface;

/**
 * Registro de Blocos Semânticos Nativos e Extensíveis.
 */
class BlockRegistry implements BlockRegistryInterface
{
    /** @var array<string, BlockInterface> */
    private array $blocks = [];

    public function register(BlockInterface $block): void
    {
        $this->blocks[$block->getType()] = $block;
    }

    public function get(string $type): ?BlockInterface
    {
        return $this->blocks[$type] ?? null;
    }

    public function all(): array
    {
        return $this->blocks;
    }

    public function allByCategory(): array
    {
        $grouped = [];
        foreach ($this->blocks as $block) {
            $cat = $block->getCategory();
            if (!isset($grouped[$cat])) {
                $grouped[$cat] = [];
            }
            $grouped[$cat][$block->getType()] = $block;
        }

        return $grouped;
    }
}
