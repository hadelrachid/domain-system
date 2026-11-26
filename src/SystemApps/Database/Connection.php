<?php

declare(strict_types=1);

namespace DomainSystem\SystemApps\Database;

use DomainSystem\Core\Contracts\DatabaseConnectionInterface;
use PDO;
use PDOException;
use Exception;

class Connection implements DatabaseConnectionInterface
{
    private ?PDO $pdo = null;
    private string $dsn;
    private string $user;
    private string $pass;
    private array $options;

    public function __construct(string $dsn, string $user = '', string $pass = '', array $options = [])
    {
        $this->dsn = $dsn;
        $this->user = $user;
        $this->pass = $pass;

        $defaultOptions = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        $this->options = array_replace($defaultOptions, $options);
        $this->connect();
    }

    private function connect(): void
    {
        try {
            $this->pdo = new PDO($this->dsn, $this->user, $this->pass, $this->options);
            if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
                try {
                    $this->pdo->exec("SET SESSION wait_timeout = 28800, interactive_timeout = 28800");
                } catch (\Throwable $e) {}
            }
        } catch (PDOException $e) {
            throw new Exception("Connection failed: " . $e->getMessage());
        }
    }

    public function getPdo(): PDO
    {
        if ($this->pdo === null) {
            $this->connect();
        } else {
            try {
                @$this->pdo->query('SELECT 1');
            } catch (\Throwable $e) {
                $this->connect();
            }
        }
        return $this->pdo;
    }
}
