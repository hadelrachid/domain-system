<?php

namespace DomainSystem\Core\Plugin;

use DomainSystem\Core\Container\Container;
use Exception;

/**
 * OsRuntime (A Ponte de Execução)
 *
 * Utilizado pelos plugins na Fase 2 para pedir os recursos.
 * Aplica o Princípio do Menor Privilégio: se o plugin não pediu na Fase 1, o Runtime barra!
 */
class OsRuntime
{
    private Container $container;
    private OsConnector $connectorManifest;
    
    // (O LinkRegistry será injetado aqui futuramente para gerenciar as rotas de links)

    public function __construct(Container $container, OsConnector $connectorManifest)
    {
        $this->container = $container;
        $this->connectorManifest = $connectorManifest;
    }

    /**
     * Entrega a instância de um Link (Serviço) ao Plugin.
     */
    public function getLink(string $linkName)
    {
        // 🚨 O GUARDIÃO DE PERMISSÕES 🚨
        if (!in_array($linkName, $this->connectorManifest->getRequiredLinks())) {
            throw new Exception("Auditoria de Segurança (Bloqueio): O plugin tentou acessar o Link '{$linkName}', mas não o requisitou durante a Fase de Registro.");
        }

        // Simulação do LinkRegistry por enquanto (ele pedirá ao Container)
        // No futuro: return $this->linkRegistry->resolve($linkName);
        return null; 
    }

    /**
     * Permite ao plugin injetar visual/dado em um Slot que ele pediu acesso.
     */
    public function contributeTo(string $slotName, mixed $payload): void
    {
        // 🚨 O GUARDIÃO DE PERMISSÕES 🚨
        if (!in_array($slotName, $this->connectorManifest->getRequestedSlots())) {
            throw new Exception("Auditoria de Segurança (Bloqueio): O plugin tentou modificar o Slot visual '{$slotName}', mas não pediu permissão no registro.");
        }

        // No futuro: Injeta o $payload no sistema visual de Hooks/Eventos.
    }
}
