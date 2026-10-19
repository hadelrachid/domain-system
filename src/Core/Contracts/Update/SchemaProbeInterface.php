<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts\Update;

/**
 * Contrato agnóstico de sondagem de tabelas e colunas do banco de dados.
 */
interface SchemaProbeInterface
{
    /**
     * Verifica se a tabela informada existe no banco de dados ativo.
     */
    public function hasTable(string $table): bool;

    /**
     * Verifica se a coluna informada existe na tabela especificada.
     */
    public function hasColumn(string $table, string $column): bool;
}
