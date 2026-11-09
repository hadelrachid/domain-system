<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Repositories;

use DomainSystem\Core\Contracts\DatabaseConnectionInterface;
use DomainSystem\Plugins\academy\Contracts\StudentCredentialRepositoryInterface;
use DomainSystem\Plugins\academy\DTO\StudentCredential;
use PDO;
use Throwable;

/**
 * Implementação PDO do repositório de credenciais de alunos (DIP / SRP).
 */
class PdoStudentCredentialRepository implements StudentCredentialRepositoryInterface
{
    private PDO $pdo;

    public function __construct(DatabaseConnectionInterface|PDO $connection)
    {
        $this->pdo = $connection instanceof DatabaseConnectionInterface
            ? $connection->getPdo()
            : $connection;
    }

    public function findByEmail(string $email): ?StudentCredential
    {
        $normalized = strtolower(trim($email));
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE LOWER(email) = ? LIMIT 1");
        $stmt->execute([$normalized]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!is_array($row)) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function findByUsername(string $username): ?StudentCredential
    {
        $normalized = strtolower(trim($username));
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE LOWER(username) = ? LIMIT 1");
            $stmt->execute([$normalized]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!is_array($row)) {
                return null;
            }

            return $this->hydrate($row);
        } catch (Throwable $e) {
            // Em caso de tabela sem a coluna username no ambiente
            return null;
        }
    }

    public function createStudent(string $name, ?string $username, string $email, string $passwordHash): int
    {
        $normalizedEmail = strtolower(trim($email));
        $normalizedUser = ($username !== null && trim($username) !== '') ? strtolower(trim($username)) : null;

        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO users (name, username, email, password, role, created_at, updated_at)
                VALUES (?, ?, ?, ?, 'user', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ");
            $stmt->execute([$name, $normalizedUser, $normalizedEmail, $passwordHash]);
        } catch (\PDOException $e) {
            // Fallback se coluna username não existir no schema
            if (str_contains(strtolower($e->getMessage()), 'no such column') || str_contains(strtolower($e->getMessage()), 'unknown column')) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO users (name, email, password, role, created_at, updated_at)
                    VALUES (?, ?, ?, 'user', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ");
                $stmt->execute([$name, $normalizedEmail, $passwordHash]);
            } else {
                throw $e;
            }
        }

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): StudentCredential
    {
        return new StudentCredential(
            id: (int)($row['id'] ?? 0),
            name: (string)($row['name'] ?? ''),
            email: (string)($row['email'] ?? ''),
            passwordHash: (string)($row['password'] ?? ''),
            username: isset($row['username']) && !empty($row['username']) ? (string)$row['username'] : null,
            status: (string)($row['status'] ?? 'active'),
            role: (string)($row['role'] ?? 'student')
        );
    }
}
