<?php

namespace DomainSystem\Core\Config;

class DatabaseConfig
{
    private string $dsn;
    private string $user;
    private string $pass;

    public function __construct(string $dsn = '', string $user = '', string $pass = '')
    {
        $this->dsn = $dsn ?: (getenv('DB_DSN') ?: ($_ENV['DB_DSN'] ?? 'sqlite::memory:'));
        $this->user = $user ?: (getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? ''));
        $this->pass = $pass ?: (getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? ''));
    }

    public function getDsn(): string
    {
        return $this->dsn;
    }

    public function getUser(): string
    {
        return $this->user;
    }

    public function getPass(): string
    {
        return $this->pass;
    }
}
