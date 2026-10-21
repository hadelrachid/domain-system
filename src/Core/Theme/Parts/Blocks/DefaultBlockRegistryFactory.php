<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Blocks;

use DomainSystem\Core\Theme\Parts\Contracts\BlockRegistryInterface;
use DomainSystem\Core\Theme\Parts\Contracts\MenuProviderInterface;
use DomainSystem\Core\Theme\ShortcodeManager;

/**
 * Fábrica e Registrador dos 12 Blocos Semânticos Nativos do Motor Visual.
 */
class DefaultBlockRegistryFactory
{
    public static function create(
        ?MenuProviderInterface $menuProvider = null,
        ?ShortcodeManager $shortcodeManager = null
    ): BlockRegistryInterface {
        $registry = new BlockRegistry();

        $registry->register(new HeadingBlock());
        $registry->register(new ParagraphBlock());
        $registry->register(new ImageBlock());
        $registry->register(new VideoBlock());
        $registry->register(new LogoBlock());
        $registry->register(new CtaButtonBlock());
        $registry->register(new DividerBlock());
        $registry->register(new ShortcodeBlock($shortcodeManager));
        $registry->register(new CustomCodeBlock());
        $registry->register(new SocialIconsBlock());
        $registry->register(new CopyrightBlock());
        $registry->register(new NavMenuBlock($menuProvider));

        return $registry;
    }
}
