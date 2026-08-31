<?php

namespace DomainSystem\Plugins\auth\Repositories;

use DomainSystem\Plugins\Database\QueryBuilder;
use DomainSystem\Plugins\auth\Contracts\TwoFactorCodeStoreInterface;

class SqliteTwoFactorCodeStore implements TwoFactorCodeStoreInterface
{
    private QueryBuilder $db;

    public function __construct(QueryBuilder $db)
    {
        $this->db = $db;
    }

    public function storeCode(int $userId, string $code, string $expiry): void
    {
        $this->db->table('users')->where('id', '=', $userId)->update([
            'email_2fa_code' => $code,
            'email_2fa_expiry' => $expiry
        ]);
    }
}
