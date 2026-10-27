<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Blocks\Traits;

use DomainSystem\Core\Theme\Parts\Contracts\ThemePartInterface;

/**
 * Trait padrão para blocos que podem ser utilizados universalmente (Header, Footer e Conteúdo Central).
 */
trait AllowedPartTypesTrait
{
    /**
     * Retorna os tipos de partes estruturais permitidas.
     * @return array<int, string>
     */
    public function getAllowedPartTypes(): array
    {
        return [
            ThemePartInterface::TYPE_HEADER,
            ThemePartInterface::TYPE_FOOTER,
            ThemePartInterface::TYPE_SECTION,
        ];
    }
}
