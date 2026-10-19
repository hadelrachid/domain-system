<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts\Update;

use DomainSystem\Core\Update\Enum\UpdateKind;
use DomainSystem\Core\Update\Value\UpdateCheckResult;
use DomainSystem\Core\Update\Value\UpdateState;
use DomainSystem\Core\Update\Value\UpdateTarget;

/**
 * Contrato base do orquestrador de atualização em etapas (máquina de estados).
 */
interface UpdaterInterface
{
    /**
     * Devolve o tipo de alvo gerenciado por este orquestrador.
     */
    public function kind(): UpdateKind;

    /**
     * Verifica se há atualização disponível para o alvo.
     */
    public function check(UpdateTarget $target, bool $force = false): UpdateCheckResult;

    /**
     * Inicia uma sessão de atualização, adquire o lock e devolve o estado inicial.
     *
     * @param array<string, mixed> $options
     */
    public function begin(UpdateTarget $target, array $options = []): UpdateState;

    /**
     * Executa exatamente uma etapa da máquina de estados e devolve o novo estado persistido.
     */
    public function advance(string $updateId): UpdateState;

    /**
     * Executa o rollback completo (código e banco, quando aplicável) para a atualização informada.
     */
    public function rollback(string $updateId): UpdateState;

    /**
     * Consulta o estado atual da atualização informada.
     */
    public function status(string $updateId): UpdateState;
}
