<?php

namespace DomainSystem\Plugins\whatsapp\Contracts;

interface WhatsAppSettingsRepositoryInterface
{
    /**
     * Retorna as configurações armazenadas do WhatsApp.
     */
    public function getSettings(): array;

    /**
     * Salva as configurações de conexão (Ex: Instância e Token).
     */
    public function saveSettings(string $instance, string $token): void;
}
