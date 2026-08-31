<?php

namespace DomainSystem\Plugins\finance\Repositories;

use DomainSystem\Plugins\Database\QueryBuilder;
use DomainSystem\Plugins\finance\Contracts\FinanceRepositoryInterface;

class SqliteFinanceRepository implements FinanceRepositoryInterface
{
    private QueryBuilder $db;

    public function __construct(QueryBuilder $db)
    {
        $this->db = $db;
    }

    public function getAllTransactions(): array
    {
        return $this->db->table('financial_transactions')->orderBy('due_date', 'DESC')->get();
    }

    public function getTransactions(?int $limit = null): array
    {
        $query = $this->db->table('financial_transactions')->orderBy('due_date', 'DESC');
        if ($limit) {
            $query->limit($limit);
        }
        return $query->get();
    }

    public function createTransaction(array $data): int
    {
        return $this->db->table('financial_transactions')->insert($data);
    }

    public function updateTransactionStatus(int $id, string $status): void
    {
        $this->db->table('financial_transactions')->where('id', '=', $id)->update(['status' => $status]);
    }
}
