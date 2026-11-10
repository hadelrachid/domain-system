<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Repositories;

use DomainSystem\Core\Contracts\DatabaseConnectionInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipOrderRepositoryInterface;
use PDO;

/**
 * Repositório PDO para Ordens de Dropshipping (SRP / DIP).
 */
class PdoDropshipOrderRepository implements DropshipOrderRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO|DatabaseConnectionInterface $connection)
    {
        $this->pdo = $connection instanceof DatabaseConnectionInterface
            ? $connection->getPdo()
            : $connection;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO domain_drop_orders (
            order_ref, customer_name, customer_email, customer_document,
            shipping_address, aliexpress_order_id, tracking_code, status,
            total_paid_brl, created_at, updated_at
        ) VALUES (
            :order_ref, :customer_name, :customer_email, :customer_document,
            :shipping_address, :aliexpress_order_id, :tracking_code, :status,
            :total_paid_brl, :created_at, :updated_at
        )";

        $stmt = $this->pdo->prepare($sql);
        $now = date('Y-m-d H:i:s');

        $stmt->execute([
            ':order_ref'           => (string)($data['order_ref'] ?? uniqid('ORD_DROP_')),
            ':customer_name'        => (string)($data['customer_name'] ?? 'Cliente'),
            ':customer_email'       => (string)($data['customer_email'] ?? ''),
            ':customer_document'    => isset($data['customer_document']) ? (string)$data['customer_document'] : null,
            ':shipping_address'     => isset($data['shipping_address']) ? (is_array($data['shipping_address']) ? json_encode($data['shipping_address'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : (string)$data['shipping_address']) : null,
            ':aliexpress_order_id'  => isset($data['aliexpress_order_id']) ? (string)$data['aliexpress_order_id'] : null,
            ':tracking_code'        => isset($data['tracking_code']) ? (string)$data['tracking_code'] : null,
            ':status'               => (string)($data['status'] ?? 'pending'),
            ':total_paid_brl'       => (float)($data['total_paid_brl'] ?? 0.0),
            ':created_at'           => $now,
            ':updated_at'           => $now,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function findByOrderRef(string $orderRef): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM domain_drop_orders WHERE order_ref = :ref LIMIT 1");
        $stmt->execute([':ref' => $orderRef]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    public function updateFulfillment(string $orderRef, string $status, ?string $aliExpressOrderId = null, ?string $trackingCode = null): bool
    {
        $fields = ['status = :status', 'updated_at = :updated_at'];
        $params = [
            ':ref'        => $orderRef,
            ':status'     => $status,
            ':updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($aliExpressOrderId !== null) {
            $fields[] = 'aliexpress_order_id = :ae_id';
            $params[':ae_id'] = $aliExpressOrderId;
        }

        if ($trackingCode !== null) {
            $fields[] = 'tracking_code = :track';
            $params[':track'] = $trackingCode;
        }

        $sql = "UPDATE domain_drop_orders SET " . implode(', ', $fields) . " WHERE order_ref = :ref";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute($params);
    }

    public function list(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = "status = :st";
            $params[':st'] = (string)$filters['status'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(order_ref LIKE :q OR customer_name LIKE :q OR customer_email LIKE :q OR tracking_code LIKE :q)";
            $params[':q'] = '%' . (string)$filters['search'] . '%';
        }

        $sql = "SELECT * FROM domain_drop_orders";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $sql .= " ORDER BY created_at DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
