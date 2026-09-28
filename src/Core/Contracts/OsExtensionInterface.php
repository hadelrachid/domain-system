<?php

namespace DomainSystem\Core\Contracts;

/**
 * Contrato Oficial do Sistema Operacional Web (Domain-System OS).
 * Todo plugin ou tema DEVE assinar este contrato.
 */
interface OsExtensionInterface
{
    /**
     * Fase 1: NEGOCIAÇÃO (Manifesto)
     * A Extensão (Plugin/Tema) declara estritamente o que PRECISA e o que FORNECE.
     * Nenhuma lógica de negócio deve rodar aqui. O SO apenas lê as permissões.
     *
     * @param OsConnectorInterface $os O Conector de Negociação do SO
     */
    public function osRegister(OsConnectorInterface $os): void;

    /**
     * Fase 2: EXECUÇÃO (Runtime)
     * O SO entrega os recursos aprovados para a Extensão trabalhar.
     * Recebe acesso APENAS às áreas e links que requisitou na Fase 1.
     *
     * @param OsRuntimeInterface $runtime O Entregador de Recursos do SO
     */
    public function osBoot(OsRuntimeInterface $runtime): void;
}
