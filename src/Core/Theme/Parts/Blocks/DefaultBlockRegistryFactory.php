<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Blocks;

use DomainSystem\Core\Theme\Parts\Contracts\BlockProviderInterface;
use DomainSystem\Core\Theme\Parts\Contracts\BlockRegistryInterface;
use DomainSystem\Core\Theme\Parts\Contracts\MenuProviderInterface;
use DomainSystem\Core\Theme\Parts\Contracts\PostProviderInterface;
use DomainSystem\Core\Theme\ShortcodeManager;

/**
 * Fábrica e Registrador dos Blocos Semânticos Nativos e Extensíveis do Motor Visual (SOLID - OCP).
 */
class DefaultBlockRegistryFactory
{
    /**
     * @param array<int, BlockProviderInterface> $extraProviders
     */
    public static function create(
        ?MenuProviderInterface $menuProvider = null,
        ?ShortcodeManager $shortcodeManager = null,
        ?PostProviderInterface $postProvider = null,
        array $extraProviders = []
    ): BlockRegistryInterface {
        $registry = new BlockRegistry();

        $coreProvider = new CoreBlockProvider($menuProvider, $shortcodeManager, $postProvider);
        $coreProvider->registerBlocks($registry);

        foreach ($extraProviders as $provider) {
            if ($provider instanceof BlockProviderInterface) {
                $provider->registerBlocks($registry);
            }
        }

        return $registry;
    }
}
