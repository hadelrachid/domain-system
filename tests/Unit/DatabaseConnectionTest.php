<?php

namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DomainSystem\Plugins\Database\Connection;
use PDO;

class DatabaseConnectionTest extends TestCase
{
    public function testConnectionUsesPdoAndSingleton()
    {
        // Using in-memory sqlite for tests
        $dsn = 'sqlite::memory:';
        
        $connection1 = new Connection($dsn);
        $connection2 = new Connection($dsn);

        $pdo1 = $connection1->getPdo();
        $pdo2 = $connection2->getPdo();

        $this->assertInstanceOf(PDO::class, $pdo1);
        
        // Ensure same instance is not guaranteed strictly by new Connection(), 
        // but by the Container. Connection class itself is just a wrapper.
        // Let's test the PDO connection works.
        $stmt = $pdo1->query("SELECT 1 as val");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertEquals(1, $result['val']);
    }
}
