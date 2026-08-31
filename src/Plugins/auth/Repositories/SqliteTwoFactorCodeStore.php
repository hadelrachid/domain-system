<?php

namespace DomainSystem\Plugins\auth\Repositories;

use DomainSystem\Plugins\Database\Connection;
use DomainSystem\Plugins\auth\Contracts\TwoFactorCodeStoreInterface;

class SqliteTwoFactorCodeStore implements TwoFactorCodeStoreInterface
{
    private \PDO $db;

    public function __construct(Connection $connection)
    {
        $this->db = $connection->getPdo();
    }

    public function storeCode(int $userId, string $code, string $expiry): void
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET email_2fa_code = :code, email_2fa_expiry = :expiry WHERE id = :id"
        );
        $stmt->execute([':code' => $code, ':expiry' => $expiry, ':id' => $userId]);
    }
}
