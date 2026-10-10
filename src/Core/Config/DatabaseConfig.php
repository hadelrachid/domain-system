<?php

namespace DomainSystem\Core\Config;

class DatabaseConfig
{
    private string $dsn;
    private string $user;
    private string $pass;

    public function __construct(string $dsn = '', string $user = '', string $pass = '')
    {
        $this->dsn = $dsn;
        $this->user = $user;
        $this->pass = $pass;
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
