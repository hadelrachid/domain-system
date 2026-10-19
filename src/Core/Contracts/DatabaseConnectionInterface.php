<?php

namespace DomainSystem\Core\Contracts;

use PDO;

interface DatabaseConnectionInterface
{
    public function getPdo(): PDO;
}
