<?php

namespace DomainSystem\Plugins\Database;

use PDO;
use PDOException;
use Exception;

class Connection
{
    private PDO $pdo;

    public function __construct(string $dsn, string $user = '', string $pass = '', array $options = [])
    {
        $defaultOptions = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        $options = array_replace($defaultOptions, $options);

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            throw new Exception("Connection failed: " . $e->getMessage());
        }
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
