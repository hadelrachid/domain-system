<?php

namespace DomainSystem\Plugins\whatsapp\Services;

use DomainSystem\Plugins\whatsapp\Contracts\WhatsAppProviderInterface;
use Exception;

class ZApiService implements WhatsAppProviderInterface
{
    private array $config = [];

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    public function sendMessage(string $phone, string $message): array
    {
        if (empty($this->config['instance']) || empty($this->config['token'])) {
            throw new Exception("Z-API não configurada. Defina a Instância e o Token no painel.");
        }

        // Z-API Endpoint format
        $url = "https://api.z-api.io/instances/{$this->config['instance']}/token/{$this->config['token']}/send-text";

        // Remove non-numeric chars from phone
        $phone = preg_replace('/[^0-9]/', '', $phone);

        $payload = [
            'phone' => $phone,
            'message' => $message
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("Erro cURL ao enviar WhatsApp: " . $error);
        }

        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'http_code' => $httpCode,
            'response' => json_decode($response, true) ?: $response
        ];
    }
}
