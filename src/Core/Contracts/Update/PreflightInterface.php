<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts\Update;

use DomainSystem\Core\Update\Value\PreflightReport;
use DomainSystem\Core\Update\Value\UpdateTarget;
use DomainSystem\Core\Update\Value\VerifiedPackage;

/**
 * Contrato Composite que agrega todas as checagens de pré-atualização (`PreflightCheckInterface`).
 */
interface PreflightInterface
{
    /**
     * Executa todas as checagens registradas e devolve o relatório consolidado.
     * Caso alguma checagem lance exceção inesperada, ela deve ser convertida em item `CheckLevel::Block`.
     */
    public function run(VerifiedPackage $package, UpdateTarget $target): PreflightReport;
}
