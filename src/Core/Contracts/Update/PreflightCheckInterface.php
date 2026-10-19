<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts\Update;

use DomainSystem\Core\Update\Value\PreflightCheck;
use DomainSystem\Core\Update\Value\UpdateTarget;
use DomainSystem\Core\Update\Value\VerifiedPackage;

/**
 * Contrato Strategy para uma verificação individual de pré-checagem antes de aplicar uma atualização.
 */
interface PreflightCheckInterface
{
    /**
     * Identificador único desta checagem (ex.: `php_version`, `disk_space`).
     */
    public function id(): string;

    /**
     * Executa a checagem sobre o pacote verificado e devolve um ou mais itens de relatório.
     *
     * @return list<PreflightCheck>
     */
    public function run(VerifiedPackage $package, UpdateTarget $target): array;
}
