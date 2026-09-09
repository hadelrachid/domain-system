<?php

namespace DomainSystem\Plugins\Database\Schema\Grammars;

use DomainSystem\Plugins\Database\Schema\Blueprint;

class SqliteGrammar implements GrammarInterface
{
    public function compileCreate(Blueprint $blueprint): string
    {
        $columns = [];
        
        foreach ($blueprint->getColumns() as $col) {
            $columns[] = $this->compileColumn($col);
        }
        
        foreach ($blueprint->getForeignKeys() as $fk) {
            $columns[] = "FOREIGN KEY ({$fk['column']}) REFERENCES {$fk['on']}({$fk['references']}) ON DELETE {$fk['onDelete']}";
        }
        
        $columnsSql = implode(', ', $columns);
        
        return "CREATE TABLE IF NOT EXISTS {$blueprint->getTable()} ($columnsSql)";
    }

    private function compileColumn(array $col): string
    {
        // SQLite expects INTEGER PRIMARY KEY AUTOINCREMENT as a single phrase
        if ($col['primary'] && $col['autoIncrement'] && $col['type'] === 'integer') {
            return "{$col['name']} INTEGER PRIMARY KEY AUTOINCREMENT";
        }
        
        $sql = "{$col['name']} " . $this->getType($col);
        
        if ($col['primary']) {
            $sql .= " PRIMARY KEY";
        }
        
        if (!$col['nullable']) {
            $sql .= " NOT NULL";
        }
        
        if ($col['unique']) {
            $sql .= " UNIQUE";
        }
        
        if ($col['default'] !== null) {
            if (strtoupper($col['default']) === 'CURRENT_TIMESTAMP') {
                $sql .= " DEFAULT CURRENT_TIMESTAMP";
            } else {
                $sql .= " DEFAULT '{$col['default']}'";
            }
        }
        
        return $sql;
    }

    private function getType(array $col): string
    {
        switch ($col['type']) {
            case 'integer':
            case 'boolean':
                return 'INTEGER';
            case 'string':
            case 'text':
            case 'date':
            case 'datetime':
                return 'TEXT';
            case 'float':
            case 'decimal':
                return 'REAL';
            default:
                return 'TEXT';
        }
    }
}
