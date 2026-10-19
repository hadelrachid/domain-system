<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts;

/**
 * Contrato agnóstico de diagnóstico e auto-reparo de integridade de módulos/plugins.
 *
 * O Sistema Operacional (Ring 0) consome apenas este contrato via Injeção de Dependências,
 * sem conhecer detalhes de persistência, drivers ou bancos de dados.
 */
interface SchemaDoctorInterface
{
    /**
     * Inspeciona o status de integridade/migrações dos plugins.
     *
     * @return array<string, array{
     *     plugin: string,
     *     total: int,
     *     executed: array<int, string>,
     *     pending: array<int, string>,
     *     status: string
     * }>
     */
    public function inspectStatus(): array;

    /**
     * Executa o auto-reparo (Auto-Heal) das estruturas pendentes.
     *
     * @return array{executed: array<string, array<int, string>>, errors: array<string, array<int, string>>}
     */
    public function healAll(): array;
}
