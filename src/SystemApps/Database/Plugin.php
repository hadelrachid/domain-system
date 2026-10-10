<?php

namespace DomainSystem\SystemApps\Database;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Contracts\OsConnectorInterface;
use DomainSystem\Core\Contracts\OsRuntimeInterface;

class Plugin extends AbstractPlugin implements OsExtensionInterface
{
    // Método legado ignorado pelo OS 2.0, mas exigido pela classe Abstrata
    public function register(): void {}

    // ==========================================
    // FASE 1: NEGOCIAÇÃO
    // ==========================================
    public function osRegister(OsConnectorInterface $os): void
    {
        $os->requireLink('core.db');
        $os->requireLink('core.db.schema');
        // O Plugin Database grita para o OS: "Eu forneço essas 3 ferramentas para o sistema!"
        $os->provideLink('core.db', Connection::class);
        $os->provideLink('core.db.query', QueryBuilder::class);
        $os->provideLink('core.db.schema', \DomainSystem\SystemApps\Database\Schema\SchemaBuilder::class);
    }

    // ==========================================
    // FASE 2: EXECUÇÃO
    // ==========================================
    public function osBoot(OsRuntimeInterface $runtime): void
    {
        // 1. Configuramos os singletons no Container do Kernel
        // Isso atende os "Providers" oficiais do OS 2.0 e MANTÉM retrocompatibilidade com plugins legados!
        $runtime->singleton(Connection::class, function($c) {
            $config = $c->make(\DomainSystem\Core\Config\DatabaseConfig::class);
            return new Connection($config->getDsn(), $config->getUser(), $config->getPass());
        });

        $runtime->bind(QueryBuilder::class, function($c) {
            return new QueryBuilder($c->make(Connection::class));
        });

        $runtime->bind(\DomainSystem\SystemApps\Database\Schema\SchemaBuilder::class, function($c) {
            return new \DomainSystem\SystemApps\Database\Schema\SchemaBuilder($c->make(Connection::class));
        });
    }
}
