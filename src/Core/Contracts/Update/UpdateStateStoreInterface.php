<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts\Update;

use DomainSystem\Core\Update\Value\UpdateState;
use DomainSystem\Core\Update\Value\UpdateTarget;

/**
 * Contrato de persistência atômica do estado da máquina de atualização entre requisições.
 */
interface UpdateStateStoreInterface
{
    /**
     * Carrega o estado atual para o alvo informado; caso não exista arquivo salvo, devolve estado `Idle`.
     */
    public function load(UpdateTarget $target): UpdateState;

    /**
     * Persiste atomicamente o estado da atualização.
     */
    public function save(UpdateState $state): void;
}
