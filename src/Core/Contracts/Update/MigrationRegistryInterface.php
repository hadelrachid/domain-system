<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts\Update;

/**
 * Contrato somente leitura para consulta de migrações já executadas no banco de dados.
 *
 * Obrigações da implementação:
 * - Não criar tabelas nem produzir efeitos colaterais no banco.
 * - Caso a tabela `kernel_migrations` ainda não exista, devolver `[]`.
 */
interface MigrationRegistryInterface
{
    /**
     * Devolve a lista de nomes de migrações já executadas no escopo informado (`__core__` ou slug de plugin).
     *
     * @return list<string>
     */
    public function executed(string $scope): array;
}
