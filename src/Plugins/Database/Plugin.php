<?php

namespace DomainSystem\Plugins\Database;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Container\Container;

class Plugin extends AbstractPlugin
{


    public function register(): void
    {
        // We bind Connection to the container so that it acts as a Singleton
        $this->container->singleton(Connection::class, function($c) {
            $context = $c->make(\DomainSystem\Core\Tenant\TenantContext::class);
            $dbConfig = $context->getDbConfig();
            
            // Fallback for extreme cases where Context isn't fully loaded
            $dsn = $dbConfig['dsn'] ?? $_ENV['DB_DSN'] ?? getenv('DB_DSN') ?: 'sqlite::memory:';
            $user = $dbConfig['user'] ?? $_ENV['DB_USER'] ?? getenv('DB_USER') ?: '';
            $pass = $dbConfig['pass'] ?? $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '';
            
            return new Connection($dsn, $user, $pass);
        });

        // We bind QueryBuilder. We can make it return a new instance or bind as factory
        // Container::make usually resolves dependencies and returns a new instance unless bound as singleton.
        // We will bind it to a closure so it always gets the shared Connection
        $this->container->bind(QueryBuilder::class, function($c) {
            return new QueryBuilder($c->make(Connection::class));
        });

        $this->container->bind(\DomainSystem\Plugins\Database\Schema\SchemaBuilder::class, function($c) {
            return new \DomainSystem\Plugins\Database\Schema\SchemaBuilder($c->make(Connection::class));
        });
    }
}
