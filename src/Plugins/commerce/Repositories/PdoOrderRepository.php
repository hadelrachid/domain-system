<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\commerce\Repositories;

use DomainSystem\Core\Contracts\DatabaseConnectionInterface;
use DomainSystem\Plugins\commerce\Contracts\OrderRepositoryInterface;
use PDO;
use Throwable;

/**
 * Repositório PDO para persistência de pedidos financeiros (ANSI SQL compatível com SQLite e MySQL).
 */
class PdoOrderRepository implements OrderRepositoryInterface
{
    private PDO $pdo;
    private bool $tableEnsured = false;

    public function __construct(DatabaseConnectionInterface|PDO $connection, bool $autoEnsureSchema = true)
    {
        $this->pdo = $connection instanceof DatabaseConnectionInterface
            ? $connection->getPdo()
            : $connection;

        if ($autoEnsureSchema) {
            $this->ensureTable();
        }
    }

    public function ensureTable(): void
    {
        if ($this->tableEnsured) {
            return;
        }

        $sql = "
            CREATE TABLE IF NOT EXISTS commerce_orders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                item_type VARCHAR(32) NOT NULL DEFAULT 'course',
                item_id INTEGER NOT NULL,
                total DECIMAL(10,2) NOT NULL,
                status VARCHAR(32) NOT NULL DEFAULT 'pending',
                gateway VARCHAR(32) NOT NULL,
                payment_method VARCHAR(32) NOT NULL,
                order_ref VARCHAR(64) NOT NULL,
                transaction_id VARCHAR(128) NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ";

        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'mysql') {
            $sql = "
                CREATE TABLE IF NOT EXISTS commerce_orders (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    item_type VARCHAR(32) NOT NULL DEFAULT 'course',
                    item_id INT NOT NULL,
                    total DECIMAL(10,2) NOT NULL,
                    status VARCHAR(32) NOT NULL DEFAULT 'pending',
                    gateway VARCHAR(32) NOT NULL,
                    payment_method VARCHAR(32) NOT NULL,
                    order_ref VARCHAR(64) NOT NULL UNIQUE,
                    transaction_id VARCHAR(128) NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_commerce_user (user_id),
                    INDEX idx_commerce_status (status),
                    INDEX idx_commerce_created (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
        }

        try {
            $this->pdo->exec($sql);
            if ($driver === 'sqlite') {
                $this->pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_commerce_order_ref ON commerce_orders(order_ref)");
                $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_commerce_user ON commerce_orders(user_id)");
                $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_commerce_status ON commerce_orders(status)");
                $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_commerce_created ON commerce_orders(created_at)");
            }
            $this->tableEnsured = true;
        } catch (Throwable $e) {
            // Silencia falha se já existir
        }
    }

    public function createOrder(array $data): int
    {
        $this->ensureTable();
        $now = date('Y-m-d H:i:s');
        $orderRef = (string)($data['order_ref'] ?? ('ORD_' . date('YmdHis') . '_' . bin2hex(random_bytes(3))));

        $stmt = $this->pdo->prepare("
            INSERT INTO commerce_orders (
                user_id, item_type, item_id, total, status, gateway, payment_method, order_ref, transaction_id, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            (int)($data['user_id'] ?? 0),
            (string)($data['item_type'] ?? 'course'),
            (int)($data['item_id'] ?? 0),
            (float)($data['total'] ?? 0.0),
            (string)($data['status'] ?? 'pending'),
            (string)($data['gateway'] ?? 'mercadopago'),
            (string)($data['payment_method'] ?? 'pix'),
            $orderRef,
            isset($data['transaction_id']) ? (string)$data['transaction_id'] : null,
            $now,
            $now,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function findById(int $orderId): ?array
    {
        $this->ensureTable();
        $stmt = $this->pdo->prepare("SELECT * FROM commerce_orders WHERE id = ? LIMIT 1");
        $stmt->execute([$orderId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    public function findByOrderRef(string $orderRef): ?array
    {
        $this->ensureTable();
        $stmt = $this->pdo->prepare("SELECT * FROM commerce_orders WHERE order_ref = ? LIMIT 1");
        $stmt->execute([$orderRef]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    public function updateStatus(int $orderId, string $status, ?string $transactionId = null): bool
    {
        $this->ensureTable();
        $now = date('Y-m-d H:i:s');

        if ($transactionId !== null) {
            $stmt = $this->pdo->prepare("
                UPDATE commerce_orders 
                SET status = ?, transaction_id = ?, updated_at = ? 
                WHERE id = ?
            ");
            return $stmt->execute([$status, $transactionId, $now, $orderId]);
        }

        $stmt = $this->pdo->prepare("
            UPDATE commerce_orders 
            SET status = ?, updated_at = ? 
            WHERE id = ?
        ");
        return $stmt->execute([$status, $now, $orderId]);
    }

    public function getUserOrders(int $userId): array
    {
        $this->ensureTable();
        $stmt = $this->pdo->prepare("
            SELECT * FROM commerce_orders 
            WHERE user_id = ? 
            ORDER BY id DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getAllOrders(int $limit = 100, int $offset = 0, ?string $period = null): array
    {
        $this->ensureTable();
        $hasPeriod = ($period !== null && preg_match('/^\d{4}-\d{2}$/', $period));
        $whereSql = $hasPeriod ? "WHERE o.created_at LIKE :periodPattern" : "";

        try {
            $stmt = $this->pdo->prepare("
                SELECT o.*, u.name AS user_name, u.email AS user_email
                FROM commerce_orders o
                LEFT JOIN users u ON u.id = o.user_id
                {$whereSql}
                ORDER BY o.id DESC 
                LIMIT :limit OFFSET :offset
            ");
            if ($hasPeriod) {
                $stmt->bindValue(':periodPattern', $period . '%', PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
            $stmt->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            $fallbackWhere = $hasPeriod ? "WHERE created_at LIKE :periodPattern" : "";
            $stmt = $this->pdo->prepare("
                SELECT * FROM commerce_orders 
                {$fallbackWhere}
                ORDER BY id DESC 
                LIMIT :limit OFFSET :offset
            ");
            if ($hasPeriod) {
                $stmt->bindValue(':periodPattern', $period . '%', PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
            $stmt->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
    }

    public function getMetrics(?string $period = null): array
    {
        $this->ensureTable();
        $metrics = [
            'total_sales'   => 0.0,
            'paid_count'    => 0,
            'pending_count' => 0,
            'total_orders'  => 0,
        ];

        $hasPeriod = ($period !== null && preg_match('/^\d{4}-\d{2}$/', $period));
        $whereSql = $hasPeriod ? "WHERE created_at LIKE :periodPattern" : "";

        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = 'paid' THEN total ELSE 0 END) as total_sales,
                    SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count
                FROM commerce_orders
                {$whereSql}
            ");
            if ($hasPeriod) {
                $stmt->bindValue(':periodPattern', $period . '%', PDO::PARAM_STR);
            }
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (is_array($row)) {
                $metrics['total_orders']  = (int)($row['total_orders'] ?? 0);
                $metrics['total_sales']   = (float)($row['total_sales'] ?? 0.0);
                $metrics['paid_count']    = (int)($row['paid_count'] ?? 0);
                $metrics['pending_count'] = (int)($row['pending_count'] ?? 0);
            }
        } catch (Throwable $e) {}

        return $metrics;
    }

    public function getAvailablePeriods(): array
    {
        $this->ensureTable();
        try {
            $stmt = $this->pdo->query("
                SELECT DISTINCT SUBSTR(created_at, 1, 7) AS period 
                FROM commerce_orders 
                WHERE created_at IS NOT NULL AND created_at != ''
                ORDER BY period DESC
            ");
            $results = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
            $current = date('Y-m');
            if (!in_array($current, $results, true)) {
                array_unshift($results, $current);
            }
            return array_values(array_filter($results));
        } catch (Throwable $e) {
            return [date('Y-m')];
        }
    }

    public function getSalesTimeline(?string $period = null): array
    {
        $this->ensureTable();
        $hasPeriod = ($period !== null && preg_match('/^\d{4}-\d{2}$/', $period));

        if ($hasPeriod) {
            $daysInMonth = (int)date('t', (int)strtotime($period . '-01'));
            $labels = [];
            $totals = [];
            $counts = [];

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $dayStr = str_pad((string)$d, 2, '0', STR_PAD_LEFT);
                $labels[] = $dayStr;
                $totals[$dayStr] = 0.0;
                $counts[$dayStr] = 0;
            }

            try {
                $stmt = $this->pdo->prepare("
                    SELECT 
                        SUBSTR(created_at, 9, 2) as day_key,
                        SUM(CASE WHEN status = 'paid' THEN total ELSE 0 END) as day_total,
                        COUNT(CASE WHEN status = 'paid' THEN 1 END) as day_count
                    FROM commerce_orders
                    WHERE created_at LIKE :periodPattern
                    GROUP BY day_key
                ");
                $stmt->execute([':periodPattern' => $period . '%']);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $k = (string)($row['day_key'] ?? '');
                    if (isset($totals[$k])) {
                        $totals[$k] = (float)($row['day_total'] ?? 0.0);
                        $counts[$k] = (int)($row['day_count'] ?? 0);
                    }
                }
            } catch (Throwable $e) {}

            return [
                'labels' => $labels,
                'totals' => array_values($totals),
                'counts' => array_values($counts),
                'mode'   => 'days',
            ];
        }

        // Modo geral: agrupa por meses (últimos 12 meses)
        $monthNamesMap = [
            '01' => 'Jan', '02' => 'Fev', '03' => 'Mar', '04' => 'Abr',
            '05' => 'Mai', '06' => 'Jun', '07' => 'Jul', '08' => 'Ago',
            '09' => 'Set', '10' => 'Out', '11' => 'Nov', '12' => 'Dez'
        ];

        $labels = [];
        $totals = [];
        $counts = [];

        try {
            $stmt = $this->pdo->query("
                SELECT 
                    SUBSTR(created_at, 1, 7) as month_key,
                    SUM(CASE WHEN status = 'paid' THEN total ELSE 0 END) as month_total,
                    COUNT(CASE WHEN status = 'paid' THEN 1 END) as month_count
                FROM commerce_orders
                WHERE created_at IS NOT NULL AND created_at != ''
                GROUP BY month_key
                ORDER BY month_key ASC
                LIMIT 12
            ");
            if ($stmt) {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $k = (string)($row['month_key'] ?? '');
                    $parts = explode('-', $k);
                    $label = (count($parts) === 2 && isset($monthNamesMap[$parts[1]]))
                        ? ($monthNamesMap[$parts[1]] . '/' . substr($parts[0], 2))
                        : $k;

                    $labels[] = $label;
                    $totals[] = (float)($row['month_total'] ?? 0.0);
                    $counts[] = (int)($row['month_count'] ?? 0);
                }
            }
        } catch (Throwable $e) {}

        if (empty($labels)) {
            $cur = date('Y-m');
            $parts = explode('-', $cur);
            $curLabel = (count($parts) === 2 && isset($monthNamesMap[$parts[1]]))
                ? ($monthNamesMap[$parts[1]] . '/' . substr($parts[0], 2))
                : $cur;
            $labels = [$curLabel];
            $totals = [0.0];
            $counts = [0];
        }

        return [
            'labels' => $labels,
            'totals' => $totals,
            'counts' => $counts,
            'mode'   => 'months',
        ];
    }

    public function getPaymentMethodBreakdown(?string $period = null): array
    {
        $this->ensureTable();
        $hasPeriod = ($period !== null && preg_match('/^\d{4}-\d{2}$/', $period));
        $whereSql = $hasPeriod ? "AND created_at LIKE :periodPattern" : "";

        $breakdown = [];
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    payment_method,
                    SUM(total) as method_total,
                    COUNT(*) as method_count
                FROM commerce_orders
                WHERE status = 'paid' {$whereSql}
                GROUP BY payment_method
            ");
            if ($hasPeriod) {
                $stmt->bindValue(':periodPattern', $period . '%', PDO::PARAM_STR);
            }
            $stmt->execute();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $method = (string)($row['payment_method'] ?? 'outros');
                $meta = match($method) {
                    'pix'          => ['name' => 'Pix Instantâneo', 'color' => '#00d284'],
                    'credit_card'  => ['name' => 'Cartão de Crédito', 'color' => '#45f3ff'],
                    'boleto'       => ['name' => 'Boleto Bancário', 'color' => '#94a3b8'],
                    'checkout_pro' => ['name' => 'Checkout Pro', 'color' => '#c084fc'],
                    default        => ['name' => ucfirst($method), 'color' => '#f59e0b'],
                };
                $breakdown[$method] = [
                    'label' => $meta['name'],
                    'color' => $meta['color'],
                    'total' => (float)($row['method_total'] ?? 0.0),
                    'count' => (int)($row['method_count'] ?? 0),
                ];
            }
        } catch (Throwable $e) {}

        return $breakdown;
    }

    public function getOrdersForExport(?string $period = null, ?string $year = null): array
    {
        $this->ensureTable();
        $whereSql = "";
        $params = [];

        if ($period !== null && preg_match('/^\d{4}-\d{2}$/', $period)) {
            $whereSql = "WHERE o.created_at LIKE :pattern";
            $params[':pattern'] = $period . '%';
        } elseif ($year !== null && preg_match('/^\d{4}$/', $year)) {
            $whereSql = "WHERE o.created_at LIKE :pattern";
            $params[':pattern'] = $year . '%';
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT o.*, u.name AS user_name, u.email AS user_email
                FROM commerce_orders o
                LEFT JOIN users u ON u.id = o.user_id
                {$whereSql}
                ORDER BY o.id ASC
            ");
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            $fallbackWhere = str_replace('o.', '', $whereSql);
            $stmt = $this->pdo->prepare("
                SELECT * FROM commerce_orders 
                {$fallbackWhere}
                ORDER BY id ASC
            ");
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
    }
}
