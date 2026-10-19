<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts\Update;

use DomainSystem\Core\Update\Value\HealthReport;
use DomainSystem\Core\Update\Value\UpdateTarget;

/**
 * Contrato de verificação de saúde pós-atualização do sistema.
 *
 * Emenda 5: executada em uma requisição HTTP nova (com o código novo carregado) antes da conclusão.
 */
interface HealthCheckInterface
{
    /**
     * Executa as verificações vitais do sistema para o alvo atualizado e devolve o relatório.
     */
    public function run(UpdateTarget $target): HealthReport;
}
