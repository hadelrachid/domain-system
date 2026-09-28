<?php

namespace DomainSystem\Core\Plugin;

/**
 * OsConnector (A Ponte de Negociação)
 *
 * Utilizado pelos plugins na Fase 1 para declarar suas intenções ao SO.
 * O SO guarda estas informações para auditar e aprovar os recursos.
 */
class OsConnector
{
    private array $requiredLinks = [];
    private array $providedLinks = [];
    private array $requestedSlots = [];
    
    // Novo: Gatilhos/Eventos
    private array $listenedHooks = [];
    private array $providedHooks = [];

    /**
     * O Plugin avisa: "Eu PRECISO deste serviço de outro plugin/core para funcionar."
     * Ex: $os->requireLink('core.db'); // Banco de dados oficial do sistema
     */
    public function requireLink(string $linkName): self
    {
        $this->requiredLinks[] = $linkName;
        return $this;
    }

    /**
     * O Plugin avisa: "Eu FORNEÇO este serviço para o sistema."
     */
    public function provideLink(string $linkName, string $className): self
    {
        $this->providedLinks[$linkName] = $className;
        return $this;
    }

    /**
     * O Plugin avisa: "Eu QUERO renderizar algo nesta área visual."
     */
    public function requestSlot(string $slotName): self
    {
        $this->requestedSlots[] = $slotName;
        return $this;
    }

    /**
     * NOVO: O Plugin avisa: "Eu QUERO ser notificado quando X acontecer."
     * Ex: $os->listenHook('patient.created');
     */
    public function listenHook(string $hookName): self
    {
        $this->listenedHooks[] = $hookName;
        return $this;
    }

    /**
     * NOVO: O Plugin avisa: "Eu VOU DISPARAR este evento para os outros ouvirem."
     * Ex: $os->provideHook('appointment.canceled');
     */
    public function provideHook(string $hookName): self
    {
        $this->providedHooks[] = $hookName;
        return $this;
    }

    // -------------------------------------------------------------
    // Métodos internos que o KERNEL usará para ler o manifesto
    // -------------------------------------------------------------

    public function getRequiredLinks(): array
    {
        return $this->requiredLinks;
    }

    public function getProvidedLinks(): array
    {
        return $this->providedLinks;
    }

    public function getRequestedSlots(): array
    {
        return $this->requestedSlots;
    }

    public function getListenedHooks(): array
    {
        return $this->listenedHooks;
    }

    public function getProvidedHooks(): array
    {
        return $this->providedHooks;
    }
}
