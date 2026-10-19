<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts\Update;

use DomainSystem\Core\Update\Value\SchemaVerification;
use DomainSystem\Core\Update\Value\UpdateManifest;

/**
 * Contrato de verificação pós-migração da estrutura efetiva do banco de dados.
 *
 * Confere se todas as tabelas e colunas declaradas em `UpdateManifest::expects` existem
 * via `SchemaProbeInterface` e se todas as migrações do manifest constam como registradas.
 */
interface SchemaVerifierInterface
{
    /**
     * Verifica se o schema atual satisfaz integralmente o manifest.
     */
    public function verify(UpdateManifest $manifest): SchemaVerification;
}
