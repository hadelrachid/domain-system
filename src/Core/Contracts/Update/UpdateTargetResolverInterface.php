<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts\Update;

use DomainSystem\Core\Update\Value\UpdateTarget;

/**
 * Contrato de resolução do alvo de atualização do Núcleo (Modelo A).
 */
interface UpdateTargetResolverInterface
{
    /**
     * Devolve o alvo de atualização do núcleo do sistema, com repositório vindo da configuração local
     * e versão instalada obtida do estado instalado.
     */
    public function core(): UpdateTarget;
}
