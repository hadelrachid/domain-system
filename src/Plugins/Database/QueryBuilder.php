<?php

namespace DomainSystem\Plugins\Database;

class QueryBuilder
{
    private Connection $connection;
    private string $table = '';
    private array $columns = ['*'];
    private array $wheres = [];
    private array $bindings = [];
    private int $bindingCounter = 0;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function table(string $table): self
    {
        // Return a new instance to allow chaining without polluting state (like multiple query builders)
        // Actually, it's better to clone or just reset state if we want to reuse the builder instance,
        // but typically a new QueryBuilder instance is created per query, or cloned.
        $instance = new self($this->connection);
        $instance->table = $table;
        return $instance;
    }

    public function select(array $columns = ['*']): self
    {
        $this->columns = $columns;
        return $this;
    }

    public function where(string $column, string $operator, mixed $value): self
    {
        $paramName = "w_" . $this->bindingCounter++;
        $this->wheres[] = "$column $operator :$paramName";
        $this->bindings[$paramName] = $value;
        return $this;
    }

    private function buildSelect(): string
    {
        $sql = "SELECT " . implode(', ', $this->columns) . " FROM {$this->table}";
        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }
        return $sql;
    }

    public function get(): array
    {
        $sql = $this->buildSelect();
        $stmt = $this->connection->getPdo()->prepare($sql);
        $stmt->execute($this->bindings);
        return $stmt->fetchAll();
    }

    public function first(): ?array
    {
        $sql = $this->buildSelect() . ' LIMIT 1';
        $stmt = $this->connection->getPdo()->prepare($sql);
        $stmt->execute($this->bindings);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function insert(array $data): int
    {
        $columns = array_keys($data);
        $placeholders = [];
        $bindings = [];

        foreach ($columns as $col) {
            $paramName = "i_" . $this->bindingCounter++;
            $placeholders[] = ":$paramName";
            $bindings[$paramName] = $data[$col];
        }

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $this->connection->getPdo()->prepare($sql);
        $stmt->execute($bindings);
        
        return (int) $this->connection->getPdo()->lastInsertId();
    }

    public function upsert(array $data, array $conflictColumns, array $updateColumns): int
    {
        $columns = array_keys($data);
        $placeholders = [];
        $bindings = [];

        foreach ($columns as $col) {
            $paramName = "i_" . $this->bindingCounter++;
            $placeholders[] = ":$paramName";
            $bindings[$paramName] = $data[$col];
        }

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        $sql .= " ON CONFLICT(" . implode(', ', $conflictColumns) . ") DO UPDATE SET ";
        $sets = [];
        foreach ($updateColumns as $uCol) {
            $sets[] = "$uCol = excluded.$uCol";
        }
        $sql .= implode(', ', $sets);

        $stmt = $this->connection->getPdo()->prepare($sql);
        $stmt->execute($bindings);
        
        return $stmt->rowCount();
    }

    public function update(array $data): int
    {
        $sets = [];
        $updateBindings = [];

        foreach ($data as $column => $value) {
            $paramName = "u_" . $this->bindingCounter++;
            $sets[] = "$column = :$paramName";
            $updateBindings[$paramName] = $value;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets);
        
        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }

        $stmt = $this->connection->getPdo()->prepare($sql);
        $stmt->execute(array_merge($updateBindings, $this->bindings));
        
        return $stmt->rowCount();
    }

    public function delete(): int
    {
        $sql = "DELETE FROM {$this->table}";
        
        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }

        $stmt = $this->connection->getPdo()->prepare($sql);
        $stmt->execute($this->bindings);
        
        return $stmt->rowCount();
    }
}
