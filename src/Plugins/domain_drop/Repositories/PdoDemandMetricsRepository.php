<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Repositories;

use DomainSystem\Core\Contracts\DatabaseConnectionInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DemandMetricsRepositoryInterface;
use PDO;
use Throwable;

final class PdoDemandMetricsRepository implements DemandMetricsRepositoryInterface
{
    private readonly PDO $pdo;

    public function __construct(DatabaseConnectionInterface|PDO $connection)
    {
        $this->pdo = $connection instanceof DatabaseConnectionInterface
            ? $connection->getPdo()
            : $connection;

        DomainDropSchemaEnsurer::ensure($this->pdo);
    }

    public function incrementMetric(
        string $productId,
        string $productTitle,
        string $categorySlug,
        string $actionType,
        ?\DomainSystem\Plugins\domain_drop\DTO\DemandEvent $event = null
    ): void {
        $cleanId = trim($productId);
        if ($cleanId === '') {
            return;
        }

        $title = trim(mb_substr($productTitle, 0, 255));
        $category = trim(mb_substr($categorySlug, 0, 64)) ?: 'geral';
        $period = date('Y-m');
        $now = date('Y-m-d H:i:s');

        $driver = (string)$this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        // Pontuação ponderada: conversions=10, buy_clicks=4, detail_clicks=1
        $detailInc = ($actionType === 'view_details' || $actionType === 'detail_click') ? 1 : 0;
        $buyInc = ($actionType === 'buy_click') ? 1 : 0;
        $convInc = ($actionType === 'conversion') ? 1 : 0;
        $scoreInc = ($convInc * 10) + ($buyInc * 4) + ($detailInc * 1);

        try {
            if ($driver === 'mysql') {
                $sql = "
                    INSERT INTO domain_drop_product_demand 
                        (product_id, product_title, category_slug, period_year_month, detail_clicks, buy_clicks, conversions, demand_score, last_searched_at)
                    VALUES 
                        (:pid, :title, :cat, :period, :d_inc, :b_inc, :c_inc, :score, :searched_at)
                    ON DUPLICATE KEY UPDATE
                        product_title = VALUES(product_title),
                        category_slug = VALUES(category_slug),
                        detail_clicks = detail_clicks + VALUES(detail_clicks),
                        buy_clicks = buy_clicks + VALUES(buy_clicks),
                        conversions = conversions + VALUES(conversions),
                        demand_score = demand_score + VALUES(demand_score),
                        last_searched_at = VALUES(last_searched_at)
                ";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    ':pid'         => $cleanId,
                    ':title'       => $title,
                    ':cat'         => $category,
                    ':period'      => $period,
                    ':d_inc'       => $detailInc,
                    ':b_inc'       => $buyInc,
                    ':c_inc'       => $convInc,
                    ':score'       => $scoreInc,
                    ':searched_at' => $now,
                ]);
            } else {
                // SQLite moderno (ON CONFLICT)
                $sql = "
                    INSERT INTO domain_drop_product_demand 
                        (product_id, product_title, category_slug, period_year_month, detail_clicks, buy_clicks, conversions, demand_score, last_searched_at)
                    VALUES 
                        (:pid, :title, :cat, :period, :d_inc, :b_inc, :c_inc, :score, :searched_at)
                    ON CONFLICT(product_id, period_year_month) DO UPDATE SET
                        product_title = excluded.product_title,
                        category_slug = excluded.category_slug,
                        detail_clicks = detail_clicks + excluded.detail_clicks,
                        buy_clicks = buy_clicks + excluded.buy_clicks,
                        conversions = conversions + excluded.conversions,
                        demand_score = demand_score + excluded.demand_score,
                        last_searched_at = excluded.last_searched_at
                ";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    ':pid'         => $cleanId,
                    ':title'       => $title,
                    ':cat'         => $category,
                    ':period'      => $period,
                    ':d_inc'       => $detailInc,
                    ':b_inc'       => $buyInc,
                    ':c_inc'       => $convInc,
                    ':score'       => $scoreInc,
                    ':searched_at' => $now,
                ]);
            }

            // Grava a trilha de auditoria individual com identificação de usuário (logado vs externo)
            $userType = $event ? $event->userType : 'externo';
            $cId = $event?->customerId;
            $cName = $event?->customerName;
            $cEmail = $event?->customerEmail;
            $sessId = $event?->sessionId;
            $ip = $event?->ipAddress ?: (\DomainSystem\Plugins\domain_drop\DTO\DemandEvent::resolveClientIp());

            $logStmt = $this->pdo->prepare("
                INSERT INTO domain_drop_demand_activity_logs
                    (product_id, product_title, category_slug, action_type, user_type, customer_id, customer_name, customer_email, session_id, ip_address, created_at)
                VALUES
                    (:pid, :title, :cat, :action, :utype, :cid, :cname, :cemail, :sess, :ip, :created)
            ");
            $logStmt->execute([
                ':pid'     => $cleanId,
                ':title'   => $title,
                ':cat'     => $category,
                ':action'  => $actionType,
                ':utype'   => $userType,
                ':cid'     => $cId,
                ':cname'   => $cName,
                ':cemail'  => $cEmail,
                ':sess'    => $sessId,
                ':ip'      => $ip,
                ':created' => $now,
            ]);
        } catch (Throwable $e) {
            error_log('[PdoDemandMetricsRepository] Falha ao incrementar métrica: ' . $e->getMessage());
        }
    }

    public function getRecentActivityLogs(int $limit = 25): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, product_id, product_title, category_slug, action_type, user_type, customer_id, customer_name, customer_email, session_id, ip_address, created_at
            FROM domain_drop_demand_activity_logs
            ORDER BY id DESC
            LIMIT :lim
        ");
        $stmt->bindValue(':lim', max(1, min(100, $limit)), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLivePulseMetrics(): array
    {
        $currentPeriod = date('Y-m');
        $todayStart = date('Y-m-d 00:00:00');

        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) AS active_products_count,
                COALESCE(SUM(detail_clicks), 0) AS total_detail_clicks,
                COALESCE(SUM(buy_clicks), 0) AS total_buy_clicks,
                COALESCE(SUM(conversions), 0) AS total_conversions,
                COALESCE(SUM(demand_score), 0) AS total_demand_score
            FROM domain_drop_product_demand
            WHERE period_year_month = :period
        ");
        $stmt->execute([':period' => $currentPeriod]);
        $summary = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        // Top 8 produtos mais quentes do mês em tempo real
        $topStmt = $this->pdo->prepare("
            SELECT product_id, product_title, category_slug, detail_clicks, buy_clicks, conversions, demand_score, last_searched_at
            FROM domain_drop_product_demand
            WHERE period_year_month = :period
            ORDER BY demand_score DESC, buy_clicks DESC
            LIMIT 8
        ");
        $topStmt->execute([':period' => $currentPeriod]);
        $topProducts = $topStmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'period'                => $currentPeriod,
            'active_products_count' => (int)($summary['active_products_count'] ?? 0),
            'total_detail_clicks'   => (int)($summary['total_detail_clicks'] ?? 0),
            'total_buy_clicks'      => (int)($summary['total_buy_clicks'] ?? 0),
            'total_conversions'     => (int)($summary['total_conversions'] ?? 0),
            'total_demand_score'    => (int)($summary['total_demand_score'] ?? 0),
            'top_products'          => $topProducts,
        ];
    }

    public function getMonthlyRankings(string $yearMonth, int $limit = 20): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                product_id,
                product_title,
                category_slug,
                period_year_month,
                detail_clicks,
                buy_clicks,
                conversions,
                demand_score,
                ROUND((buy_clicks * 100.0 / NULLIF(detail_clicks + buy_clicks, 0)), 1) AS buy_intent_ratio,
                last_searched_at
            FROM domain_drop_product_demand
            WHERE period_year_month = :period
            ORDER BY demand_score DESC, buy_clicks DESC, conversions DESC
            LIMIT :lim
        ");
        $stmt->bindValue(':period', $yearMonth);
        $stmt->bindValue(':lim', max(1, min(100, $limit)), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAudienceCategoryProfile(string $yearMonth): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                category_slug,
                SUM(detail_clicks) AS category_details,
                SUM(buy_clicks) AS category_buys,
                SUM(demand_score) AS category_score
            FROM domain_drop_product_demand
            WHERE period_year_month = :period
            GROUP BY category_slug
            ORDER BY category_score DESC
        ");
        $stmt->execute([':period' => $yearMonth]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $profile = [];
        foreach ($rows as $row) {
            $cat = (string)($row['category_slug'] ?? 'geral');
            $profile[$cat] = [
                'score'         => (int)($row['category_score'] ?? 0),
                'detail_clicks' => (int)($row['category_details'] ?? 0),
                'buy_clicks'    => (int)($row['category_buys'] ?? 0),
            ];
        }

        return $profile;
    }

    public function promoteInteractions(
        string $sessionId,
        int $customerId,
        string $customerName,
        string $customerEmail,
        ?string $ipAddress = null
    ): int {
        $cleanSession = trim($sessionId);
        $cleanIp = trim($ipAddress ?? '');
        if ($customerId <= 0 || ($cleanSession === '' && $cleanIp === '')) {
            return 0;
        }

        try {
            // Janela temporal recente para correlação por IP (últimas 4 horas)
            $recentThreshold = date('Y-m-d H:i:s', time() - 14400);

            if ($cleanSession !== '' && $cleanIp !== '') {
                $sql = "
                    UPDATE domain_drop_demand_activity_logs
                    SET 
                        customer_id = :cid,
                        customer_name = :cname,
                        customer_email = :cemail,
                        user_type = 'cliente_logado'
                    WHERE (
                        session_id = :sess 
                        OR (ip_address = :ip AND created_at >= :recent)
                    )
                    AND (customer_id IS NULL OR customer_id = 0 OR user_type = 'externo')
                ";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    ':cid'    => $customerId,
                    ':cname'  => $customerName,
                    ':cemail' => $customerEmail,
                    ':sess'   => $cleanSession,
                    ':ip'     => $cleanIp,
                    ':recent' => $recentThreshold,
                ]);
            } elseif ($cleanSession !== '') {
                $sql = "
                    UPDATE domain_drop_demand_activity_logs
                    SET 
                        customer_id = :cid,
                        customer_name = :cname,
                        customer_email = :cemail,
                        user_type = 'cliente_logado'
                    WHERE session_id = :sess
                      AND (customer_id IS NULL OR customer_id = 0 OR user_type = 'externo')
                ";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    ':cid'    => $customerId,
                    ':cname'  => $customerName,
                    ':cemail' => $customerEmail,
                    ':sess'   => $cleanSession,
                ]);
            } else {
                $sql = "
                    UPDATE domain_drop_demand_activity_logs
                    SET 
                        customer_id = :cid,
                        customer_name = :cname,
                        customer_email = :cemail,
                        user_type = 'cliente_logado'
                    WHERE ip_address = :ip
                      AND created_at >= :recent
                      AND (customer_id IS NULL OR customer_id = 0 OR user_type = 'externo')
                ";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    ':cid'    => $customerId,
                    ':cname'  => $customerName,
                    ':cemail' => $customerEmail,
                    ':ip'     => $cleanIp,
                    ':recent' => $recentThreshold,
                ]);
            }

            return $stmt->rowCount();
        } catch (Throwable $e) {
            error_log('[PdoDemandMetricsRepository] Falha ao promover interações de sessão/IP: ' . $e->getMessage());
            return 0;
        }
    }

    public function promoteSessionInteractions(
        string $sessionId,
        int $customerId,
        string $customerName,
        string $customerEmail
    ): int {
        return $this->promoteInteractions($sessionId, $customerId, $customerName, $customerEmail);
    }
}
