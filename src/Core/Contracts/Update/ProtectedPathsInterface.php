<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts\Update;

use DomainSystem\Core\Update\Enum\UpdateKind;

/**
 * Contrato da política local de proteção de caminhos e raízes permitidas.
 *
 * A política reside exclusivamente no sistema instalado e nunca pode ser sobrescrita pelo pacote.
 */
interface ProtectedPathsInterface
{
    /**
     * Devolve a lista de caminhos relativos protegidos que jamais podem ser sobrescritos ou removidos.
     *
     * @return list<string>
     */
    public function protectedPaths(UpdateKind $kind, string $slug): array;

    /**
     * Devolve as únicas raízes ou arquivos da raiz onde o pacote tem permissão de escrita/remoção.
     *
     * @return list<string>
     */
    public function allowedRoots(UpdateKind $kind, string $slug): array;
}
