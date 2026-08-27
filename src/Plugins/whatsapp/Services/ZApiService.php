<?php

namespace DomainSystem\Plugins\whatsapp\Services;

use DomainSystem\Plugins\Database\Connection;
use Exception;

class ZApiService
{
    private Connection $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function getSettings(): array
    {
        $pdo = $this->db->getPdo();
        $settings = $pdo->query("SELECT key_name, key_value FROM settings WHERE key_name IN ('zapi_instance', 'zapi_token')")->fetchAll(\PDO::FETCH_KEY_PAIR);
        return [
            'instance' => $settings['zapi_instance'] ?? '',
            'token' => decrypt_string($settings['zapi_token'] ?? '')
        ];
    }

    public function sendMessage(string $phone, string $message): array
    {
        $settings = $this->getSettings();
        if (empty($settings['instance']) || empty($settings['token'])) {
            throw new Exception("Z-API não configurada. Defina a Instância e o Token no painel.");
        }

        // Z-API Endpoint format
        $url = "https://api.z-api.io/instances/{$settings['instance']}/token/{$settings['token']}/send-text";

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
