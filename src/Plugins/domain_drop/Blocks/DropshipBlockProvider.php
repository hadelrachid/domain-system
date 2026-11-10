<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Blocks;

use DomainSystem\Core\Theme\Parts\Contracts\BlockProviderInterface;
use DomainSystem\Core\Theme\Parts\Contracts\BlockRegistryInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ElementStyleRendererInterface;
use DomainSystem\Core\Theme\Parts\Contracts\HtmlSanitizerInterface;
use DomainSystem\Core\Theme\Parts\Contracts\PublicUrlResolverInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipCatalogRepositoryInterface;

/**
 * Provedor de Blocos Semânticos do Plugin DomainDrop para o Flex Builder (SOLID - OCP / DIP).
 */
class DropshipBlockProvider implements BlockProviderInterface
{
    public function __construct(
        private readonly DropshipCatalogRepositoryInterface $catalogRepo,
        private readonly ?ElementStyleRendererInterface $styleRenderer = null,
        private readonly ?HtmlSanitizerInterface $htmlSanitizer = null,
        private readonly ?PublicUrlResolverInterface $urlResolver = null
    ) {
    }

    public function registerBlocks(BlockRegistryInterface $registry): void
    {
        if (!$registry->has('dropship_showcase')) {
            $registry->register(new DropshipShowcaseBlock(
                $this->catalogRepo,
                $this->styleRenderer,
                $this->htmlSanitizer,
                $this->urlResolver
            ));
        }
    }
}
