<?php

declare(strict_types=1);

namespace DomainSystem\SystemApps\auth\Services;

use DateTimeImmutable;
use DomainSystem\SystemApps\auth\Contracts\LoginThrottleInterface;
use PDO;

class PdoLoginThrottle implements LoginThrottleInterface
{
    private PDO $pdo;
    private int $maxAttempts;
    private int $lockoutMinutes;
    private bool $tableEnsured = false;

    public function __construct(PDO $pdo, int $maxAttempts = 5, int $lockoutMinutes = 5)
    {
        $this->pdo = $pdo;
        $this->maxAttempts = max(1, $maxAttempts);
        $this->lockoutMinutes = max(1, $lockoutMinutes);
    }

    public function ensureTable(): void
    {
        if ($this->tableEnsured) {
            return;
        }

        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS auth_login_attempts (
                    `key` VARCHAR(190) PRIMARY KEY,
                    `attempts` INTEGER NOT NULL DEFAULT 0,
                    `locked_until` DATETIME NULL,
                    `updated_at` DATETIME NOT NULL
                )
            ");
        } else {
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS auth_login_attempts (
                    `key` VARCHAR(190) NOT NULL PRIMARY KEY,
                    `attempts` INT NOT NULL DEFAULT 0,
                    `locked_until` DATETIME NULL,
                    `updated_at` DATETIME NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        $this->tableEnsured = true;
    }

    public function tooManyAttempts(string $key): bool
    {
        $this->ensureTable();
        $now = new DateTimeImmutable();

        $stmt = $this->pdo->prepare("SELECT attempts, locked_until FROM auth_login_attempts WHERE `key` = ? LIMIT 1");
        $stmt->execute([$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return false;
        }

        if (!empty($row['locked_until'])) {
            $lockedUntil = new DateTimeImmutable((string)$row['locked_until']);
            if ($lockedUntil > $now) {
                return true;
            }
            return false;
        }

        return (int)$row['attempts'] >= $this->maxAttempts;
    }

    public function hit(string $key): void
    {
        $this->ensureTable();
        $now = new DateTimeImmutable();
        $nowStr = $now->format('Y-m-d H:i:s');

        $stmt = $this->pdo->prepare("SELECT attempts FROM auth_login_attempts WHERE `key` = ? LIMIT 1");
        $stmt->execute([$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $attempts = 1;
            $lockedUntil = null;
            $ins = $this->pdo->prepare("INSERT INTO auth_login_attempts (`key`, attempts, locked_until, updated_at) VALUES (?, ?, ?, ?)");
            $ins->execute([$key, $attempts, $lockedUntil, $nowStr]);
        } else {
            $attempts = (int)$row['attempts'] + 1;
            $lockedUntil = null;
            if ($attempts >= $this->maxAttempts) {
                $multiplier = max(1, $attempts - $this->maxAttempts + 1);
                $minutes = $this->lockoutMinutes * $multiplier;
                $lockedUntil = $now->modify("+{$minutes} minutes")->format('Y-m-d H:i:s');
            }
            $upd = $this->pdo->prepare("UPDATE auth_login_attempts SET attempts = ?, locked_until = ?, updated_at = ? WHERE `key` = ?");
            $upd->execute([$attempts, $lockedUntil, $nowStr, $key]);
        }
    }

    public function clear(string $key): void
    {
        $this->ensureTable();
        $stmt = $this->pdo->prepare("DELETE FROM auth_login_attempts WHERE `key` = ?");
        $stmt->execute([$key]);
    }
}
