<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Repositories;

use DomainSystem\Core\Contracts\DatabaseConnectionInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipDisputeRepositoryInterface;
use PDO;

/**
 * Repositório PDO para reclamações e intermediações com fornecedores.
 */
class PdoDropshipDisputeRepository implements DropshipDisputeRepositoryInterface
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
        $attachments = $data['attachments_json'] ?? [];
        if (is_array($attachments)) {
            $attachmentsJson = json_encode(array_values($attachments), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '[]';
        } else {
            $attachmentsJson = (string)$attachments;
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO domain_drop_disputes (
                order_ref, customer_id, customer_email, reason, description,
                attachments_json, seller_origin, seller_name, seller_email,
                seller_language, translated_message, mediation_status,
                mediation_channel, mediation_sent_at, created_at, updated_at
            ) VALUES (
                :order_ref, :customer_id, :customer_email, :reason, :description,
                :attachments_json, :seller_origin, :seller_name, :seller_email,
                :seller_language, :translated_message, :mediation_status,
                :mediation_channel, :mediation_sent_at, :created_at, :updated_at
            )
        ");

        $stmt->execute([
            ':order_ref'          => (string)($data['order_ref'] ?? ''),
            ':customer_id'        => isset($data['customer_id']) ? (int)$data['customer_id'] : null,
            ':customer_email'     => strtolower(trim((string)($data['customer_email'] ?? ''))),
            ':reason'             => (string)($data['reason'] ?? 'produto_danificado'),
            ':description'        => trim((string)($data['description'] ?? '')),
            ':attachments_json'   => $attachmentsJson,
            ':seller_origin'      => strtoupper((string)($data['seller_origin'] ?? 'CN')),
            ':seller_name'        => isset($data['seller_name']) ? (string)$data['seller_name'] : null,
            ':seller_email'       => isset($data['seller_email']) ? (string)$data['seller_email'] : null,
            ':seller_language'    => isset($data['seller_language']) ? (string)$data['seller_language'] : null,
            ':translated_message' => isset($data['translated_message']) ? (string)$data['translated_message'] : null,
            ':mediation_status'   => (string)($data['mediation_status'] ?? 'open'),
            ':mediation_channel'  => isset($data['mediation_channel']) ? (string)$data['mediation_channel'] : null,
            ':mediation_sent_at'  => isset($data['mediation_sent_at']) ? (string)$data['mediation_sent_at'] : null,
            ':created_at'         => (string)($data['created_at'] ?? $now),
            ':updated_at'         => (string)($data['updated_at'] ?? $now),
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM domain_drop_disputes WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $this->decodeRow($row) : null;
    }

    public function findByOrderRef(string $orderRef): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM domain_drop_disputes WHERE order_ref = :ref ORDER BY created_at DESC, id DESC");
        $stmt->execute([':ref' => $orderRef]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map([$this, 'decodeRow'], $rows);
    }

    public function findByCustomerEmail(string $email): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM domain_drop_disputes WHERE LOWER(customer_email) = :email ORDER BY created_at DESC, id DESC");
        $stmt->execute([':email' => strtolower(trim($email))]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map([$this, 'decodeRow'], $rows);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listRecent(int $limit = 30): array
    {
        $lim = max(1, min(100, $limit));
        $stmt = $this->pdo->query("SELECT * FROM domain_drop_disputes ORDER BY created_at DESC, id DESC LIMIT {$lim}");
        $rows = $stmt !== false ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];

        return array_map([$this, 'decodeRow'], $rows);
    }

    public function mapByOrderRefs(array $orderRefs): array
    {
        $refs = array_values(array_unique(array_filter(array_map('strval', $orderRefs))));
        if ($refs === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($refs), '?'));
        $stmt = $this->pdo->prepare("SELECT * FROM domain_drop_disputes WHERE order_ref IN ($placeholders) ORDER BY created_at DESC, id DESC");
        $stmt->execute($refs);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $grouped = [];
        foreach ($rows as $row) {
            $decoded = $this->decodeRow($row);
            $ref = (string)($decoded['order_ref'] ?? '');
            if ($ref !== '') {
                $grouped[$ref][] = $decoded;
            }
        }

        return $grouped;
    }

    public function markMediationSent(
        int $disputeId,
        string $sellerLanguage,
        string $translatedMessage,
        string $channel = 'email'
    ): bool {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare("
            UPDATE domain_drop_disputes
            SET seller_language = :lang,
                translated_message = :msg,
                mediation_status = 'sent',
                mediation_channel = :channel,
                mediation_sent_at = :sent_at,
                updated_at = :updated_at
            WHERE id = :id
        ");

        return $stmt->execute([
            ':lang'       => $sellerLanguage,
            ':msg'        => $translatedMessage,
            ':channel'    => $channel,
            ':sent_at'    => $now,
            ':updated_at' => $now,
            ':id'         => $disputeId,
        ]);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function decodeRow(array $row): array
    {
        $raw = (string)($row['attachments_json'] ?? '[]');
        $decoded = json_decode($raw, true);
        $row['attachments'] = is_array($decoded) ? $decoded : [];

        return $row;
    }
}
