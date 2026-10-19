<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts\Update;

use DomainSystem\Core\Update\Exception\RollbackFailedException;
use DomainSystem\Core\Update\Value\DumpResult;

/**
 * Contrato de dump e restauração de banco de dados agnóstico ao driver para o Ring 0.
 *
 * A implementação concreta (SQLite ou MySQL) reside na camada de banco de dados.
 */
interface DatabaseBackupInterface
{
    /**
     * Indica se o banco atual suporta dump automático (ex.: falso caso um banco MySQL ultrapasse o limite seguro).
     */
    public function isSupported(): bool;

    /**
     * Gera o dump do banco de dados no arquivo de destino informado.
     */
    public function dump(string $destinationFile): DumpResult;

    /**
     * Restaura o banco de dados a partir do arquivo de dump informado.
     *
     * @throws RollbackFailedException Quando a restauração do dump falha.
     */
    public function restore(string $dumpFile): void;
}
