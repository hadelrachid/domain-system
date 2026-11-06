<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Repositories;

use DomainSystem\Core\Contracts\DatabaseConnectionInterface;
use DomainSystem\Plugins\academy\Contracts\StudentAdminRepositoryInterface;
use InvalidArgumentException;
use PDO;

/**
 * Repositório PDO para persistência e consulta administrativa de alunos.
 */
class PdoStudentAdminRepository implements StudentAdminRepositoryInterface
{
    private PDO $pdo;

    public function __construct(DatabaseConnectionInterface|PDO $connection)
    {
        $this->pdo = $connection instanceof DatabaseConnectionInterface
            ? $connection->getPdo()
            : $connection;
    }

    public function getPaginatedStudents(
        int $page = 1,
        int $perPage = 20,
        ?string $search = null,
        ?string $status = null
    ): array {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        [$whereSql, $params] = $this->buildWhere($search, $status);

        $sql = "SELECT id, name, username, email, role, status, created_at 
                FROM users 
                {$whereSql} 
                ORDER BY id DESC 
                LIMIT {$perPage} OFFSET {$offset}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countStudents(?string $search = null, ?string $status = null): int
    {
        [$whereSql, $params] = $this->buildWhere($search, $status);

        $sql = "SELECT COUNT(*) FROM users {$whereSql}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    public function updateStatus(int $studentId, string $status): void
    {
        $valid = ['active', 'suspended', 'banned'];
        if (!in_array($status, $valid, true)) {
            throw new InvalidArgumentException("Status inválido para aluno: {$status}");
        }

        $stmt = $this->pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$status, $studentId]);
    }

    public function updatePassword(int $studentId, string $passwordHash): void
    {
        $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$passwordHash, $studentId]);
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildWhere(?string $search = null, ?string $status = null): array
    {
        $where = ["role IN ('user', 'student', 'subscriber')"];
        $params = [];

        if ($status !== null && $status !== '' && $status !== 'all') {
            $where[] = "status = :status";
            $params[':status'] = $status;
        }

        if ($search !== null && trim($search) !== '') {
            $where[] = "(name LIKE :search OR email LIKE :search OR username LIKE :search)";
            $params[':search'] = '%' . trim($search) . '%';
        }

        return ["WHERE " . implode(" AND ", $where), $params];
    }
}
