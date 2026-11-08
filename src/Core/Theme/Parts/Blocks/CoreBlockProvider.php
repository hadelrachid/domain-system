<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Blocks;

use DomainSystem\Core\Theme\Parts\Contracts\BlockProviderInterface;
use DomainSystem\Core\Theme\Parts\Contracts\BlockRegistryInterface;
use DomainSystem\Core\Theme\Parts\Contracts\HtmlSanitizerInterface;
use DomainSystem\Core\Theme\Parts\Contracts\MenuProviderInterface;
use DomainSystem\Core\Theme\Parts\Contracts\PostProviderInterface;
use DomainSystem\Core\Theme\Parts\Contracts\PublicUrlResolverInterface;
use DomainSystem\Core\Theme\ShortcodeManager;

/**
 * Provedor dos 14 Blocos Semânticos Nativos do Kernel (SOLID - OCP / SRP).
 */
class CoreBlockProvider implements BlockProviderInterface
{
    private ?MenuProviderInterface $menuProvider;
    private ?ShortcodeManager $shortcodeManager;
    private ?PostProviderInterface $postProvider;
    private ?HtmlSanitizerInterface $htmlSanitizer;
    private ?PublicUrlResolverInterface $urlResolver;

    public function __construct(
        ?MenuProviderInterface $menuProvider = null,
        ?ShortcodeManager $shortcodeManager = null,
        ?PostProviderInterface $postProvider = null,
        ?HtmlSanitizerInterface $htmlSanitizer = null,
        ?PublicUrlResolverInterface $urlResolver = null
    ) {
        $this->menuProvider = $menuProvider;
        $this->shortcodeManager = $shortcodeManager;
        $this->postProvider = $postProvider;
        $this->htmlSanitizer = $htmlSanitizer;
        $this->urlResolver = $urlResolver;
    }

    public function registerBlocks(BlockRegistryInterface $registry): void
    {
        $registry->register(new HeadingBlock());
        $registry->register(new ParagraphBlock($this->htmlSanitizer));
        $registry->register(new ImageBlock());
        $registry->register(new VideoBlock());
        $registry->register(new LogoBlock($this->htmlSanitizer, $this->urlResolver));
        $registry->register(new ButtonBlock());
        $registry->register(new CtaButtonBlock());
        $registry->register(new DividerBlock());
        $registry->register(new ShortcodeBlock($this->shortcodeManager));
        $registry->register(new CustomCodeBlock());
        $registry->register(new SocialIconsBlock());
        $registry->register(new CopyrightBlock($this->urlResolver));
        $registry->register(new NavMenuBlock($this->menuProvider));
        $registry->register(new PostCardsGridBlock($this->postProvider));
    }
}
