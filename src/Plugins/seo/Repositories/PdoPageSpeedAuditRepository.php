<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\seo\Repositories;

use DomainSystem\Core\Contracts\DatabaseConnectionInterface;
use PDO;
use Throwable;

/**
 * Repositório PDO para persistência e consulta do histórico de auditorias de performance (ANSI SQL / SQLite / MySQL).
 */
class PdoPageSpeedAuditRepository
{
    private PDO $pdo;
    private bool $ensured = false;

    public function __construct(PDO|DatabaseConnectionInterface $connection)
    {
        $this->pdo = $connection instanceof DatabaseConnectionInterface
            ? $connection->getPdo()
            : $connection;
        $this->ensureTable();
    }

    public function ensureTable(): void
    {
        if ($this->ensured) {
            return;
        }

        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'mysql') {
            $sql = "
                CREATE TABLE IF NOT EXISTS domain_seo_audits (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    url VARCHAR(500) NOT NULL,
                    strategy VARCHAR(16) NOT NULL,
                    score INT NOT NULL,
                    source VARCHAR(32) NOT NULL DEFAULT 'google_pagespeed_api',
                    lcp VARCHAR(32) NULL,
                    cls VARCHAR(32) NULL,
                    inp VARCHAR(32) NULL,
                    fcp VARCHAR(32) NULL,
                    ttfb VARCHAR(32) NULL,
                    metrics_json LONGTEXT NULL,
                    opportunities_json LONGTEXT NULL,
                    ai_diagnostics_json LONGTEXT NULL,
                    raw_report_url VARCHAR(500) NULL,
                    created_at DATETIME NOT NULL,
                    INDEX idx_seo_audit_strategy (strategy),
                    INDEX idx_seo_audit_created (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
        } else {
            $sql = "
                CREATE TABLE IF NOT EXISTS domain_seo_audits (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    url TEXT NOT NULL,
                    strategy TEXT NOT NULL,
                    score INTEGER NOT NULL,
                    source TEXT NOT NULL DEFAULT 'google_pagespeed_api',
                    lcp TEXT NULL,
                    cls TEXT NULL,
                    inp TEXT NULL,
                    fcp TEXT NULL,
                    ttfb TEXT NULL,
                    metrics_json TEXT NULL,
                    opportunities_json TEXT NULL,
                    ai_diagnostics_json TEXT NULL,
                    raw_report_url TEXT NULL,
                    created_at DATETIME NOT NULL
                )
            ";
        }

        try {
            $this->pdo->exec($sql);
            if ($driver === 'sqlite') {
                $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_seo_audit_strategy ON domain_seo_audits(strategy)");
                $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_seo_audit_created ON domain_seo_audits(created_at)");
            }
            $this->ensured = true;
        } catch (Throwable $e) {
            // Silencia falha se já existir
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function save(array $data): int
    {
        $this->ensureTable();

        $metricsJson = !empty($data['metrics']) && is_array($data['metrics'])
            ? json_encode($data['metrics'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            : (string)($data['metrics_json'] ?? '{}');

        $opportunitiesJson = !empty($data['opportunities']) && is_array($data['opportunities'])
            ? json_encode($data['opportunities'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            : (string)($data['opportunities_json'] ?? '[]');

        $aiJson = !empty($data['ai_diagnostics']) && is_array($data['ai_diagnostics'])
            ? json_encode($data['ai_diagnostics'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            : (string)($data['ai_diagnostics_json'] ?? '{}');

        $stmt = $this->pdo->prepare("
            INSERT INTO domain_seo_audits (
                url, strategy, score, source, lcp, cls, inp, fcp, ttfb,
                metrics_json, opportunities_json, ai_diagnostics_json,
                raw_report_url, created_at
            ) VALUES (
                :url, :strategy, :score, :source, :lcp, :cls, :inp, :fcp, :ttfb,
                :metrics_json, :opportunities_json, :ai_diagnostics_json,
                :raw_report_url, :created_at
            )
        ");

        $stmt->execute([
            ':url'                 => (string)($data['url'] ?? 'https://rachidd.com'),
            ':strategy'            => (string)($data['strategy'] ?? 'mobile'),
            ':score'               => (int)($data['score'] ?? 0),
            ':source'              => (string)($data['source'] ?? 'google_pagespeed_api'),
            ':lcp'                 => isset($data['lcp']) ? (string)$data['lcp'] : null,
            ':cls'                 => isset($data['cls']) ? (string)$data['cls'] : null,
            ':inp'                 => isset($data['inp']) ? (string)$data['inp'] : null,
            ':fcp'                 => isset($data['fcp']) ? (string)$data['fcp'] : null,
            ':ttfb'                => isset($data['ttfb']) ? (string)$data['ttfb'] : null,
            ':metrics_json'        => $metricsJson,
            ':opportunities_json'  => $opportunitiesJson,
            ':ai_diagnostics_json' => $aiJson,
            ':raw_report_url'      => isset($data['raw_report_url']) ? (string)$data['raw_report_url'] : null,
            ':created_at'          => (string)($data['audited_at'] ?? ($data['created_at'] ?? date('Y-m-d H:i:s'))),
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function getLatestByStrategy(string $strategy = 'mobile'): ?array
    {
        $this->ensureTable();
        $stmt = $this->pdo->prepare("
            SELECT * FROM domain_seo_audits
            WHERE strategy = :strategy
            ORDER BY created_at DESC, id DESC
            LIMIT 1
        ");
        $stmt->execute([':strategy' => strtolower($strategy)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $this->hydrateRow($row) : null;
    }

    /**
     * Retorna o último par auditado: [ 'mobile' => ..., 'desktop' => ... ]
     *
     * @return array{mobile: ?array, desktop: ?array}
     */
    public function getLatestPair(): array
    {
        return [
            'mobile'  => $this->getLatestByStrategy('mobile'),
            'desktop' => $this->getLatestByStrategy('desktop'),
        ];
    }

    /**
     * Retorna o histórico das auditorias recentes.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getHistory(int $limit = 20): array
    {
        $this->ensureTable();
        $lim = max(1, min(100, $limit));
        $stmt = $this->pdo->query("SELECT * FROM domain_seo_audits ORDER BY created_at DESC, id DESC LIMIT {$lim}");
        $rows = $stmt !== false ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];

        return array_map([$this, 'hydrateRow'], $rows);
    }

    /**
     * Retorna um resumo executivo formatado para consulta rápida da I.A.
     *
     * @return array<string, mixed>
     */
    public function getSummaryForAi(): array
    {
        $pair = $this->getLatestPair();
        $mobile = $pair['mobile'];
        $desktop = $pair['desktop'];

        return [
            'status'         => 'active',
            'latest_audits'  => [
                'mobile'  => $mobile ? [
                    'score'           => $mobile['score'],
                    'lcp'             => $mobile['lcp'],
                    'cls'             => $mobile['cls'],
                    'inp'             => $mobile['inp'],
                    'opportunities'   => $mobile['opportunities'],
                    'ai_instructions' => $mobile['ai_diagnostics']['ai_prompt_instruction'] ?? '',
                    'audited_at'      => $mobile['created_at'],
                ] : null,
                'desktop' => $desktop ? [
                    'score'           => $desktop['score'],
                    'lcp'             => $desktop['lcp'],
                    'cls'             => $desktop['cls'],
                    'inp'             => $desktop['inp'],
                    'opportunities'   => $desktop['opportunities'],
                    'ai_instructions' => $desktop['ai_diagnostics']['ai_prompt_instruction'] ?? '',
                    'audited_at'      => $desktop['created_at'],
                ] : null,
            ],
            'prompt_for_assistant' => "Como assistente Antigravity, leia as últimas oportunidades do PageSpeed listadas acima e aplique correções automáticas no código (compressão de assets, eliminação de CSS redundante e ajuste de dimensões/lazy de imagens).",
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function hydrateRow(array $row): array
    {
        $row['metrics'] = !empty($row['metrics_json']) ? @json_decode($row['metrics_json'], true) : [];
        $row['opportunities'] = !empty($row['opportunities_json']) ? @json_decode($row['opportunities_json'], true) : [];
        $row['ai_diagnostics'] = !empty($row['ai_diagnostics_json']) ? @json_decode($row['ai_diagnostics_json'], true) : [];
        $row['score'] = (int)($row['score'] ?? 0);

        return $row;
    }
}
