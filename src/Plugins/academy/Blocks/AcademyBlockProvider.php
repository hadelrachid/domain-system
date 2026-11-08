<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Blocks;

use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Theme\Parts\Blocks\UserAuthBlock;
use DomainSystem\Core\Theme\Parts\Contracts\BlockProviderInterface;
use DomainSystem\Core\Theme\Parts\Contracts\BlockRegistryInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ElementStyleRendererInterface;
use DomainSystem\Core\Theme\Parts\Contracts\HtmlSanitizerInterface;
use DomainSystem\Core\Theme\Parts\Contracts\PublicUrlResolverInterface;

/**
 * Provedor de Blocos Semânticos do Plugin Academy para o Flex Builder (SOLID - OCP / DIP).
 *
 * Registra o bloco 'Área de Login / Aluno' (user_auth) de forma extensível.
 * Caso o plugin Academy esteja desativado, este provedor não é carregado,
 * mantendo o Flex Builder 100% desacoplado.
 */
class AcademyBlockProvider implements BlockProviderInterface
{
    private ?HtmlSanitizerInterface $htmlSanitizer;
    private ?PublicUrlResolverInterface $urlResolver;
    private ?ElementStyleRendererInterface $styleRenderer;
    private ?SessionManagerInterface $session;

    public function __construct(
        ?HtmlSanitizerInterface $htmlSanitizer = null,
        ?PublicUrlResolverInterface $urlResolver = null,
        ?ElementStyleRendererInterface $styleRenderer = null,
        ?SessionManagerInterface $session = null
    ) {
        $this->htmlSanitizer = $htmlSanitizer;
        $this->urlResolver = $urlResolver;
        $this->styleRenderer = $styleRenderer;
        $this->session = $session;
    }

    public function registerBlocks(BlockRegistryInterface $registry): void
    {
        if (!$registry->has('user_auth')) {
            $registry->register(new UserAuthBlock(
                $this->htmlSanitizer,
                $this->urlResolver,
                $this->styleRenderer,
                $this->session
            ));
        }
    }
}
