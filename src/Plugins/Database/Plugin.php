<?php

namespace DomainSystem\Plugins\Database;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Container\Container;

class Plugin extends AbstractPlugin
{


    public function register(): void
    {
        // For default we look at env vars
        $dsn = getenv('DB_DSN') ?: 'sqlite::memory:'; // Fallback to memory for safety if not set
        $user = getenv('DB_USER') ?: '';
        $pass = getenv('DB_PASS') ?: '';

        // We bind Connection to the container so that it acts as a Singleton
        $this->container->singleton(Connection::class, function() use ($dsn, $user, $pass) {
            return new Connection($dsn, $user, $pass);
        });

        // We bind QueryBuilder. We can make it return a new instance or bind as factory
        // Container::make usually resolves dependencies and returns a new instance unless bound as singleton.
        // We will bind it to a closure so it always gets the shared Connection
        $this->container->bind(QueryBuilder::class, function($c) {
            return new QueryBuilder($c->make(Connection::class));
        });
    }
}
