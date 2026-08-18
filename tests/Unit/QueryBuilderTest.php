<?php

namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DomainSystem\Plugins\Database\Connection;
use DomainSystem\Plugins\Database\QueryBuilder;

class QueryBuilderTest extends TestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        // Setup SQLite in memory connection
        $this->connection = new Connection('sqlite::memory:');
        
        // Create a dummy table for tests
        $pdo = $this->connection->getPdo();
        $pdo->exec("CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT)");
    }

    public function testInsertAndSelect()
    {
        $qb = new QueryBuilder($this->connection);
        
        $id = $qb->table('users')->insert([
            'name' => 'John Doe',
            'email' => 'john@test.com'
        ]);

        $this->assertGreaterThan(0, $id);

        // Fetch it back
        $user = $qb->table('users')->where('id', '=', $id)->first();
        
        $this->assertEquals('John Doe', $user['name']);
        $this->assertEquals('john@test.com', $user['email']);
    }

    public function testUpdate()
    {
        $qb = new QueryBuilder($this->connection);
        $id = $qb->table('users')->insert(['name' => 'Old Name', 'email' => 'old@test.com']);

        $affected = $qb->table('users')->where('id', '=', $id)->update(['name' => 'New Name']);
        
        $this->assertEquals(1, $affected);

        $user = $qb->table('users')->where('id', '=', $id)->first();
        $this->assertEquals('New Name', $user['name']);
        $this->assertEquals('old@test.com', $user['email']);
    }

    public function testDelete()
    {
        $qb = new QueryBuilder($this->connection);
        $id = $qb->table('users')->insert(['name' => 'Delete Me', 'email' => 'del@test.com']);

        $affected = $qb->table('users')->where('id', '=', $id)->delete();
        $this->assertEquals(1, $affected);

        $user = $qb->table('users')->where('id', '=', $id)->first();
        $this->assertNull($user);
    }

    public function testGetMultiple()
    {
        $qb = new QueryBuilder($this->connection);
        $qb->table('users')->insert(['name' => 'User 1']);
        $qb->table('users')->insert(['name' => 'User 2']);

        $users = $qb->table('users')->get();
        $this->assertCount(2, $users);
    }
}
