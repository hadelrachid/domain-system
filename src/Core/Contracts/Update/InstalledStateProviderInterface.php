<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts\Update;

use DomainSystem\Core\Update\Value\InstalledPlugin;

/**
 * Contrato que fornece informações sobre a versão instalada do núcleo e os plugins presentes.
 */
interface InstalledStateProviderInterface
{
    /**
     * Devolve a versão SemVer atualmente instalada do núcleo (ex.: `2.3.0`).
     */
    public function coreVersion(): string;

    /**
     * Devolve o mapa de plugins instalados no sistema indexado pelo slug.
     *
     * @return array<string, InstalledPlugin>
     */
    public function plugins(): array;
}
