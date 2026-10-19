<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts\Update;

use DomainSystem\Core\Update\Exception\ApplyException;
use DomainSystem\Core\Update\Exception\RollbackFailedException;
use DomainSystem\Core\Update\Value\ApplyResult;
use DomainSystem\Core\Update\Value\BackupRecord;
use DomainSystem\Core\Update\Value\UpdateTarget;
use DomainSystem\Core\Update\Value\VerifiedPackage;

/**
 * Contrato de aplicação em estágios dos arquivos do pacote verificado sobre a instalação.
 *
 * Obrigações da implementação:
 * - Escrever apenas arquivos contidos no manifest e dentro de `ProtectedPathsInterface::allowedRoots()`.
 * - Respeitar `protectedPaths()` e o modo `default_if_missing`.
 * - Aplicar os pontos de entrada (`public/index.php` e `bootstrap.php`) por último.
 */
interface UpdateApplierInterface
{
    /**
     * Aplica os arquivos e remoções do pacote verificado.
     *
     * @throws ApplyException Quando qualquer caminho viola a política ou falha na gravação/troca.
     */
    public function apply(VerifiedPackage $package, UpdateTarget $target, BackupRecord $backup): ApplyResult;

    /**
     * Reverte a aplicação restaurando integralmente o backup associado.
     *
     * @throws RollbackFailedException Quando a restauração falha.
     */
    public function revert(ApplyResult $result): void;
}
