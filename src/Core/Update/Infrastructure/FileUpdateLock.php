<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Infrastructure;

use Closure;
use DomainSystem\Core\Contracts\Update\UpdateLockInterface;
use DomainSystem\Core\Update\Enum\UpdateKind;
use DomainSystem\Core\Update\Value\UpdateTarget;

final class FileUpdateLock implements UpdateLockInterface
{
    private string $stateDir;

    /**
     * @var Closure(): int
     */
    private Closure $clock;

    /**
     * @param (Closure(): int)|null $clock
     */
    public function __construct(string $rootPath, ?Closure $clock = null)
    {
        $normalizedRoot = rtrim(str_replace('\\', '/', $rootPath), '/');
        $this->stateDir = $normalizedRoot . '/storage/state';
        $this->clock = $clock ?? static fn(): int => time();
    }

    public function acquire(UpdateTarget $target, int $ttlSeconds): bool
    {
        if (!is_dir($this->stateDir) && !@mkdir($this->stateDir, 0755, true) && !is_dir($this->stateDir)) {
            return false;
        }

        $lockFile = $this->resolveLockPath($target);
        $fp = @fopen($lockFile, 'c+b');
        if ($fp === false) {
            return false;
        }

        if (!@flock($fp, LOCK_EX | LOCK_NB)) {
            @fclose($fp);
            return false;
        }

        try {
            $now = ($this->clock)();
            rewind($fp);
            $raw = (string)stream_get_contents($fp);

            if (trim($raw) !== '') {
                $existing = json_decode($raw, true);
                if (is_array($existing)) {
                    $expiresAt = (int)($existing['expires_at'] ?? 0);
                    if ($expiresAt > $now) {
                        return false;
                    }
                }
            }

            $payload = json_encode([
                'target'      => $target->slug,
                'acquired_at' => $now,
                'expires_at'  => $now + max(1, $ttlSeconds),
            ], JSON_UNESCAPED_SLASHES);

            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, (string)$payload);
            fflush($fp);

            return true;
        } finally {
            @flock($fp, LOCK_UN);
            @fclose($fp);
        }
    }

    public function release(UpdateTarget $target): void
    {
        $lockFile = $this->resolveLockPath($target);
        if (!is_file($lockFile)) {
            return;
        }

        $fp = @fopen($lockFile, 'c+b');
        if ($fp !== false) {
            if (@flock($fp, LOCK_EX | LOCK_NB)) {
                ftruncate($fp, 0);
                fflush($fp);
                @flock($fp, LOCK_UN);
            }
            @fclose($fp);
        }

        @unlink($lockFile);
    }

    public function resolveLockPath(UpdateTarget $target): string
    {
        if ($target->kind === UpdateKind::Core) {
            return $this->stateDir . '/update-core.lock';
        }

        $safeSlug = preg_replace('/[^a-zA-Z0-9_-]/', '_', $target->slug) ?: 'plugin';
        return $this->stateDir . '/update-plugin-' . $safeSlug . '.lock';
    }
}
