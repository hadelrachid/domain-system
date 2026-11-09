<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Plugins\academy\DTO\StudentCredential;
use DomainSystem\Plugins\academy\Repositories\PdoStudentCredentialRepository;
use PDO;
use PHPUnit\Framework\TestCase;

class StudentCredentialRepositoryTest extends TestCase
{
    private PDO $pdo;
    private PdoStudentCredentialRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                username TEXT NULL UNIQUE,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'active',
                role TEXT NOT NULL DEFAULT 'user',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->repo = new PdoStudentCredentialRepository($this->pdo);
    }

    public function testCreateStudentAndFindByEmailAndUsername(): void
    {
        $hash = password_hash('segredo123', PASSWORD_DEFAULT);
        $id = $this->repo->createStudent('Ana Silva', 'anasilva', 'ana@silva.com', $hash);

        $this->assertGreaterThan(0, $id);

        $byEmail = $this->repo->findByEmail('ANA@SILVA.COM');
        $this->assertInstanceOf(StudentCredential::class, $byEmail);
        $this->assertSame($id, $byEmail->id);
        $this->assertSame('Ana Silva', $byEmail->name);
        $this->assertSame('anasilva', $byEmail->username);
        $this->assertSame('active', $byEmail->status);
        $this->assertTrue(password_verify('segredo123', $byEmail->passwordHash));

        $byUsername = $this->repo->findByUsername('ANASILVA');
        $this->assertInstanceOf(StudentCredential::class, $byUsername);
        $this->assertSame($id, $byUsername->id);
    }

    public function testReturnsNullWhenNotFound(): void
    {
        $this->assertNull($this->repo->findByEmail('inexistente@email.com'));
        $this->assertNull($this->repo->findByUsername('usuario_fantasma'));
    }
}
