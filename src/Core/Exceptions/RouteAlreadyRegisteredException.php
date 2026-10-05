<?php

namespace DomainSystem\Core\Exceptions;

use Exception;

class RouteAlreadyRegisteredException extends Exception
{
    public function __construct(string $method, string $path)
    {
        parent::__construct("A rota [{$method}] {$path} já está registrada e não pode ser sobrescrita.");
    }
}
