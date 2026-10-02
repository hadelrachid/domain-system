<?php

namespace DomainSystem\SystemApps\Database\Schema;

use DomainSystem\SystemApps\Database\Connection;
use DomainSystem\SystemApps\Database\Schema\Grammars\GrammarInterface;
use DomainSystem\SystemApps\Database\Schema\Grammars\MysqlGrammar;
use DomainSystem\SystemApps\Database\Schema\Grammars\SqliteGrammar;

class SchemaBuilder
{
    private Connection $connection;
    private GrammarInterface $grammar;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        
        $driver = $this->connection->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        
        if ($driver === 'sqlite') {
            $this->grammar = new SqliteGrammar();
        } else {
            // Default to MySQL
            $this->grammar = new MysqlGrammar();
        }
    }

    public function create(string $table, \Closure $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);
        
        $sql = $this->grammar->compileCreate($blueprint);
        
        $this->connection->getPdo()->exec($sql);
    }
}
