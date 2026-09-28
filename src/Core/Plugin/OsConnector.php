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

    /**
     * O Plugin avisa: "Eu PRECISO deste serviço de outro plugin/core para funcionar."
     * Ex: $os->requireLink('auth.current_user');
     */
    public function requireLink(string $linkName): self
    {
        $this->requiredLinks[] = $linkName;
        return $this;
    }

    /**
     * O Plugin avisa: "Eu FORNEÇO este serviço para o sistema."
     * Ex: $os->provideLink('sms.sender', TwilioSmsSender::class);
     */
    public function provideLink(string $linkName, string $className): self
    {
        $this->providedLinks[$linkName] = $className;
        return $this;
    }

    /**
     * O Plugin avisa: "Eu QUERO renderizar algo ou escutar eventos nesta área."
     * Ex: $os->requestSlot('admin.header'); // Para colocar um relógio
     * Ex: $os->requestSlot('admin.menu'); // Para colocar um link no menu lateral
     */
    public function requestSlot(string $slotName): self
    {
        $this->requestedSlots[] = $slotName;
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
}
