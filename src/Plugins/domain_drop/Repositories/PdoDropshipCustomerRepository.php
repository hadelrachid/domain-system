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

        $shippingZip = isset($data['shipping_zip']) && trim((string)$data['shipping_zip']) !== '' ? trim((string)$data['shipping_zip']) : null;
        $shippingAddress = isset($data['shipping_address']) && trim((string)$data['shipping_address']) !== '' ? trim((string)$data['shipping_address']) : null;
        $shippingNumber = isset($data['shipping_number']) && trim((string)$data['shipping_number']) !== '' ? trim((string)$data['shipping_number']) : null;
        $shippingComplement = isset($data['shipping_complement']) && trim((string)$data['shipping_complement']) !== '' ? trim((string)$data['shipping_complement']) : null;
        $shippingNeighborhood = isset($data['shipping_neighborhood']) && trim((string)$data['shipping_neighborhood']) !== '' ? trim((string)$data['shipping_neighborhood']) : null;
        $shippingCity = isset($data['shipping_city']) && trim((string)$data['shipping_city']) !== '' ? trim((string)$data['shipping_city']) : null;
        $shippingState = isset($data['shipping_state']) && trim((string)$data['shipping_state']) !== '' ? strtoupper(trim((string)$data['shipping_state'])) : null;

        $defaultShippingAddress = null;
        if (isset($data['default_shipping_address']) && is_array($data['default_shipping_address'])) {
            $defaultShippingAddress = json_encode($data['default_shipping_address'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } elseif (isset($data['default_shipping_address']) && is_string($data['default_shipping_address']) && trim($data['default_shipping_address']) !== '') {
            $defaultShippingAddress = trim($data['default_shipping_address']);
        } elseif ($shippingZip !== null || $shippingAddress !== null) {
            $defaultShippingAddress = json_encode([
                'zip'          => $shippingZip,
                'street'       => $shippingAddress,
                'number'       => $shippingNumber,
                'complement'   => $shippingComplement,
                'neighborhood' => $shippingNeighborhood,
                'city'         => $shippingCity,
                'state'        => $shippingState,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO domain_drop_customers (
                name, email, cpf, phone, password_hash, status,
                shipping_zip, shipping_address, shipping_number, shipping_complement,
                shipping_neighborhood, shipping_city, shipping_state, default_shipping_address,
                created_at, updated_at
            ) VALUES (
                :name, :email, :cpf, :phone, :password_hash, :status,
                :shipping_zip, :shipping_address, :shipping_number, :shipping_complement,
                :shipping_neighborhood, :shipping_city, :shipping_state, :default_shipping_address,
                :created_at, :updated_at
            )
        ");

        $stmt->execute([
            ':name'                     => trim((string)($data['name'] ?? '')),
            ':email'                    => $email,
            ':cpf'                      => isset($data['cpf']) && trim((string)$data['cpf']) !== '' ? trim((string)$data['cpf']) : null,
            ':phone'                    => isset($data['phone']) && trim((string)$data['phone']) !== '' ? trim((string)$data['phone']) : null,
            ':password_hash'            => (string)($data['password_hash'] ?? ''),
            ':status'                   => (string)($data['status'] ?? 'active'),
            ':shipping_zip'             => $shippingZip,
            ':shipping_address'         => $shippingAddress,
            ':shipping_number'          => $shippingNumber,
            ':shipping_complement'      => $shippingComplement,
            ':shipping_neighborhood'    => $shippingNeighborhood,
            ':shipping_city'            => $shippingCity,
            ':shipping_state'           => $shippingState,
            ':default_shipping_address' => $defaultShippingAddress,
            ':created_at'               => (string)($data['created_at'] ?? $now),
            ':updated_at'               => (string)($data['updated_at'] ?? $now),
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM domain_drop_customers WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $this->hydrateCustomer($row) : null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM domain_drop_customers WHERE LOWER(email) = :email LIMIT 1");
        $stmt->execute([':email' => strtolower(trim($email))]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $this->hydrateCustomer($row) : null;
    }

    public function updateAddress(int $id, array $addressData): bool
    {
        $zip = isset($addressData['shipping_zip']) && trim((string)$addressData['shipping_zip']) !== ''
            ? trim((string)$addressData['shipping_zip'])
            : (isset($addressData['zip']) && trim((string)$addressData['zip']) !== '' ? trim((string)$addressData['zip']) : null);

        $address = isset($addressData['shipping_address']) && trim((string)$addressData['shipping_address']) !== ''
            ? trim((string)$addressData['shipping_address'])
            : (isset($addressData['street']) && trim((string)$addressData['street']) !== '' ? trim((string)$addressData['street']) : null);

        $number = isset($addressData['shipping_number']) && trim((string)$addressData['shipping_number']) !== ''
            ? trim((string)$addressData['shipping_number'])
            : (isset($addressData['number']) && trim((string)$addressData['number']) !== '' ? trim((string)$addressData['number']) : null);

        $complement = isset($addressData['shipping_complement'])
            ? trim((string)$addressData['shipping_complement'])
            : (isset($addressData['complement']) ? trim((string)$addressData['complement']) : null);

        $neighborhood = isset($addressData['shipping_neighborhood']) && trim((string)$addressData['shipping_neighborhood']) !== ''
            ? trim((string)$addressData['shipping_neighborhood'])
            : (isset($addressData['neighborhood']) && trim((string)$addressData['neighborhood']) !== '' ? trim((string)$addressData['neighborhood']) : null);

        $city = isset($addressData['shipping_city']) && trim((string)$addressData['shipping_city']) !== ''
            ? trim((string)$addressData['shipping_city'])
            : (isset($addressData['city']) && trim((string)$addressData['city']) !== '' ? trim((string)$addressData['city']) : null);

        $state = isset($addressData['shipping_state']) && trim((string)$addressData['shipping_state']) !== ''
            ? strtoupper(trim((string)$addressData['shipping_state']))
            : (isset($addressData['state']) && trim((string)$addressData['state']) !== '' ? strtoupper(trim((string)$addressData['state'])) : null);

        $phone = isset($addressData['phone']) && trim((string)$addressData['phone']) !== ''
            ? trim((string)$addressData['phone'])
            : (isset($addressData['payer_phone']) && trim((string)$addressData['payer_phone']) !== '' ? trim((string)$addressData['payer_phone']) : null);

        $cpf = isset($addressData['cpf']) && trim((string)$addressData['cpf']) !== ''
            ? trim((string)$addressData['cpf'])
            : (isset($addressData['payer_cpf']) && trim((string)$addressData['payer_cpf']) !== '' ? trim((string)$addressData['payer_cpf']) : null);

        $payloadJson = json_encode([
            'zip'          => $zip,
            'street'       => $address,
            'number'       => $number,
            'complement'   => $complement,
            'neighborhood' => $neighborhood,
            'city'         => $city,
            'state'        => $state,
            'phone'        => $phone,
            'cpf'          => $cpf,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $sql = "
            UPDATE domain_drop_customers
            SET shipping_zip = :shipping_zip,
                shipping_address = :shipping_address,
                shipping_number = :shipping_number,
                shipping_complement = :shipping_complement,
                shipping_neighborhood = :shipping_neighborhood,
                shipping_city = :shipping_city,
                shipping_state = :shipping_state,
                default_shipping_address = :default_shipping_address,
                updated_at = :updated_at
        ";

        $params = [
            ':shipping_zip'             => $zip,
            ':shipping_address'         => $address,
            ':shipping_number'          => $number,
            ':shipping_complement'      => $complement,
            ':shipping_neighborhood'    => $neighborhood,
            ':shipping_city'            => $city,
            ':shipping_state'           => $state,
            ':default_shipping_address' => $payloadJson,
            ':updated_at'               => date('Y-m-d H:i:s'),
            ':id'                       => $id,
        ];

        if ($phone !== null) {
            $sql .= ", phone = :phone";
            $params[':phone'] = $phone;
        }
        if ($cpf !== null) {
            $sql .= ", cpf = :cpf";
            $params[':cpf'] = $cpf;
        }

        $sql .= " WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function updateProfile(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id, ':updated_at' => date('Y-m-d H:i:s')];

        if (isset($data['name']) && trim((string)$data['name']) !== '') {
            $fields[] = 'name = :name';
            $params[':name'] = trim((string)$data['name']);
        }
        if (isset($data['phone'])) {
            $fields[] = 'phone = :phone';
            $params[':phone'] = trim((string)$data['phone']) !== '' ? trim((string)$data['phone']) : null;
        }
        if (isset($data['cpf'])) {
            $fields[] = 'cpf = :cpf';
            $params[':cpf'] = trim((string)$data['cpf']) !== '' ? trim((string)$data['cpf']) : null;
        }
        if (isset($data['shipping_zip'])) {
            $fields[] = 'shipping_zip = :shipping_zip';
            $params[':shipping_zip'] = trim((string)$data['shipping_zip']) !== '' ? trim((string)$data['shipping_zip']) : null;
        }
        if (isset($data['shipping_address'])) {
            $fields[] = 'shipping_address = :shipping_address';
            $params[':shipping_address'] = trim((string)$data['shipping_address']) !== '' ? trim((string)$data['shipping_address']) : null;
        }
        if (isset($data['shipping_number'])) {
            $fields[] = 'shipping_number = :shipping_number';
            $params[':shipping_number'] = trim((string)$data['shipping_number']) !== '' ? trim((string)$data['shipping_number']) : null;
        }
        if (isset($data['shipping_complement'])) {
            $fields[] = 'shipping_complement = :shipping_complement';
            $params[':shipping_complement'] = trim((string)$data['shipping_complement']) !== '' ? trim((string)$data['shipping_complement']) : null;
        }
        if (isset($data['shipping_neighborhood'])) {
            $fields[] = 'shipping_neighborhood = :shipping_neighborhood';
            $params[':shipping_neighborhood'] = trim((string)$data['shipping_neighborhood']) !== '' ? trim((string)$data['shipping_neighborhood']) : null;
        }
        if (isset($data['shipping_city'])) {
            $fields[] = 'shipping_city = :shipping_city';
            $params[':shipping_city'] = trim((string)$data['shipping_city']) !== '' ? trim((string)$data['shipping_city']) : null;
        }
        if (isset($data['shipping_state'])) {
            $fields[] = 'shipping_state = :shipping_state';
            $params[':shipping_state'] = trim((string)$data['shipping_state']) !== '' ? strtoupper(trim((string)$data['shipping_state'])) : null;
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = 'updated_at = :updated_at';
        $sql = "UPDATE domain_drop_customers SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute($params);
    }

    public function saveAddressByEmail(string $email, array $addressData): bool
    {
        $customer = $this->findByEmail($email);
        if ($customer === null || empty($customer['id'])) {
            return false;
        }

        return $this->updateAddress((int)$customer['id'], $addressData);
    }

    public function listAll(int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM domain_drop_customers ORDER BY created_at DESC LIMIT " . max(1, (int)$limit) . " OFFSET " . max(0, (int)$offset)
        );
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map([$this, 'hydrateCustomer'], $rows);
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
            'items' => array_map([$this, 'hydrateCustomer'], $items),
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

    /**
     * Normaliza e decodifica campos estruturados do cliente.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function hydrateCustomer(array $row): array
    {
        $parsedAddress = null;
        if (!empty($row['default_shipping_address']) && is_string($row['default_shipping_address'])) {
            $decoded = @json_decode($row['default_shipping_address'], true);
            if (is_array($decoded)) {
                $parsedAddress = $decoded;
            }
        }

        if ($parsedAddress === null && (!empty($row['shipping_zip']) || !empty($row['shipping_address']))) {
            $parsedAddress = [
                'zip'          => $row['shipping_zip'] ?? null,
                'street'       => $row['shipping_address'] ?? null,
                'number'       => $row['shipping_number'] ?? null,
                'complement'   => $row['shipping_complement'] ?? null,
                'neighborhood' => $row['shipping_neighborhood'] ?? null,
                'city'         => $row['shipping_city'] ?? null,
                'state'        => $row['shipping_state'] ?? null,
                'phone'        => $row['phone'] ?? null,
                'cpf'          => $row['cpf'] ?? null,
            ];
        }

        $row['parsed_shipping_address'] = $parsedAddress;
        return $row;
    }
}
