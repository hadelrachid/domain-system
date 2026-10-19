<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts\Update;

use DomainSystem\Core\Update\Value\UpdateCheckResult;
use DomainSystem\Core\Update\Value\UpdateTarget;

/**
 * Contrato de verificação de novas versões com suporte a cache/TTL.
 *
 * Obrigações da implementação:
 * - Respeitar o TTL persistido no estado, exceto quando `$force === true`.
 * - Nunca propagar exceções de rede ou parse: falhas convertem-se em `UpdateStatus::Unknown` com o motivo em `reasons`.
 */
interface UpdateCheckerInterface
{
    /**
     * Verifica se há uma versão superior disponível para o alvo informado.
     */
    public function check(UpdateTarget $target, bool $force = false): UpdateCheckResult;
}
