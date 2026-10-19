<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts\Update;

use DomainSystem\Core\Update\Exception\SchemaPlanException;
use DomainSystem\Core\Update\Value\SchemaPlan;
use DomainSystem\Core\Update\Value\UpdateManifest;

/**
 * Contrato de planejamento estático (dry-run) das migrações declaradas no manifest.
 *
 * Obrigações da implementação:
 * - Comparar as migrações do manifest com as registradas em `MigrationRegistryInterface`.
 * - Verificar que cada arquivo existe na quarentena e que seu SHA-256 confere com o manifest,
 *   sem executar nem dar `require` no arquivo PHP.
 * - Detectar migrações já executadas no banco que não existem no pacote (`blocked`, indício de downgrade).
 */
interface SchemaPlannerInterface
{
    /**
     * Monta o plano de migração de banco para o pacote extraído em `$extractedPath`.
     *
     * @throws SchemaPlanException Quando um arquivo de migration está ausente, diverge em hash ou tem formato inválido.
     */
    public function plan(UpdateManifest $manifest, string $extractedPath): SchemaPlan;
}
