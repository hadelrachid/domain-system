<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts\Update;

use DomainSystem\Core\Update\Value\UpdateTarget;

/**
 * Contrato de bloqueio de concorrência (lock não bloqueante com TTL) para impedir atualizações simultâneas.
 */
interface UpdateLockInterface
{
    /**
     * Tenta adquirir o lock para o alvo informado com o TTL especificado em segundos.
     * Não bloqueia: devolve `false` se o lock já estiver ocupado e dentro da validade.
     */
    public function acquire(UpdateTarget $target, int $ttlSeconds): bool;

    /**
     * Libera imediatamente o lock associado ao alvo informado.
     */
    public function release(UpdateTarget $target): void;
}
