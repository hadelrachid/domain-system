<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Contracts;

/**
 * Contrato de Bloco Semântico para o Motor Visual (Padrão Elementor / Gutenberg / Academy).
 * Compõe RenderableBlockInterface e SchemaAwareBlockInterface respeitando o ISP.
 */
interface BlockInterface extends RenderableBlockInterface, SchemaAwareBlockInterface
{
}
