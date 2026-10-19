<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts\Update;

use DomainSystem\Core\Update\Value\SchemaPlan;
use DomainSystem\Core\Update\Value\SchemaResult;

/**
 * Contrato de execução de migrações de schema durante a atualização.
 *
 * Obrigações da implementação:
 * - Executar somente para frente na ordem definida pelo `SchemaPlan`.
 * - Interromper imediatamente na primeira falha e devolver o nome da migration que falhou em `SchemaResult::failed`.
 */
interface SchemaMigratorInterface
{
    /**
     * Executa as migrações pendentes descritas no plano.
     */
    public function migrate(SchemaPlan $plan): SchemaResult;
}
