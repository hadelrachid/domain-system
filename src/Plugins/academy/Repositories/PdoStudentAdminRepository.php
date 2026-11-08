<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Repositories;

use DomainSystem\Core\Contracts\DatabaseConnectionInterface;
use DomainSystem\Plugins\academy\Contracts\StudentAdminRepositoryInterface;
use InvalidArgumentException;
use PDO;
use Throwable;

/**
 * Repositório PDO para persistência e consulta administrativa de alunos.
 * Resiliente a variações de schema da tabela users em produção (ex: ausência de username ou status).
 */
class PdoStudentAdminRepository implements StudentAdminRepositoryInterface
{
    private PDO $pdo;
    private bool $hasUsername = true;
    private bool $hasStatus = true;
    private bool $columnsChecked = false;

    public function __construct(DatabaseConnectionInterface|PDO $connection)
    {
        $this->pdo = $connection instanceof DatabaseConnectionInterface
            ? $connection->getPdo()
            : $connection;
    }

    /**
     * Inspeciona as colunas da tabela users e executa auto-heal não-destrutivo se necessário.
     */
    private function ensureSchema(): void
    {
        if ($this->columnsChecked) {
            return;
        }

        try {
            $colNames = [];
            $driver = '';
            try {
                $driver = (string)$this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            } catch (Throwable) {
                $driver = 'mysql';
            }

            if ($driver === 'sqlite') {
                $stmt = $this->pdo->query("PRAGMA table_info(users)");
                if ($stmt) {
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $colNames = array_map('strtolower', array_column($rows, 'name'));
                }
            } else {
                $stmt = $this->pdo->query("SHOW COLUMNS FROM users");
                if ($stmt) {
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $colNames = array_map('strtolower', array_column($rows, 'Field'));
                }
            }

            $this->hasUsername = in_array('username', $colNames, true);
            $this->hasStatus = in_array('status', $colNames, true);

            // Auto-heal: tenta adicionar a coluna username se ausente
            if (!$this->hasUsername) {
                try {
                    $this->pdo->exec("ALTER TABLE users ADD COLUMN username VARCHAR(100) NULL");
                    $this->hasUsername = true;
                } catch (Throwable) {
                    // Se falhar (ex: sem privilégio DDL), mantém fallback para alias
                }
            }

            // Auto-heal: tenta adicionar a coluna status se ausente
            if (!$this->hasStatus) {
                try {
                    $this->pdo->exec("ALTER TABLE users ADD COLUMN status VARCHAR(20) DEFAULT 'active'");
                    $this->hasStatus = true;
                } catch (Throwable) {
                    // Mantém fallback para alias
                }
            }
        } catch (Throwable) {
            // Em caso de falha de conexão ou tabela restrita, opera em modo seguro
            $this->hasUsername = false;
            $this->hasStatus = false;
        }

        $this->columnsChecked = true;
    }

    public function getPaginatedStudents(
        int $page = 1,
        int $perPage = 20,
        ?string $search = null,
        ?string $status = null
    ): array {
        $this->ensureSchema();

        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        [$whereSql, $params] = $this->buildWhere($search, $status);

        $usernameField = $this->hasUsername ? "username" : "'' AS username";
        $statusField = $this->hasStatus ? "status" : "'active' AS status";

        $sql = "SELECT id, name, {$usernameField}, email, role, {$statusField}, created_at 
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
        $this->ensureSchema();

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

        $this->ensureSchema();
        if (!$this->hasStatus) {
            try {
                $this->pdo->exec("ALTER TABLE users ADD COLUMN status VARCHAR(20) DEFAULT 'active'");
                $this->hasStatus = true;
            } catch (Throwable) {
                return;
            }
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

        if ($this->hasStatus && $status !== null && $status !== '' && $status !== 'all') {
            $where[] = "status = :status";
            $params[':status'] = $status;
        }

        if ($search !== null && trim($search) !== '') {
            if ($this->hasUsername) {
                $where[] = "(name LIKE :search OR email LIKE :search OR username LIKE :search)";
            } else {
                $where[] = "(name LIKE :search OR email LIKE :search)";
            }
            $params[':search'] = '%' . trim($search) . '%';
        }

        return ["WHERE " . implode(" AND ", $where), $params];
    }
}
