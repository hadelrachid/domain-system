<?php

namespace DomainSystem\Core\Monitoring;

use DomainSystem\Core\Contracts\NotificationManagerInterface;

class NotificationManager implements NotificationManagerInterface
{
    private string $storageFile;

    public function __construct(string $basePath)
    {
        $this->storageFile = rtrim($basePath, '/\\') . '/temp/notifications.json';
    }

    public function push(string $message, string $type = 'info', ?string $source = null, array $context = []): void
    {
        $data = $this->getAll();
        
        $notification = [
            'id'        => uniqid('notif_'),
            'timestamp' => date('Y-m-d H:i:s'),
            'type'      => $type,
            'source'    => $source ?? 'Sistema',
            'message'   => $message,
            'context'   => $context,
            'read'      => false
        ];
        
        array_unshift($data, $notification); // Mais recentes primeiro
        
        // Mantém limite de 200 para não estourar o disco
        if (count($data) > 200) {
            $data = array_slice($data, 0, 200);
        }

        $this->save($data);
    }

    public function getUnread(): array
    {
        $all = $this->getAll();
        return array_filter($all, fn($n) => empty($n['read']));
    }

    public function markAsRead(): void
    {
        $all = $this->getAll();
        $updated = false;
        foreach ($all as &$n) {
            if (empty($n['read'])) {
                $n['read'] = true;
                $updated = true;
            }
        }
        if ($updated) {
            $this->save($all);
        }
    }

    public function getAll(): array
    {
        if (!file_exists($this->storageFile)) {
            return [];
        }
        $content = file_get_contents($this->storageFile);
        if (!$content) return [];
        return json_decode($content, true) ?: [];
    }

    public function clear(): void
    {
        if (file_exists($this->storageFile)) {
            unlink($this->storageFile);
        }
    }

    private function save(array $data): void
    {
        file_put_contents($this->storageFile, json_encode($data, JSON_PRETTY_PRINT));
    }
}
