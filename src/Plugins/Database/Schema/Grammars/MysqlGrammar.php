<?php

namespace DomainSystem\Plugins\Database\Schema\Grammars;

use DomainSystem\Plugins\Database\Schema\Blueprint;

class MysqlGrammar implements GrammarInterface
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
        $sql = "{$col['name']} " . $this->getType($col);
        
        if ($col['nullable']) {
            $sql .= " NULL";
        } else {
            $sql .= " NOT NULL";
        }
        
        if ($col['autoIncrement']) {
            $sql .= " AUTO_INCREMENT";
        }
        
        if ($col['primary']) {
            $sql .= " PRIMARY KEY";
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
                return 'INT';
            case 'string':
                $len = $col['length'] ?? 255;
                return "VARCHAR($len)";
            case 'text':
                return 'TEXT';
            case 'boolean':
                return 'TINYINT(1)';
            case 'float':
                return 'FLOAT';
            case 'decimal':
                return "DECIMAL({$col['precision']}, {$col['scale']})";
            case 'date':
                return 'DATE';
            case 'datetime':
                return 'DATETIME';
            default:
                return 'VARCHAR(255)';
        }
    }
}
