<?php

namespace DomainSystem\Plugins\whatsapp\Contracts;

interface WhatsAppProviderInterface
{
    /**
     * Define as configurações necessárias para a API (Ex: token, instance, host).
     */
    public function setConfig(array $config): void;

    /**
     * Envia uma mensagem de texto para o número especificado.
     * Retorna um array com o resultado ('success' => bool, 'http_code' => int).
     */
    public function sendMessage(string $phone, string $message): array;
}
