<?php

declare(strict_types=1);

namespace DomainSystem\Core\Storage;

use DomainSystem\Core\Contracts\JsonStoreInterface;

/**
 * Implementação atômica de leitura/gravação JSON com proteção contra concorrência (Pilar 1 v2.4).
 *
 * Previne truncamento e corrupção de estado em requisições simultâneas usando:
 * - Leitura com lock compartilhado (LOCK_SH)
 * - Escrita atômica em arquivo temporário + LOCK_EX + rename()
 * - Mutação transacional via arquivo de lock dedicado (.lock)
 */
class AtomicJsonStore implements JsonStoreInterface
{
    public function read(string $filePath, array $default = []): array
    {
        if (!is_file($filePath)) {
            return $default;
        }

        $fp = @fopen($filePath, 'rb');
        if ($fp === false) {
            return $default;
        }

        $content = '';
        if (@flock($fp, LOCK_SH)) {
            $content = (string)stream_get_contents($fp);
            @flock($fp, LOCK_UN);
        } else {
            $content = (string)stream_get_contents($fp);
        }
        @fclose($fp);

        if (trim($content) === '') {
            return $default;
        }

        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : $default;
    }

    public function write(string $filePath, array $data): bool
    {
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return false;
        }

        $tmpFile = $filePath . '.tmp.' . bin2hex(random_bytes(4));
        if (@file_put_contents($tmpFile, $json, LOCK_EX) === false) {
            return false;
        }

        if (!@rename($tmpFile, $filePath)) {
            $ok = @file_put_contents($filePath, $json, LOCK_EX) !== false;
            @unlink($tmpFile);
            return $ok;
        }

        return true;
    }

    public function update(string $filePath, callable $mutator, array $default = []): array
    {
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $lockFile = $filePath . '.lock';
        $lockFp = @fopen($lockFile, 'cb');

        if ($lockFp !== false && @flock($lockFp, LOCK_EX)) {
            try {
                $current = $this->read($filePath, $default);
                $updated = $mutator($current);
                if (!is_array($updated)) {
                    $updated = $default;
                }
                $this->write($filePath, $updated);
                return $updated;
            } finally {
                @flock($lockFp, LOCK_UN);
                @fclose($lockFp);
            }
        }

        if ($lockFp !== false) {
            @fclose($lockFp);
        }

        $current = $this->read($filePath, $default);
        $updated = $mutator($current);
        if (!is_array($updated)) {
            $updated = $default;
        }
        $this->write($filePath, $updated);
        return $updated;
    }
}
