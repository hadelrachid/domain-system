<?php

declare(strict_types=1);

namespace DomainSystem\SystemApps\auth\Repositories;

use DomainSystem\SystemApps\Database\Connection;
use DomainSystem\SystemApps\auth\Contracts\UserRepositoryInterface;
use InvalidArgumentException;

class UserRepository implements UserRepositoryInterface
{
    private const ALLOWED_COLUMNS = [
        'id',
        'name',
        'username',
        'email',
        'password',
        'role',
        'status',
        'avatar',
        'profile_image',
        'theme_color',
        'dashboard_layout',
        'two_factor_type',
        'two_factor_secret',
        'email_2fa_code',
        'email_2fa_expiry',
        'session_timeout',
        'created_at',
        'updated_at',
    ];

    private \PDO $db;

    public function __construct(Connection $connection)
    {
        $this->db = $connection->getPdo();
    }

    private function validateColumns(array $data): void
    {
        foreach (array_keys($data) as $col) {
            if (!in_array((string)$col, self::ALLOWED_COLUMNS, true)) {
                throw new InvalidArgumentException("Coluna inválida não permitida em users: {$col}");
            }
        }
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([":email" => $email]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([":id" => $id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function updateTwoFactor(int $userId, string $type, ?string $secret): void
    {
        $stmt = $this->db->prepare("UPDATE users SET two_factor_type = :type, two_factor_secret = :secret WHERE id = :id");
        $stmt->execute([":type" => $type, ":secret" => $secret, ":id" => $userId]);
    }

    public function getAllUsers(): array
    {
        $stmt = $this->db->query("SELECT * FROM users ORDER BY id DESC");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPaginatedUsers(
        int $page = 1,
        int $perPage = 20,
        ?string $role = null,
        ?string $search = null,
        ?string $status = null
    ): array {
        $perPage = max(1, min(100, $perPage));
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        [$whereSql, $params] = $this->buildFilterConditions($role, $search, $status);

        $sql = "SELECT * FROM users {$whereSql} ORDER BY id DESC LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function countUsers(?string $role = null, ?string $search = null, ?string $status = null): int
    {
        [$whereSql, $params] = $this->buildFilterConditions($role, $search, $status);

        $sql = "SELECT COUNT(*) FROM users {$whereSql}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    public function updateStatus(int $userId, string $status): void
    {
        $validStatuses = ['active', 'suspended', 'banned'];
        if (!in_array($status, $validStatuses, true)) {
            throw new InvalidArgumentException("Status inválido para usuário: {$status}");
        }

        $stmt = $this->db->prepare("UPDATE users SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $status, ':id' => $userId]);
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildFilterConditions(?string $role = null, ?string $search = null, ?string $status = null): array
    {
        $where = [];
        $params = [];

        if ($role !== null && $role !== '' && $role !== 'all') {
            if ($role === 'students_group') {
                $where[] = "role IN ('user', 'student', 'subscriber')";
            } elseif ($role === 'admins_group') {
                $where[] = "role IN ('admin', 'manager', 'emergency')";
            } else {
                $where[] = "role = :role";
                $params[':role'] = $role;
            }
        }

        if ($status !== null && $status !== '' && $status !== 'all') {
            $where[] = "status = :status";
            $params[':status'] = $status;
        }

        if ($search !== null && trim($search) !== '') {
            $where[] = "(name LIKE :search OR email LIKE :search OR username LIKE :search)";
            $params[':search'] = '%' . trim($search) . '%';
        }

        $whereSql = empty($where) ? "" : "WHERE " . implode(" AND ", $where);
        return [$whereSql, $params];
    }

    public function createUser(array $data): int
    {
        $this->validateColumns($data);
        $fields = implode(", ", array_keys($data));
        $placeholders = ":" . implode(", :", array_keys($data));
        $stmt = $this->db->prepare("INSERT INTO users ($fields) VALUES ($placeholders)");
        $params = [];
        foreach ($data as $k => $v) {
            $params[":$k"] = $v;
        }
        $stmt->execute($params);
        $userId = (int)$this->db->lastInsertId();
        if (isset($data['role'])) {
            try {
                $stmtRole = $this->db->prepare('SELECT id FROM roles WHERE slug = ?');
                $stmtRole->execute([$data['role']]);
                $roleId = $stmtRole->fetchColumn();
                if ($roleId) {
                    $this->db->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)')->execute([$userId, $roleId]);
                }
            } catch (\Throwable $e) {
                // Tabela roles/user_roles pode não existir em ambientes isolados
            }
        }
        return $userId;
    }

    public function updateTwoFactorSecret(int $userId, ?string $secret): void
    {
        $stmt = $this->db->prepare("UPDATE users SET two_factor_secret = :secret WHERE id = :id");
        $stmt->execute([":secret" => $secret, ":id" => $userId]);
    }

    public function updatePassword(int $userId, string $hash): void
    {
        $stmt = $this->db->prepare("UPDATE users SET password = :password WHERE id = :id");
        $stmt->execute([":password" => $hash, ":id" => $userId]);
    }

    public function updateProfile(int $userId, array $data): void
    {
        if (empty($data)) {
            return;
        }
        $this->validateColumns($data);
        $setParts = [];
        $params = [":id" => $userId];
        foreach ($data as $key => $val) {
            $setParts[] = "$key = :$key";
            $params[":$key"] = $val;
        }
        $setClause = implode(", ", $setParts);
        $stmt = $this->db->prepare("UPDATE users SET $setClause WHERE id = :id");
        $stmt->execute($params);
    }

    public function deleteUser(int $id): void
    {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
    }
}
