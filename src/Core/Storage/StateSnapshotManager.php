<?php

declare(strict_types=1);

namespace DomainSystem\Core\Storage;

use DomainSystem\Core\Contracts\JsonStoreInterface;
use InvalidArgumentException;

/**
 * Gerenciador de Snapshots e Rollback 1-Clique do Estado do CockPit (Pilar 6 v2.4).
 *
 * Captura e restaura de forma atômica os arquivos vitais de estado:
 * - config/plugins.json
 * - config/disarmed.json
 * - database.sqlite (quando presente)
 */
class StateSnapshotManager
{
    private string $basePath;
    private string $snapshotsDir;
    private JsonStoreInterface $jsonStore;

    public function __construct(?string $basePath = null, JsonStoreInterface $jsonStore = new AtomicJsonStore())
    {
        $this->basePath = rtrim($basePath ?? (defined('DOMAIN_SYSTEM_ROOT') ? DOMAIN_SYSTEM_ROOT : dirname(__DIR__, 3)), '/\\');
        $this->snapshotsDir = $this->basePath . '/storage/snapshots';
        $this->jsonStore = $jsonStore;
    }

    /**
     * Cria um novo snapshot do estado atual do sistema.
     *
     * @return array{id: string, label: string, created_at: string, files: array<int, string>}
     */
    public function createSnapshot(string $label = 'Snapshot Manual'): array
    {
        if (!is_dir($this->snapshotsDir)) {
            @mkdir($this->snapshotsDir, 0755, true);
            @file_put_contents($this->snapshotsDir . '/.htaccess', "Deny from all\n", LOCK_EX);
        }

        $id = 'snap_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3));
        $targetDir = $this->snapshotsDir . DIRECTORY_SEPARATOR . $id;
        @mkdir($targetDir, 0755, true);

        $copiedFiles = [];
        $candidates = [
            'plugins.json'  => $this->basePath . '/config/plugins.json',
            'disarmed.json' => $this->basePath . '/config/disarmed.json',
        ];

        foreach ($candidates as $name => $srcPath) {
            if (is_file($srcPath)) {
                if (@copy($srcPath, $targetDir . DIRECTORY_SEPARATOR . $name)) {
                    $copiedFiles[] = $name;
                }
            }
        }

        $meta = [
            'id'         => $id,
            'label'      => trim($label) !== '' ? trim($label) : 'Snapshot Manual',
            'created_at' => date('Y-m-d H:i:s'),
            'files'      => $copiedFiles,
        ];

        $this->jsonStore->write($targetDir . DIRECTORY_SEPARATOR . 'metadata.json', $meta);
        return $meta;
    }

    /**
     * Lista todos os snapshots disponíveis ordenados do mais recente para o mais antigo.
     *
     * @return array<int, array{id: string, label: string, created_at: string, files: array<int, string>}>
     */
    public function listSnapshots(): array
    {
        if (!is_dir($this->snapshotsDir)) {
            return [];
        }

        $entries = scandir($this->snapshotsDir);
        if ($entries === false) {
            return [];
        }

        $snapshots = [];
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $metaFile = $this->snapshotsDir . DIRECTORY_SEPARATOR . $entry . DIRECTORY_SEPARATOR . 'metadata.json';
            if (is_file($metaFile)) {
                $meta = $this->jsonStore->read($metaFile, []);
                if (!empty($meta['id'])) {
                    $snapshots[] = $meta;
                }
            }
        }

        usort($snapshots, static fn(array $a, array $b): int => strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? '')));
        return $snapshots;
    }

    /**
     * Restaura um snapshot pelo seu ID.
     */
    public function restoreSnapshot(string $snapshotId): bool
    {
        $cleanId = preg_replace('/[^a-zA-Z0-9_-]/', '', $snapshotId) ?? '';
        if ($cleanId === '' || $cleanId !== $snapshotId) {
            throw new InvalidArgumentException("ID de snapshot inválido.");
        }

        $sourceDir = $this->snapshotsDir . DIRECTORY_SEPARATOR . $cleanId;
        if (!is_dir($sourceDir) || !is_file($sourceDir . DIRECTORY_SEPARATOR . 'metadata.json')) {
            throw new InvalidArgumentException("Snapshot não encontrado: {$cleanId}");
        }

        $restoredAny = false;
        $targets = [
            'plugins.json'    => $this->basePath . '/config/plugins.json',
            'disarmed.json'   => $this->basePath . '/config/disarmed.json',
            'database.sqlite' => $this->basePath . '/database.sqlite',
        ];

        foreach ($targets as $name => $destPath) {
            $snapFile = $sourceDir . DIRECTORY_SEPARATOR . $name;
            if (is_file($snapFile)) {
                $destDir = dirname($destPath);
                if (!is_dir($destDir)) {
                    @mkdir($destDir, 0755, true);
                }
                $tmpDest = $destPath . '.restore.' . bin2hex(random_bytes(3));
                if (@copy($snapFile, $tmpDest)) {
                    if (!@rename($tmpDest, $destPath)) {
                        @copy($tmpDest, $destPath);
                        @unlink($tmpDest);
                    }
                    $restoredAny = true;
                }
            }
        }

        return $restoredAny;
    }
}
