<?php

namespace DomainSystem\Plugins\finance\Contracts;

interface FinanceRepositoryInterface
{
    public function getAllTransactions(): array;
    public function getTransactions(?int $limit = null): array;
    public function createTransaction(array $data): int;
    public function updateTransactionStatus(int $id, string $status): void;
}
