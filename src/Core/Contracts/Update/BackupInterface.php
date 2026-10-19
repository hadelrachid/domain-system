<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts\Update;

use DomainSystem\Core\Update\Exception\RollbackFailedException;
use DomainSystem\Core\Update\Value\BackupRecord;
use DomainSystem\Core\Update\Value\BackupScope;

/**
 * Contrato de backup e restauração de arquivos de código e configuração.
 */
interface BackupInterface
{
    /**
     * Cria um pacote de backup do código/configuração para o escopo informado.
     */
    public function create(BackupScope $scope, string $label): BackupRecord;

    /**
     * Restaura os arquivos a partir do registro de backup informado.
     *
     * @throws RollbackFailedException Quando a restauração falha parcial ou totalmente.
     */
    public function restore(BackupRecord $record): void;

    /**
     * Lista os backups disponíveis em ordem decrescente de criação.
     *
     * @return list<BackupRecord>
     */
    public function list(?BackupScope $scope = null): array;

    /**
     * Remove os backups mais antigos do escopo informado, mantendo apenas os `$keep` mais recentes.
     * Devolve a quantidade de backups removidos.
     */
    public function prune(BackupScope $scope, int $keep): int;
}
