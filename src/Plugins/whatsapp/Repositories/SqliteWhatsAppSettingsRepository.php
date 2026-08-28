<?php

namespace DomainSystem\Plugins\whatsapp\Repositories;

use DomainSystem\Plugins\whatsapp\Contracts\WhatsAppSettingsRepositoryInterface;
use DomainSystem\Plugins\Database\Connection;

class SqliteWhatsAppSettingsRepository implements WhatsAppSettingsRepositoryInterface
{
    private \PDO $pdo;

    public function __construct(Connection $db)
    {
        $this->pdo = $db->getPdo();
    }

    public function getSettings(): array
    {
        $settings = $this->pdo->query("SELECT key_name, key_value FROM settings WHERE key_name IN ('zapi_instance', 'zapi_token')")->fetchAll(\PDO::FETCH_KEY_PAIR);
        return [
            'instance' => $settings['zapi_instance'] ?? '',
            'token' => decrypt_string($settings['zapi_token'] ?? '')
        ];
    }

    public function saveSettings(string $instance, string $token): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO settings (key_name, key_value) VALUES (?, ?) ON CONFLICT(key_name) DO UPDATE SET key_value = excluded.key_value");
        $stmt->execute(['zapi_instance', $instance]);
        $stmt->execute(['zapi_token', encrypt_string($token)]);
    }
}
