<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Services;

/**
 * Utilitário seguro para leitura e gravação atômica de configurações JSON com flock.
 * Previne corrupção de arquivos e race conditions em requisições paralelas.
 */
final class AtomicConfigStorage
{
    /**
     * Lê um arquivo JSON de forma segura com bloqueio compartilhado (LOCK_SH).
     *
     * @return array<string, mixed>
     */
    public static function read(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [];
        }

        $fp = @fopen($filePath, 'rb');
        if (!$fp) {
            return [];
        }

        @flock($fp, LOCK_SH);
        $content = stream_get_contents($fp);
        @flock($fp, LOCK_UN);
        @fclose($fp);

        if (!is_string($content) || trim($content) === '') {
            return [];
        }

        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Grava um arquivo JSON de forma atômica através de arquivo temporário e rename com LOCK_EX.
     *
     * @param array<string, mixed> $data
     */
    public static function write(string $filePath, array $data): bool
    {
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return false;
        }

        $tempFile = $filePath . '.' . bin2hex(random_bytes(8)) . '.tmp';
        $fp = @fopen($tempFile, 'wb');
        if (!$fp) {
            return false;
        }

        if (!@flock($fp, LOCK_EX)) {
            @fclose($fp);
            @unlink($tempFile);
            return false;
        }

        $written = @fwrite($fp, $json);
        @fflush($fp);
        @flock($fp, LOCK_UN);
        @fclose($fp);

        if ($written === false || $written < strlen($json)) {
            @unlink($tempFile);
            return false;
        }

        if (!@rename($tempFile, $filePath)) {
            if (@copy($tempFile, $filePath)) {
                @unlink($tempFile);
                return true;
            }
            @unlink($tempFile);
            return false;
        }

        return true;
    }

    /**
     * Executa uma transação de leitura, modificação e escrita atômica garantida por lock exclusivo.
     *
     * @param callable(array<string, mixed>): array<string, mixed> $modifier
     * @return array<string, mixed> O novo array resultante gravado.
     */
    public static function update(string $filePath, callable $modifier): array
    {
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $lockFile = $filePath . '.lock';
        $lockFp = @fopen($lockFile, 'c+');
        if ($lockFp) {
            @flock($lockFp, LOCK_EX);
        }

        try {
            $current = self::read($filePath);
            $updated = (array)$modifier($current);
            self::write($filePath, $updated);
            return $updated;
        } finally {
            if ($lockFp) {
                @flock($lockFp, LOCK_UN);
                @fclose($lockFp);
            }
        }
    }
}
