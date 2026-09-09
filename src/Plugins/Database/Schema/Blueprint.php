<?php

namespace DomainSystem\Plugins\Database\Schema;

class Blueprint
{
    private string $table;
    private array $columns = [];
    private array $foreignKeys = [];
    private array $indexes = [];

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    public function getIndexes(): array
    {
        return $this->indexes;
    }

    public function addColumn(string $type, string $name, array $parameters = [])
    {
        $column = array_merge(['type' => $type, 'name' => $name, 'nullable' => false, 'default' => null, 'unique' => false, 'primary' => false, 'autoIncrement' => false], $parameters);
        $this->columns[] = &$column;
        
        return new class($column) {
            private array $col;
            public function __construct(&$col) { $this->col = &$col; }
            public function nullable(): self { $this->col['nullable'] = true; return $this; }
            public function default($value): self { $this->col['default'] = $value; return $this; }
            public function unique(): self { $this->col['unique'] = true; return $this; }
            public function primary(): self { $this->col['primary'] = true; return $this; }
            public function autoIncrement(): self { $this->col['autoIncrement'] = true; return $this; }
        };
    }

    public function id(string $column = 'id')
    {
        return $this->addColumn('integer', $column)->primary()->autoIncrement();
    }

    public function string(string $column, int $length = 255)
    {
        return $this->addColumn('string', $column, ['length' => $length]);
    }

    public function text(string $column)
    {
        return $this->addColumn('text', $column);
    }

    public function integer(string $column)
    {
        return $this->addColumn('integer', $column);
    }

    public function float(string $column, int $precision = 8, int $scale = 2)
    {
        return $this->addColumn('float', $column, ['precision' => $precision, 'scale' => $scale]);
    }
    
    public function decimal(string $column, int $precision = 10, int $scale = 2)
    {
        return $this->addColumn('decimal', $column, ['precision' => $precision, 'scale' => $scale]);
    }

    public function boolean(string $column)
    {
        return $this->addColumn('boolean', $column);
    }

    public function date(string $column)
    {
        return $this->addColumn('date', $column);
    }

    public function datetime(string $column)
    {
        return $this->addColumn('datetime', $column);
    }

    public function timestamps()
    {
        $this->addColumn('datetime', 'created_at')->nullable()->default('CURRENT_TIMESTAMP');
        $this->addColumn('datetime', 'updated_at')->nullable();
    }

    public function foreign(string $column, string $references, string $onTable, string $onDelete = 'CASCADE')
    {
        $this->foreignKeys[] = [
            'column' => $column,
            'references' => $references,
            'on' => $onTable,
            'onDelete' => $onDelete
        ];
    }
}
