<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Repositories;

use DomainSystem\Core\Contracts\DatabaseConnectionInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipCustomerRepositoryInterface;
use PDO;

/**
 * Repositório PDO para clientes cadastrados na vitrine RachidD.com / DomainDrop.
 */
class PdoDropshipCustomerRepository implements DropshipCustomerRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO|DatabaseConnectionInterface $connection)
    {
        $this->pdo = $connection instanceof DatabaseConnectionInterface
            ? $connection->getPdo()
            : $connection;
        DomainDropSchemaEnsurer::ensure($this->pdo);
    }

    public function create(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $email = strtolower(trim((string)($data['email'] ?? '')));

        $stmt = $this->pdo->prepare("
            INSERT INTO domain_drop_customers (
                name, email, cpf, phone, password_hash, status, created_at, updated_at
            ) VALUES (
                :name, :email, :cpf, :phone, :password_hash, :status, :created_at, :updated_at
            )
        ");

        $stmt->execute([
            ':name'          => trim((string)($data['name'] ?? '')),
            ':email'         => $email,
            ':cpf'           => isset($data['cpf']) && trim((string)$data['cpf']) !== '' ? trim((string)$data['cpf']) : null,
            ':phone'         => isset($data['phone']) && trim((string)$data['phone']) !== '' ? trim((string)$data['phone']) : null,
            ':password_hash' => (string)($data['password_hash'] ?? ''),
            ':status'        => (string)($data['status'] ?? 'active'),
            ':created_at'    => (string)($data['created_at'] ?? $now),
            ':updated_at'    => (string)($data['updated_at'] ?? $now),
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM domain_drop_customers WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM domain_drop_customers WHERE LOWER(email) = :email LIMIT 1");
        $stmt->execute([':email' => strtolower(trim($email))]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    public function listAll(int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM domain_drop_customers ORDER BY created_at DESC LIMIT " . max(1, (int)$limit) . " OFFSET " . max(0, (int)$offset)
        );
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function listCustomers(int $page = 1, int $perPage = 15, ?string $search = null, ?string $status = null): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $where = [];
        $params = [];

        if ($status !== null && $status !== '' && $status !== 'all') {
            $where[] = 'c.status = :status';
            $params[':status'] = $status;
        }

        if ($search !== null && trim($search) !== '') {
            $q = '%' . strtolower(trim($search)) . '%';
            $where[] = '(LOWER(c.name) LIKE :q1 OR LOWER(c.email) LIKE :q2 OR LOWER(COALESCE(c.cpf, \'\')) LIKE :q3 OR LOWER(COALESCE(c.phone, \'\')) LIKE :q4)';
            $params[':q1'] = $q;
            $params[':q2'] = $q;
            $params[':q3'] = $q;
            $params[':q4'] = $q;
        }

        $whereSql = !empty($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM domain_drop_customers c {$whereSql}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $sql = "
            SELECT
                c.*,
                (SELECT COUNT(*) FROM domain_drop_orders o WHERE LOWER(o.customer_email) = LOWER(c.email)) AS orders_count,
                (SELECT COALESCE(SUM(o.total_paid_brl), 0) FROM domain_drop_orders o WHERE LOWER(o.customer_email) = LOWER(c.email)) AS total_spent_brl,
                (SELECT COUNT(*) FROM domain_drop_disputes d WHERE LOWER(d.customer_email) = LOWER(c.email)) AS disputes_count
            FROM domain_drop_customers c
            {$whereSql}
            ORDER BY c.created_at DESC, c.id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    public function getCustomerMetrics(): array
    {
        $total = (int)$this->pdo->query("SELECT COUNT(*) FROM domain_drop_customers")->fetchColumn();
        $active = (int)$this->pdo->query("SELECT COUNT(*) FROM domain_drop_customers WHERE status = 'active'")->fetchColumn();
        $suspended = max(0, $total - $active);
        $linkedOrders = (int)$this->pdo->query("
            SELECT COUNT(*) FROM domain_drop_orders o
            WHERE EXISTS (SELECT 1 FROM domain_drop_customers c WHERE LOWER(c.email) = LOWER(o.customer_email))
        ")->fetchColumn();

        return [
            'total_customers'     => $total,
            'active_customers'    => $active,
            'suspended_customers' => $suspended,
            'linked_orders'       => $linkedOrders,
        ];
    }

    public function updateStatus(int $id, string $status): bool
    {
        $allowed = ['active', 'suspended', 'blocked'];
        $normalized = in_array($status, $allowed, true) ? $status : 'active';

        $stmt = $this->pdo->prepare("
            UPDATE domain_drop_customers
            SET status = :status, updated_at = :updated_at
            WHERE id = :id
        ");

        return $stmt->execute([
            ':status'     => $normalized,
            ':updated_at' => date('Y-m-d H:i:s'),
            ':id'         => $id,
        ]);
    }
}
