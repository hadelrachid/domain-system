<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts;

use PDO;

interface DatabaseConnectionInterface
{
    public function getPdo(): PDO;
}
